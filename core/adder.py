import asyncio
import logging
import csv
import time
from telethon import errors
from .account_manager import AccountRotator, NoUsableAccountsError
from .telethon_client import TelethonClient
from .database import DB

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("../logs/adder.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("adder")

class Adder:
    def __init__(self):
        """Initialize the adder"""
        self.db = DB.get_instance()
        self.account_rotator = AccountRotator()
        self.current_operation = None
        self.stop_requested = False
        logger.info("Adder initialized")
    
    async def add_members(self, target_channel, members_file, user_id, operation_id=None):
        """Add members from a CSV file to a target channel"""
        if not operation_id:
            # Create new operation record
            operation_id = self.db.create_operation(user_id, 'add', target_channel)
        
        self.current_operation = operation_id
        self.db.update_operation_progress(operation_id, 0, 'running')
        
        # Read members from CSV file
        members = []
        try:
            with open(members_file, 'r', newline='', encoding='utf-8') as f:
                reader = csv.reader(f)
                # Skip header
                next(reader, None)
                for row in reader:
                    if row and len(row) > 0 and row[0]:  # Username or ID in first column
                        members.append(row[0])
        except Exception as e:
            logger.error(f"Failed to read members file: {str(e)}")
            self.db.update_operation_progress(operation_id, 0, 'failed')
            self.db.log_action(
                operation_id, 
                0, 
                'add_error', 
                'error', 
                f"Failed to read members file: {str(e)}"
            )
            return False, f"Failed to read members file: {str(e)}"
        
        if not members:
            logger.error("No members found in the CSV file")
            self.db.update_operation_progress(operation_id, 0, 'failed')
            self.db.log_action(
                operation_id, 
                0, 
                'add_error', 
                'error', 
                "No members found in the CSV file"
            )
            return False, "No members found in the CSV file"
        
        total_members = len(members)
        added_members = 0
        failed_members = 0
        last_account_id = None
        
        # Process members in chunks (users per account)
        users_per_account = 40  # Limit per account to avoid restrictions
        
        for i in range(0, total_members, users_per_account):
            if self.stop_requested:
                break
                
            member_chunk = members[i:i+users_per_account]
            chunk_size = len(member_chunk)
            
            try:
                # Get next available account
                account = self.account_rotator.get_next_account()
                last_account_id = account['id']
                
                # Create client with this account
                client = TelethonClient(account)
                await client.connect()
                
                if not client.connected:
                    self.account_rotator.mark_account_status(account['id'], 'restricted', 1)
                    logger.warning(f"Account {account['phone']} failed to connect, marking as restricted")
                    continue
                
                # Log operation start for this chunk
                self.db.log_action(
                    operation_id, 
                    account['id'], 
                    'add_start', 
                    'success', 
                    f"Starting to add {chunk_size} members to {target_channel}"
                )
                
                # Add members
                success, result = await client.add_users_to_channel(target_channel, member_chunk)
                
                if success:
                    added_in_chunk = result.get("success", 0)
                    failed_in_chunk = result.get("failed", 0)
                    
                    added_members += added_in_chunk
                    failed_members += failed_in_chunk
                    
                    # Update operation progress
                    progress = min(100, int(((i + chunk_size) / total_members) * 100))
                    self.db.update_operation_progress(operation_id, progress)
                    
                    # Log success
                    self.db.log_action(
                        operation_id, 
                        account['id'], 
                        'add_batch', 
                        'success', 
                        f"Added {added_in_chunk} members, failed {failed_in_chunk}, total added: {added_members}"
                    )
                    
                    # Mark account as used but still active
                    self.account_rotator.mark_account_status(account['id'], 'active')
                else:
                    failed_members += chunk_size
                    
                    # Log failure
                    self.db.log_action(
                        operation_id, 
                        account['id'], 
                        'add_error', 
                        'error', 
                        f"Failed to add members: {result}"
                    )
                
                # Disconnect client
                await client.disconnect()
                
                # Sleep before using next account to avoid abuse
                await asyncio.sleep(60)
                
            except NoUsableAccountsError:
                logger.error("No usable accounts available for adding members")
                self.db.log_action(
                    operation_id, 
                    last_account_id if last_account_id else 0, 
                    'add_error', 
                    'error', 
                    "No usable accounts available"
                )
                self.db.update_operation_progress(operation_id, int((added_members / total_members) * 100), 'failed')
                return False, "No usable accounts available"
                
            except errors.FloodWaitError as e:
                cooldown_time = e.seconds
                logger.warning(f"FloodWaitError: Must wait {cooldown_time} seconds")
                
                if last_account_id:
                    # Calculate cooldown hours (convert seconds to hours, round up)
                    cooldown_hours = (cooldown_time + 3599) // 3600  # Round up to nearest hour
                    self.account_rotator.mark_account_status(last_account_id, 'restricted', cooldown_hours)
                    
                    self.db.log_action(
                        operation_id, 
                        last_account_id, 
                        'add_flood', 
                        'warning', 
                        f"Rate limited, cooldown: {cooldown_time} seconds"
                    )
                
                # Continue with next account
                await asyncio.sleep(5)
                
            except Exception as e:
                logger.error(f"Error during adding members: {str(e)}")
                if last_account_id:
                    self.db.log_action(
                        operation_id, 
                        last_account_id, 
                        'add_error', 
                        'error', 
                        str(e)
                    )
                
                # Continue with next account
                await asyncio.sleep(5)
        
        # Mark operation as completed
        status = 'completed' if not self.stop_requested else 'stopped'
        self.db.update_operation_progress(operation_id, 100, status)
        
        self.db.log_action(
            operation_id, 
            last_account_id if last_account_id else 0, 
            'add_complete', 
            'success', 
            f"Added {added_members} members, failed {failed_members}"
        )
        
        return True, f"Successfully added {added_members} members, failed {failed_members}"
    
    def stop_adding(self):
        """Stop the current adding operation"""
        self.stop_requested = True
        logger.info("Stop requested for adding operation")
        return True
