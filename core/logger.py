"""
Error handling and logging module for the Telegram Member Manager.

This module provides centralized error handling and logging functions
for all components of the system.
"""

import logging
import os
import sys
import traceback
from datetime import datetime
from logging.handlers import RotatingFileHandler

# Configure logger
logger = None
LOG_LEVELS = {
    'DEBUG': logging.DEBUG,
    'INFO': logging.INFO,
    'WARNING': logging.WARNING,
    'ERROR': logging.ERROR,
    'CRITICAL': logging.CRITICAL
}

def setup_logger(log_level='INFO', log_file=None, max_size=10485760, backup_count=5):
    """
    Set up the logger for the application.
    
    Args:
        log_level (str): The log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
        log_file (str): The path to the log file (default: None, logs to console)
        max_size (int): Maximum size of log file in bytes before rotating (default: 10MB)
        backup_count (int): Number of backup log files to keep (default: 5)
    
    Returns:
        logging.Logger: The configured logger instance
    """
    global logger
    
    # Create logger
    logger = logging.getLogger('telemanpro')
    logger.setLevel(LOG_LEVELS.get(log_level.upper(), logging.INFO))
    
    # Create formatter
    formatter = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    
    # Clear existing handlers
    if logger.hasHandlers():
        logger.handlers.clear()
    
    # Add file handler if log file is specified
    if log_file:
        # Ensure log directory exists
        log_dir = os.path.dirname(log_file)
        if log_dir and not os.path.exists(log_dir):
            os.makedirs(log_dir)
        
        file_handler = RotatingFileHandler(
            log_file,
            maxBytes=max_size,
            backupCount=backup_count
        )
        file_handler.setFormatter(formatter)
        logger.addHandler(file_handler)
    
    # Add console handler
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)
    
    return logger

def log_exception(e, context=None):
    """
    Log an exception with traceback and context information.
    
    Args:
        e (Exception): The exception to log
        context (str): Additional context information
    """
    if logger is None:
        setup_logger()
    
    error_msg = f"Exception: {type(e).__name__}: {str(e)}"
    if context:
        error_msg = f"{context} - {error_msg}"
    
    logger.error(error_msg)
    logger.debug(traceback.format_exc())

def log_operation(operation_id, action, status, details=None, account_id=None, db_conn=None):
    """
    Log an operation to both the log file and the database.
    
    Args:
        operation_id (int): The ID of the operation
        action (str): The action being performed
        status (str): The status of the action (success, warning, error)
        details (str): Additional details about the action
        account_id (int): The ID of the account performing the action
        db_conn: Database connection object
    """
    if logger is None:
        setup_logger()
    
    # Log to file
    log_message = f"Operation #{operation_id} - {action} - {status}"
    if account_id:
        log_message += f" (Account: {account_id})"
    if details:
        log_message += f": {details}"
    
    if status == 'error':
        logger.error(log_message)
    elif status == 'warning':
        logger.warning(log_message)
    else:
        logger.info(log_message)
    
    # Log to database if connection is provided
    if db_conn:
        try:
            cursor = db_conn.cursor()
            query = """
                INSERT INTO logs (operation_id, account_id, action, status, details) 
                VALUES (%s, %s, %s, %s, %s)
            """
            cursor.execute(query, (operation_id, account_id, action, status, details))
            db_conn.commit()
            cursor.close()
        except Exception as e:
            logger.error(f"Failed to log to database: {str(e)}")

def log_api_request(request, status_code, response_data=None):
    """
    Log an API request and response.
    
    Args:
        request: The Flask request object
        status_code (int): The HTTP status code of the response
        response_data (dict): The response data
    """
    if logger is None:
        setup_logger()
    
    # Get request information
    method = request.method
    path = request.path
    remote_addr = request.remote_addr
    user_agent = request.headers.get('User-Agent', 'Unknown')
    
    # Create log message
    log_message = f"API {method} {path} from {remote_addr} - Status: {status_code}"
    
    # Determine log level based on status code
    if status_code >= 500:
        logger.error(log_message)
        if response_data:
            logger.error(f"Response: {response_data}")
    elif status_code >= 400:
        logger.warning(log_message)
        if response_data:
            logger.warning(f"Response: {response_data}")
    else:
        logger.info(log_message)
        if response_data and logger.level <= logging.DEBUG:
            logger.debug(f"Response: {response_data}")

class TelegramManagerException(Exception):
    """Base exception for Telegram Manager errors."""
    def __init__(self, message, code=None):
        self.message = message
        self.code = code
        super().__init__(self.message)

class AccountException(TelegramManagerException):
    """Exception raised for errors in account management."""
    pass

class ScraperException(TelegramManagerException):
    """Exception raised for errors in the scraper module."""
    pass

class AdderException(TelegramManagerException):
    """Exception raised for errors in the adder module."""
    pass

class DatabaseException(TelegramManagerException):
    """Exception raised for errors in database operations."""
    pass

class APIException(TelegramManagerException):
    """Exception raised for errors in API operations."""
    pass
