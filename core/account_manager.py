import time
import logging
import random
from datetime import datetime, timedelta
from .database import DB

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("../logs/account_manager.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("account_manager")

class NoUsableAccountsError(Exception):
    """Exception raised when no usable accounts are available"""
    pass

class AccountRotator:
    def __init__(self):
        """Initialize the account rotator"""
        self.db = DB.get_instance()
        self.accounts = self.load_accounts()
        self.cooldown = 24 * 3600  # 24 hours default cooldown
        logger.info(f"Account rotator initialized with {len(self.accounts)} accounts")
    
    def load_accounts(self):
        """Load accounts from database"""
        try:
            accounts = self.db.query("SELECT * FROM accounts ORDER BY last_used ASC")
            logger.info(f"Loaded {len(accounts)} accounts from database")
            return accounts
        except Exception as e:
            logger.error(f"Failed to load accounts: {str(e)}")
            return []
    
    def reload_accounts(self):
        """Reload accounts from database"""
        self.accounts = self.load_accounts()
        logger.info(f"Reloaded {len(self.accounts)} accounts from database")
    
    def get_next_account(self):
        """Get the next available account based on priority and cooldown"""
        self.reload_accounts()  # Always reload to get fresh status
        
        # Prioritize unrestricted accounts
        active_accounts = [acc for acc in self.accounts if acc['status'] == 'active']
        if active_accounts:
            # Pick a random account from the least recently used 50% of active accounts
            # This helps distribute load while maintaining some randomness
            sorted_active = sorted(active_accounts, key=lambda x: x['last_used'])
            selection_pool = sorted_active[:max(1, len(sorted_active) // 2)]
            account = random.choice(selection_pool)
            logger.info(f"Selected active account: {account['phone']}")
            return account
        
        # If no active accounts, check for cooled-down restricted accounts
        now = time.time()
        current_time = datetime.now()
        
        for account in self.accounts:
            if account['status'] == 'restricted':
                cooldown_until = account.get('cooldown_until')
                
                if cooldown_until:
                    # If cooldown_until is specified, use it
                    cooldown_time = datetime.strptime(str(cooldown_until), '%Y-%m-%d %H:%M:%S')
                    if current_time >= cooldown_time:
                        logger.info(f"Selected cooled-down account: {account['phone']}")
                        return account
                else:
                    # Otherwise use default cooldown period
                    last_used = datetime.strptime(str(account['last_used']), '%Y-%m-%d %H:%M:%S')
                    if current_time >= last_used + timedelta(seconds=self.cooldown):
                        logger.info(f"Selected cooled-down account: {account['phone']}")
                        return account
        
        logger.error("No usable accounts available")
        raise NoUsableAccountsError("No available accounts")
    
    def mark_account_status(self, account_id, status, cooldown_hours=None):
        """Mark an account with a specific status and optional cooldown period"""
        cooldown_until = None
        if cooldown_hours and status == 'restricted':
            cooldown_until = (datetime.now() + timedelta(hours=cooldown_hours)).strftime('%Y-%m-%d %H:%M:%S')
            logger.info(f"Setting account {account_id} as {status} with cooldown until {cooldown_until}")
        else:
            logger.info(f"Setting account {account_id} as {status}")
            
        self.db.update_account_status(account_id, status, cooldown_until)
    
    def add_account(self, phone, api_id, api_hash):
        """Add a new account to the system"""
        try:
            data = {
                'phone': phone,
                'api_id': api_id,
                'api_hash': api_hash,
                'status': 'active',
                'last_used': time.strftime('%Y-%m-%d %H:%M:%S')
            }
            
            account_id = self.db.insert('accounts', data)
            logger.info(f"Added new account: {phone}")
            self.reload_accounts()
            return account_id
        except Exception as e:
            logger.error(f"Failed to add account: {str(e)}")
            raise
    
    def get_account_by_phone(self, phone):
        """Get an account by phone number"""
        return self.db.get_one("SELECT * FROM accounts WHERE phone = %s", (phone,))
    
    def delete_account(self, account_id):
        """Delete an account from the system"""
        try:
            self.db.execute("DELETE FROM accounts WHERE id = %s", (account_id,))
            logger.info(f"Deleted account: {account_id}")
            self.reload_accounts()
            return True
        except Exception as e:
            logger.error(f"Failed to delete account: {str(e)}")
            return False
