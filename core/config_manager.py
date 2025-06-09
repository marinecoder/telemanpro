"""
Configuration management module for the Telegram Member Manager.

This module handles loading, validating, and saving configuration settings
from the config.ini file.
"""

import configparser
import os
import json
from typing import Dict, Any, Optional
from core.logger import setup_logger, logger

# Default configuration values
DEFAULT_CONFIG = {
    'general': {
        'app_name': 'Telegram Member Manager',
        'timezone': 'UTC',
        'log_level': 'INFO',
        'log_retention': '30',
        'enable_notifications': 'true',
        'notification_email': ''
    },
    'telegram': {
        'api_layer': 'mtproto',
        'session_type': 'string',
        'connection_retries': '3',
        'connection_timeout': '30',
        'use_ipv6': 'false',
        'flood_wait_auto_retry': 'true'
    },
    'scraper': {
        'max_batch_size': '1000',
        'batch_delay': '10',
        'account_cooldown': '30',
        'mode': 'safe',
        'scrape_user_details': 'false',
        'filter_active_users': 'true'
    },
    'adder': {
        'max_adds_per_day': '40',
        'add_delay': '60',
        'account_cooldown': '24',
        'max_errors_before_switch': '5',
        'stop_on_too_many_flood_waits': 'true',
        'smart_mode': 'true'
    },
    'proxy': {
        'enabled': 'false',
        'type': 'socks5',
        'rotation': 'round_robin',
        'list': '',
        'test_before_use': 'true'
    },
    'security': {
        'session_encryption': 'none',
        'require_2fa': 'none',
        'password_policy': 'medium',
        'session_timeout': '60',
        'ip_whitelist_enabled': 'false',
        'ip_whitelist': ''
    },
    'backup': {
        'auto_backup_enabled': 'true',
        'frequency': 'weekly',
        'retention': '5',
        'path': '../backups',
        'encrypt': 'true'
    },
    'database': {
        'host': 'localhost',
        'name': 'telegram_manager',
        'user': 'telemanpro',
        'password': '',
        'port': '3306'
    }
}

class Config:
    """Configuration manager for the application."""
    
    def __init__(self, config_path: str = None):
        """
        Initialize the configuration manager.
        
        Args:
            config_path (str): Path to the config.ini file
        """
        self.config_path = config_path or os.path.join(os.path.dirname(os.path.dirname(__file__)), 'config', 'config.ini')
        self.config = configparser.ConfigParser()
        self.load()
    
    def load(self) -> None:
        """Load configuration from file."""
        # Create config with default values
        for section, options in DEFAULT_CONFIG.items():
            if not self.config.has_section(section):
                self.config.add_section(section)
            for option, value in options.items():
                if not self.config.has_option(section, option):
                    self.config.set(section, option, value)
        
        # Try to load configuration from file
        if os.path.exists(self.config_path):
            try:
                self.config.read(self.config_path)
                if logger:
                    logger.info(f"Configuration loaded from {self.config_path}")
                else:
                    print(f"Configuration loaded from {self.config_path}")
            except Exception as e:
                if logger:
                    logger.error(f"Error loading configuration: {str(e)}")
                else:
                    print(f"Error loading configuration: {str(e)}")
                    setup_logger()
                    logger.error(f"Error loading configuration: {str(e)}")
        else:
            # If config file doesn't exist, create it with default values
            try:
                os.makedirs(os.path.dirname(self.config_path), exist_ok=True)
                with open(self.config_path, 'w') as configfile:
                    self.config.write(configfile)
                if logger:
                    logger.info(f"Created default configuration at {self.config_path}")
                else:
                    print(f"Created default configuration at {self.config_path}")
            except Exception as e:
                if logger:
                    logger.error(f"Error creating default configuration: {str(e)}")
                else:
                    print(f"Error creating default configuration: {str(e)}")
                    setup_logger()
                    logger.error(f"Error creating default configuration: {str(e)}")
    
    def save(self) -> None:
        """Save configuration to file."""
        try:
            with open(self.config_path, 'w') as configfile:
                self.config.write(configfile)
            logger.info(f"Configuration saved to {self.config_path}")
        except Exception as e:
            logger.error(f"Error saving configuration: {str(e)}")
    
    def get(self, section: str, option: str, fallback: Any = None) -> str:
        """
        Get a configuration value.
        
        Args:
            section (str): Configuration section
            option (str): Configuration option
            fallback (Any): Fallback value if the option doesn't exist
            
        Returns:
            str: Configuration value
        """
        return self.config.get(section, option, fallback=fallback)
    
    def getboolean(self, section: str, option: str, fallback: bool = None) -> bool:
        """
        Get a boolean configuration value.
        
        Args:
            section (str): Configuration section
            option (str): Configuration option
            fallback (bool): Fallback value if the option doesn't exist
            
        Returns:
            bool: Configuration value as boolean
        """
        return self.config.getboolean(section, option, fallback=fallback)
    
    def getint(self, section: str, option: str, fallback: int = None) -> int:
        """
        Get an integer configuration value.
        
        Args:
            section (str): Configuration section
            option (str): Configuration option
            fallback (int): Fallback value if the option doesn't exist
            
        Returns:
            int: Configuration value as integer
        """
        return self.config.getint(section, option, fallback=fallback)
    
    def getfloat(self, section: str, option: str, fallback: float = None) -> float:
        """
        Get a float configuration value.
        
        Args:
            section (str): Configuration section
            option (str): Configuration option
            fallback (float): Fallback value if the option doesn't exist
            
        Returns:
            float: Configuration value as float
        """
        return self.config.getfloat(section, option, fallback=fallback)
    
    def set(self, section: str, option: str, value: Any) -> None:
        """
        Set a configuration value.
        
        Args:
            section (str): Configuration section
            option (str): Configuration option
            value (Any): Value to set
        """
        if not self.config.has_section(section):
            self.config.add_section(section)
        self.config.set(section, option, str(value))
    
    def get_section(self, section: str) -> Dict[str, str]:
        """
        Get all options in a section.
        
        Args:
            section (str): Configuration section
            
        Returns:
            Dict[str, str]: Dictionary of options and values
        """
        if self.config.has_section(section):
            return dict(self.config.items(section))
        return {}
    
    def get_all(self) -> Dict[str, Dict[str, str]]:
        """
        Get the entire configuration.
        
        Returns:
            Dict[str, Dict[str, str]]: Dictionary of all sections and options
        """
        result = {}
        for section in self.config.sections():
            result[section] = dict(self.config.items(section))
        return result
    
    def update_section(self, section: str, values: Dict[str, Any]) -> None:
        """
        Update an entire section of configuration.
        
        Args:
            section (str): Configuration section
            values (Dict[str, Any]): Dictionary of option-value pairs
        """
        if not self.config.has_section(section):
            self.config.add_section(section)
        
        for option, value in values.items():
            self.config.set(section, option, str(value))
    
    def import_json(self, json_data: str) -> None:
        """
        Import configuration from JSON string.
        
        Args:
            json_data (str): JSON configuration data
        """
        try:
            data = json.loads(json_data)
            for section, options in data.items():
                if not self.config.has_section(section):
                    self.config.add_section(section)
                for option, value in options.items():
                    self.config.set(section, option, str(value))
            logger.info("Configuration imported from JSON")
        except Exception as e:
            logger.error(f"Error importing configuration from JSON: {str(e)}")
    
    def export_json(self) -> str:
        """
        Export configuration as JSON string.
        
        Returns:
            str: JSON configuration data
        """
        try:
            data = {}
            for section in self.config.sections():
                data[section] = dict(self.config.items(section))
            return json.dumps(data, indent=2)
        except Exception as e:
            logger.error(f"Error exporting configuration to JSON: {str(e)}")
            return "{}"
    
    def reset_to_defaults(self) -> None:
        """Reset configuration to default values."""
        self.config = configparser.ConfigParser()
        for section, options in DEFAULT_CONFIG.items():
            if not self.config.has_section(section):
                self.config.add_section(section)
            for option, value in options.items():
                self.config.set(section, option, value)
        logger.info("Configuration reset to defaults")

# Create a global configuration instance
config = Config()

# Use this function to get the configuration instance
def get_config() -> Config:
    """
    Get the global configuration instance.
    
    Returns:
        Config: Configuration instance
    """
    global config
    return config
