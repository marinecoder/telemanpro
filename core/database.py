import pymysql
import time
import os
from dotenv import load_dotenv
import logging

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("../logs/database.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("database")

# Load environment variables
load_dotenv('../config/config.ini')

class DB:
    _instance = None
    _connection = None
    
    @classmethod
    def get_instance(cls):
        """Singleton pattern to ensure only one DB instance exists"""
        if cls._instance is None:
            cls._instance = DB()
        return cls._instance
    
    def __init__(self):
        """Initialize the database connection"""
        if DB._instance is not None:
            raise Exception("This class is a singleton! Use get_instance() method.")
        else:
            DB._instance = self
            self.connect()
    
    def connect(self):
        """Establish connection to MySQL database"""
        try:
            self._connection = pymysql.connect(
                host=os.getenv('DB_HOST', 'localhost'),
                user=os.getenv('DB_USER', 'root'),
                password=os.getenv('DB_PASSWORD', ''),
                database=os.getenv('DB_NAME', 'telegram_manager'),
                charset='utf8mb4',
                cursorclass=pymysql.cursors.DictCursor
            )
            logger.info("Database connection established")
        except Exception as e:
            logger.error(f"Database connection failed: {str(e)}")
            raise
    
    def _ensure_connection(self):
        """Ensure the connection is active"""
        try:
            self._connection.ping(reconnect=True)
        except Exception:
            logger.info("Reconnecting to database...")
            self.connect()
    
    def query(self, sql, params=None):
        """Execute a query and return all results"""
        self._ensure_connection()
        with self._connection.cursor() as cursor:
            cursor.execute(sql, params or ())
            result = cursor.fetchall()
        return result
    
    def execute(self, sql, params=None):
        """Execute a query without returning results"""
        self._ensure_connection()
        with self._connection.cursor() as cursor:
            affected_rows = cursor.execute(sql, params or ())
            self._connection.commit()
        return affected_rows
    
    def get_one(self, sql, params=None):
        """Execute a query and return one result"""
        self._ensure_connection()
        with self._connection.cursor() as cursor:
            cursor.execute(sql, params or ())
            result = cursor.fetchone()
        return result
    
    def insert(self, table, data):
        """Insert data into a table and return the ID"""
        columns = ', '.join(data.keys())
        placeholders = ', '.join(['%s'] * len(data))
        sql = f"INSERT INTO {table} ({columns}) VALUES ({placeholders})"
        
        self._ensure_connection()
        with self._connection.cursor() as cursor:
            cursor.execute(sql, tuple(data.values()))
            self._connection.commit()
            return cursor.lastrowid
    
    def update(self, table, data, condition):
        """Update data in a table"""
        set_clause = ', '.join([f"{key} = %s" for key in data.keys()])
        where_clause = ' AND '.join([f"{key} = %s" for key in condition.keys()])
        sql = f"UPDATE {table} SET {set_clause} WHERE {where_clause}"
        
        params = list(data.values()) + list(condition.values())
        
        self._ensure_connection()
        with self._connection.cursor() as cursor:
            affected_rows = cursor.execute(sql, params)
            self._connection.commit()
            return affected_rows
    
    # Specific methods for application entities
    
    def get_active_accounts(self):
        """Get all active accounts"""
        return self.query("SELECT * FROM accounts WHERE status = 'active' ORDER BY last_used ASC")
    
    def get_account(self, account_id):
        """Get account by ID"""
        return self.get_one("SELECT * FROM accounts WHERE id = %s", (account_id,))
    
    def update_account_status(self, account_id, status, cooldown_until=None):
        """Update account status and cooldown"""
        data = {
            'status': status,
            'last_used': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        
        if cooldown_until:
            data['cooldown_until'] = cooldown_until
            
        return self.update('accounts', data, {'id': account_id})
    
    def create_operation(self, user_id, operation_type, target):
        """Create a new operation"""
        data = {
            'user_id': user_id,
            'type': operation_type,
            'target': target,
            'status': 'pending',
            'progress': 0,
            'started_at': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        
        return self.insert('operations', data)
    
    def update_operation_progress(self, operation_id, progress, status=None):
        """Update operation progress"""
        data = {'progress': progress}
        
        if status:
            data['status'] = status
            
        if status == 'completed':
            data['completed_at'] = time.strftime('%Y-%m-%d %H:%M:%S')
            
        return self.update('operations', data, {'id': operation_id})
    
    def get_operation_status(self, operation_id):
        """Get operation status"""
        return self.get_one("SELECT * FROM operations WHERE id = %s", (operation_id,))
    
    def log_action(self, operation_id, account_id, action, status, details=None):
        """Log an action"""
        data = {
            'operation_id': operation_id,
            'account_id': account_id,
            'action': action,
            'status': status,
            'details': details,
            'created_at': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        
        return self.insert('logs', data)
