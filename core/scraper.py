import asyncio
import logging
import csv
import os
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
        logging.FileHandler("../logs/scraper.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("scraper")

class Scraper:
    def __init__(self):
        """Initialize the scraper"""
        self.db = DB.get_instance()
        self.account_rotator = AccountRotator()
        self.current_operation = None
        self.stop_requested = False
        logger.info("Scraper initialized")
    
    async def scrape_channel(self, channel, output_file, user_id, limit=0, operation_id=None):
        """Scrape members from a channel and save to CSV"""
        if not operation_id:
            # Create new operation record
            operation_id = self.db.create_operation(user_id, 'scrape', channel)
        
        self.current_operation = operation_id
        self.db.update_operation_progress(operation_id, 0, 'running')
        
        # Ensure the logs directory exists
        os.makedirs("../logs", exist_ok=True)
        
        # Initialize CSV file with headers
        with open(output_file, 'w', newline='', encoding='utf-8') as f:
            writer = csv.writer(f)
            writer.writerow(['Username', 'User ID', 'First Name', 'Last Name', 'Phone', 'Is Bot'])
        
        total_scraped = 0
        last_account_id = None
        
        while not self.stop_requested:
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
                
                # Log operation start
                self.db.log_action(
                    operation_id, 
                    account['id'], 
                    'scrape_start', 
                    'success', 
                    f"Starting scrape of {channel}"
                )
                
                # Get participants
                batch_size = 200  # Get participants in batches
                participants = await client.get_participants(channel, batch_size)
                
                if not participants:
                    self.db.log_action(
                        operation_id, 
                        account['id'], 
                        'scrape_error', 
                        'error', 
                        f"No participants found in {channel}"
                    )
                    await client.disconnect()
                    self.db.update_operation_progress(operation_id, 0, 'failed')
                    return False, "No participants found"
                
                # Update CSV file with this batch
                with open(output_file, 'a', newline='', encoding='utf-8') as f:
                    writer = csv.writer(f)
                    for user in participants:
                        # Write user info to CSV
                        writer.writerow([
                            user.username,
                            user.id,
                            user.first_name,
                            user.last_name,
                            user.phone if hasattr(user, 'phone') else '',
                            user.bot
                        ])
                
                total_scraped += len(participants)
                
                # Update operation progress
                progress = 100 if limit == 0 or total_scraped >= limit else int((total_scraped / limit) * 100)
                self.db.update_operation_progress(operation_id, progress)
                
                # Log success
                self.db.log_action(
                    operation_id, 
                    account['id'], 
                    'scrape_batch', 
                    'success', 
                    f"Scraped {len(participants)} members, total: {total_scraped}"
                )
                
                # Mark account as used but still active
                self.account_rotator.mark_account_status(account['id'], 'active')
                
                # Disconnect client
                await client.disconnect()
                
                # Check if we've reached the limit or no more participants
                if (limit > 0 and total_scraped >= limit) or len(participants) < batch_size:
                    # We've reached the limit or no more participants
                    break
                
                # Sleep before using next account to avoid abuse
                await asyncio.sleep(30)
                
            except NoUsableAccountsError:
                logger.error("No usable accounts available for scraping")
                self.db.log_action(
                    operation_id, 
                    last_account_id if last_account_id else 0, 
                    'scrape_error', 
                    'error', 
                    "No usable accounts available"
                )
                self.db.update_operation_progress(operation_id, total_scraped, 'failed')
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
                        'scrape_flood', 
                        'warning', 
                        f"Rate limited, cooldown: {cooldown_time} seconds"
                    )
                
                # Continue with next account
                await asyncio.sleep(5)
                
            except Exception as e:
                logger.error(f"Error during scraping: {str(e)}")
                if last_account_id:
                    self.db.log_action(
                        operation_id, 
                        last_account_id, 
                        'scrape_error', 
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
            'scrape_complete', 
            'success', 
            f"Scraped a total of {total_scraped} members"
        )
        
        return True, f"Successfully scraped {total_scraped} members"
    
    def stop_scraping(self):
        """Stop the current scraping operation"""
        self.stop_requested = True
        logger.info("Stop requested for scraping operation")
        return True
