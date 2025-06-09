from telethon import TelegramClient, errors
from telethon.tl.functions.channels import GetParticipantsRequest
from telethon.tl.functions.channels import InviteToChannelRequest
from telethon.tl.types import ChannelParticipantsSearch
from telethon.tl.types import InputPeerUser, InputPeerChannel
import asyncio
import logging
import os
import time
from datetime import datetime, timedelta
import json

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("../logs/telethon_client.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("telethon_client")

class TelethonClient:
    def __init__(self, account, session_dir="../sessions/"):
        """Initialize the Telethon client"""
        self.phone = account['phone']
        self.api_id = account['api_id']
        self.api_hash = account['api_hash']
        self.account_id = account['id']
        self.session_dir = session_dir
        self.client = None
        self.connected = False
        
        # Ensure session directory exists
        os.makedirs(session_dir, exist_ok=True)
        
        # Session file path
        self.session_file = os.path.join(session_dir, f"{self.phone}")
        
        logger.info(f"Initialized TelethonClient for account: {self.phone}")
    
    async def connect(self):
        """Connect to Telegram"""
        try:
            logger.info(f"Connecting client for {self.phone}")
            self.client = TelegramClient(self.session_file, self.api_id, self.api_hash)
            await self.client.connect()
            
            # Check if authorized
            if not await self.client.is_user_authorized():
                logger.warning(f"Account {self.phone} is not authorized. Auth flow needed.")
                return False
            
            self.connected = True
            logger.info(f"Successfully connected account: {self.phone}")
            return True
        except Exception as e:
            logger.error(f"Failed to connect account {self.phone}: {str(e)}")
            return False
    
    async def authorize(self, phone_code_callback=None):
        """Authorize the client with a phone code"""
        if not self.client:
            await self.connect()
        
        try:
            # Send code request
            await self.client.send_code_request(self.phone)
            
            # Get phone code from callback
            if phone_code_callback:
                phone_code = await phone_code_callback(self.phone)
            else:
                phone_code = input(f"Enter the code received on {self.phone}: ")
            
            # Sign in
            await self.client.sign_in(self.phone, phone_code)
            
            # Check if further 2FA is needed (password)
            if await self.client.is_user_authorized():
                self.connected = True
                logger.info(f"Successfully authorized account: {self.phone}")
                return True
            else:
                logger.warning(f"Authorization failed for account: {self.phone}")
                return False
        except errors.SessionPasswordNeededError:
            # 2FA is enabled, need password
            logger.warning(f"2FA required for account: {self.phone}")
            return False
        except Exception as e:
            logger.error(f"Failed to authorize account {self.phone}: {str(e)}")
            return False
    
    async def authorize_2fa(self, password):
        """Complete 2FA authorization with password"""
        if not self.client:
            await self.connect()
        
        try:
            await self.client.sign_in(password=password)
            self.connected = True
            logger.info(f"Successfully authorized account with 2FA: {self.phone}")
            return True
        except Exception as e:
            logger.error(f"Failed to authorize account with 2FA {self.phone}: {str(e)}")
            return False
    
    async def disconnect(self):
        """Disconnect from Telegram"""
        if self.client:
            await self.client.disconnect()
            self.connected = False
            logger.info(f"Disconnected account: {self.phone}")
    
    async def get_entity(self, username_or_id):
        """Get a Telegram entity by username or ID"""
        if not self.connected:
            await self.connect()
        
        try:
            entity = await self.client.get_entity(username_or_id)
            return entity
        except Exception as e:
            logger.error(f"Failed to get entity {username_or_id}: {str(e)}")
            return None
    
    async def get_participants(self, channel, limit=100, search=''):
        """Get participants from a channel"""
        if not self.connected:
            await self.connect()
        
        try:
            entity = await self.get_entity(channel)
            if not entity:
                return []
            
            offset = 0
            all_participants = []
            
            while True:
                participants = await self.client(GetParticipantsRequest(
                    channel=entity,
                    filter=ChannelParticipantsSearch(search),
                    offset=offset,
                    limit=100,
                    hash=0
                ))
                
                if not participants.users:
                    break
                
                all_participants.extend(participants.users)
                offset += len(participants.users)
                
                # Stop if we've reached the limit or exhausted participants
                if limit and len(all_participants) >= limit:
                    all_participants = all_participants[:limit]
                    break
                
                if len(participants.users) < 100:
                    break
                
                # Rate limiting - sleep between requests
                await asyncio.sleep(2)
            
            return all_participants
        except errors.FloodWaitError as e:
            cooldown_time = e.seconds
            logger.warning(f"FloodWaitError: Must wait {cooldown_time} seconds for account {self.phone}")
            raise
        except Exception as e:
            logger.error(f"Failed to get participants from {channel}: {str(e)}")
            return []
    
    async def add_users_to_channel(self, channel, users):
        """Add users to a channel"""
        if not self.connected:
            await self.connect()
        
        try:
            entity = await self.get_entity(channel)
            if not entity:
                return False, "Channel not found"
            
            # Process users in chunks to avoid hitting limits
            chunk_size = 10
            results = {"success": 0, "failed": 0, "errors": []}
            
            for i in range(0, len(users), chunk_size):
                chunk = users[i:i+chunk_size]
                
                try:
                    user_entities = []
                    for user in chunk:
                        try:
                            user_entity = await self.get_entity(user)
                            if user_entity:
                                user_entities.append(InputPeerUser(
                                    user_id=user_entity.id,
                                    access_hash=user_entity.access_hash
                                ))
                        except Exception as e:
                            results["failed"] += 1
                            results["errors"].append(f"Failed to get entity for {user}: {str(e)}")
                    
                    if user_entities:
                        await self.client(InviteToChannelRequest(
                            channel=entity,
                            users=user_entities
                        ))
                        results["success"] += len(user_entities)
                        
                    # Rate limiting - sleep between chunks
                    await asyncio.sleep(30)
                except errors.FloodWaitError as e:
                    cooldown_time = e.seconds
                    logger.warning(f"FloodWaitError: Must wait {cooldown_time} seconds for account {self.phone}")
                    raise
                except Exception as e:
                    results["failed"] += len(chunk)
                    results["errors"].append(str(e))
            
            return True, results
        except errors.FloodWaitError as e:
            cooldown_time = e.seconds
            logger.warning(f"FloodWaitError: Must wait {cooldown_time} seconds for account {self.phone}")
            raise
        except Exception as e:
            logger.error(f"Failed to add users to {channel}: {str(e)}")
            return False, str(e)
    
    async def get_dialogs(self, limit=100):
        """Get the user's dialogs (chats and channels)"""
        if not self.connected:
            await self.connect()
        
        try:
            dialogs = await self.client.get_dialogs(limit=limit)
            return dialogs
        except Exception as e:
            logger.error(f"Failed to get dialogs: {str(e)}")
            return []
    
    def handle_error(self, error):
        """Handle common Telegram errors and determine appropriate action"""
        if isinstance(error, errors.FloodWaitError):
            cooldown_time = error.seconds
            logger.warning(f"FloodWaitError: Must wait {cooldown_time} seconds for account {self.phone}")
            return "restricted", cooldown_time
        
        if isinstance(error, errors.PhoneNumberBannedError):
            logger.error(f"Account {self.phone} is banned")
            return "banned", None
        
        if isinstance(error, (errors.AuthKeyUnregisteredError, errors.SessionExpiredError, errors.SessionRevokedError)):
            logger.error(f"Session expired for account {self.phone}")
            # Delete the session file to force re-authentication
            if os.path.exists(self.session_file):
                os.remove(self.session_file)
            return "active", None  # Mark as active but needs re-auth
        
        # For other errors, keep the account active but log the error
        logger.error(f"Error with account {self.phone}: {str(error)}")
        return "active", None
