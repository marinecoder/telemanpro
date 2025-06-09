#!/usr/bin/env python3
"""
Backup utility for Telegram Member Manager.

This script creates and restores backups of the system, including the database,
configuration files, and session files.
"""

import os
import sys
import time
import shutil
import argparse
import zipfile
import json
import configparser
import mysql.connector
import hashlib
import base64
from datetime import datetime
from Crypto.Cipher import AES
from Crypto.Util.Padding import pad, unpad

# Add the parent directory to the path so we can import the core module
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from core import setup_logger, log_exception, get_config

# Set up logger
logger = setup_logger(log_file='../logs/backup.log')

def create_backup(encrypt=True, backup_dir=None, backup_name=None):
    """
    Create a backup of the system.
    
    Args:
        encrypt (bool): Whether to encrypt the backup
        backup_dir (str): Directory to store the backup
        backup_name (str): Name of the backup file
    
    Returns:
        str: Path to the created backup file
    """
    logger.info("Starting backup creation")
    
    # Get configuration
    config = get_config()
    
    # Use default backup directory if not specified
    if not backup_dir:
        backup_dir = config.get('backup', 'path', fallback='../backups')
    
    # Create backup directory if it doesn't exist
    os.makedirs(backup_dir, exist_ok=True)
    
    # Generate backup filename
    timestamp = datetime.now().strftime('%Y%m%d%H%M%S')
    if not backup_name:
        backup_name = f"telemanpro_backup_{timestamp}.zip"
    
    backup_path = os.path.join(backup_dir, backup_name)
    
    # Create temporary directory for backup files
    temp_dir = os.path.join(backup_dir, f"temp_backup_{timestamp}")
    os.makedirs(temp_dir, exist_ok=True)
    
    try:
        # 1. Backup database
        logger.info("Backing up database")
        db_backup_path = backup_database(temp_dir)
        
        # 2. Backup configuration
        logger.info("Backing up configuration")
        config_backup_path = backup_configuration(temp_dir)
        
        # 3. Backup sessions
        logger.info("Backing up session files")
        sessions_backup_path = backup_sessions(temp_dir)
        
        # 4. Create metadata file
        logger.info("Creating backup metadata")
        create_backup_metadata(temp_dir, {
            "timestamp": timestamp,
            "version": "1.0.0",
            "encrypted": encrypt,
            "components": {
                "database": os.path.basename(db_backup_path),
                "configuration": os.path.basename(config_backup_path),
                "sessions": os.path.basename(sessions_backup_path)
            }
        })
        
        # 5. Create ZIP archive
        logger.info("Creating backup archive")
        if encrypt:
            logger.info("Encrypting backup")
            create_encrypted_zip(temp_dir, backup_path)
        else:
            create_zip(temp_dir, backup_path)
        
        logger.info(f"Backup created successfully at {backup_path}")
        return backup_path
    
    except Exception as e:
        logger.error(f"Error creating backup: {str(e)}")
        raise
    finally:
        # Clean up temporary directory
        logger.info("Cleaning up temporary files")
        shutil.rmtree(temp_dir, ignore_errors=True)

def restore_backup(backup_path, decrypt=True):
    """
    Restore the system from a backup.
    
    Args:
        backup_path (str): Path to the backup file
        decrypt (bool): Whether the backup is encrypted
    
    Returns:
        bool: True if restoration was successful
    """
    logger.info(f"Starting restoration from {backup_path}")
    
    # Create temporary directory for extracted files
    temp_dir = os.path.join(os.path.dirname(backup_path), "temp_restore")
    os.makedirs(temp_dir, exist_ok=True)
    
    try:
        # Extract backup
        logger.info("Extracting backup archive")
        if decrypt:
            logger.info("Decrypting backup")
            extract_encrypted_zip(backup_path, temp_dir)
        else:
            extract_zip(backup_path, temp_dir)
        
        # Read metadata
        logger.info("Reading backup metadata")
        metadata = read_backup_metadata(temp_dir)
        
        # Restore components
        logger.info("Restoring database")
        restore_database(os.path.join(temp_dir, metadata["components"]["database"]))
        
        logger.info("Restoring configuration")
        restore_configuration(os.path.join(temp_dir, metadata["components"]["configuration"]))
        
        logger.info("Restoring sessions")
        restore_sessions(os.path.join(temp_dir, metadata["components"]["sessions"]))
        
        logger.info("Backup restored successfully")
        return True
    
    except Exception as e:
        logger.error(f"Error restoring backup: {str(e)}")
        raise
    finally:
        # Clean up temporary directory
        logger.info("Cleaning up temporary files")
        shutil.rmtree(temp_dir, ignore_errors=True)

def backup_database(temp_dir):
    """
    Backup the database to a SQL file.
    
    Args:
        temp_dir (str): Temporary directory for backup files
    
    Returns:
        str: Path to the database backup file
    """
    config = get_config()
    db_host = config.get('database', 'host', fallback='localhost')
    db_name = config.get('database', 'name', fallback='telegram_manager')
    db_user = config.get('database', 'user', fallback='telemanpro')
    db_password = config.get('database', 'password', fallback='')
    
    # Connect to the database
    conn = mysql.connector.connect(
        host=db_host,
        user=db_user,
        password=db_password,
        database=db_name
    )
    
    # Get a cursor
    cursor = conn.cursor()
    
    # Output file
    db_backup_path = os.path.join(temp_dir, "database_backup.sql")
    
    with open(db_backup_path, 'w') as f:
        # Write database creation statement
        f.write(f"-- Database: {db_name}\n")
        f.write(f"CREATE DATABASE IF NOT EXISTS `{db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n")
        f.write(f"USE `{db_name}`;\n\n")
        
        # Get all tables
        cursor.execute("SHOW TABLES")
        tables = cursor.fetchall()
        
        for table in tables:
            table_name = table[0]
            f.write(f"-- Table: {table_name}\n")
            
            # Get table creation statement
            cursor.execute(f"SHOW CREATE TABLE `{table_name}`")
            create_stmt = cursor.fetchone()[1]
            f.write(f"{create_stmt};\n\n")
            
            # Get table data
            cursor.execute(f"SELECT * FROM `{table_name}`")
            rows = cursor.fetchall()
            
            if rows:
                # Get column names
                cursor.execute(f"SHOW COLUMNS FROM `{table_name}`")
                columns = [column[0] for column in cursor.fetchall()]
                
                # Write insert statements
                f.write(f"-- Data for table: {table_name}\n")
                for row in rows:
                    values = []
                    for value in row:
                        if value is None:
                            values.append("NULL")
                        elif isinstance(value, (int, float)):
                            values.append(str(value))
                        elif isinstance(value, datetime):
                            values.append(f"'{value.strftime('%Y-%m-%d %H:%M:%S')}'")
                        elif isinstance(value, (bytes, bytearray)):
                            values.append(f"X'{value.hex()}'")
                        else:
                            # Escape single quotes
                            values.append(f"'{str(value).replace('\"', '\\\"').replace(\"'\", \"\\'\")}'")
                    
                    f.write(f"INSERT INTO `{table_name}` (`{'`, `'.join(columns)}`) VALUES ({', '.join(values)});\n")
                
                f.write("\n")
    
    # Close connections
    cursor.close()
    conn.close()
    
    return db_backup_path

def backup_configuration(temp_dir):
    """
    Backup configuration files.
    
    Args:
        temp_dir (str): Temporary directory for backup files
    
    Returns:
        str: Path to the configuration backup directory
    """
    config_dir = os.path.join(temp_dir, "config")
    os.makedirs(config_dir, exist_ok=True)
    
    # Copy config.ini
    source_config = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "config", "config.ini")
    if os.path.exists(source_config):
        shutil.copy2(source_config, os.path.join(config_dir, "config.ini"))
    
    # Copy web configuration
    source_web_config = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "web", "includes", "db_config.php")
    if os.path.exists(source_web_config):
        shutil.copy2(source_web_config, os.path.join(config_dir, "db_config.php"))
    
    return config_dir

def backup_sessions(temp_dir):
    """
    Backup session files.
    
    Args:
        temp_dir (str): Temporary directory for backup files
    
    Returns:
        str: Path to the sessions backup directory
    """
    sessions_dir = os.path.join(temp_dir, "sessions")
    os.makedirs(sessions_dir, exist_ok=True)
    
    # Copy session files
    source_sessions = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "sessions")
    if os.path.exists(source_sessions):
        for filename in os.listdir(source_sessions):
            source_file = os.path.join(source_sessions, filename)
            if os.path.isfile(source_file):
                shutil.copy2(source_file, os.path.join(sessions_dir, filename))
    
    return sessions_dir

def create_backup_metadata(temp_dir, metadata):
    """
    Create a metadata file for the backup.
    
    Args:
        temp_dir (str): Temporary directory for backup files
        metadata (dict): Metadata to write
    """
    metadata_path = os.path.join(temp_dir, "backup_metadata.json")
    with open(metadata_path, 'w') as f:
        json.dump(metadata, f, indent=2)

def read_backup_metadata(temp_dir):
    """
    Read backup metadata.
    
    Args:
        temp_dir (str): Directory containing extracted backup files
    
    Returns:
        dict: Backup metadata
    """
    metadata_path = os.path.join(temp_dir, "backup_metadata.json")
    with open(metadata_path, 'r') as f:
        return json.load(f)

def create_zip(source_dir, output_path):
    """
    Create a ZIP archive.
    
    Args:
        source_dir (str): Directory to archive
        output_path (str): Path to the output ZIP file
    """
    with zipfile.ZipFile(output_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, _, files in os.walk(source_dir):
            for file in files:
                file_path = os.path.join(root, file)
                rel_path = os.path.relpath(file_path, source_dir)
                zipf.write(file_path, rel_path)

def extract_zip(zip_path, extract_dir):
    """
    Extract a ZIP archive.
    
    Args:
        zip_path (str): Path to the ZIP file
        extract_dir (str): Directory to extract to
    """
    with zipfile.ZipFile(zip_path, 'r') as zipf:
        zipf.extractall(extract_dir)

def create_encrypted_zip(source_dir, output_path):
    """
    Create an encrypted ZIP archive.
    
    Args:
        source_dir (str): Directory to archive
        output_path (str): Path to the output ZIP file
    """
    # Create a temporary ZIP file
    temp_zip = f"{output_path}.temp"
    create_zip(source_dir, temp_zip)
    
    # Get encryption key
    key = get_encryption_key()
    
    # Encrypt the ZIP file
    with open(temp_zip, 'rb') as f_in:
        data = f_in.read()
    
    # Generate a random initialization vector
    iv = os.urandom(16)
    
    # Create cipher
    cipher = AES.new(key, AES.MODE_CBC, iv)
    
    # Pad data to be a multiple of block size
    padded_data = pad(data, AES.block_size)
    
    # Encrypt data
    encrypted_data = cipher.encrypt(padded_data)
    
    # Write encrypted data and IV to output file
    with open(output_path, 'wb') as f_out:
        f_out.write(iv)
        f_out.write(encrypted_data)
    
    # Remove temporary file
    os.remove(temp_zip)

def extract_encrypted_zip(zip_path, extract_dir):
    """
    Extract an encrypted ZIP archive.
    
    Args:
        zip_path (str): Path to the encrypted ZIP file
        extract_dir (str): Directory to extract to
    """
    # Get encryption key
    key = get_encryption_key()
    
    # Read encrypted data
    with open(zip_path, 'rb') as f:
        # Read the IV (first 16 bytes)
        iv = f.read(16)
        # Read the encrypted data
        encrypted_data = f.read()
    
    # Create cipher
    cipher = AES.new(key, AES.MODE_CBC, iv)
    
    # Decrypt data
    padded_data = cipher.decrypt(encrypted_data)
    
    # Unpad data
    data = unpad(padded_data, AES.block_size)
    
    # Write decrypted data to temporary ZIP file
    temp_zip = f"{zip_path}.temp"
    with open(temp_zip, 'wb') as f:
        f.write(data)
    
    # Extract temporary ZIP file
    extract_zip(temp_zip, extract_dir)
    
    # Remove temporary file
    os.remove(temp_zip)

def get_encryption_key():
    """
    Get encryption key from configuration or generate one.
    
    Returns:
        bytes: Encryption key
    """
    config = get_config()
    encryption_key = config.get('backup', 'encryption_key', fallback=None)
    
    if not encryption_key:
        # Generate a new key
        encryption_key = base64.b64encode(os.urandom(32)).decode('utf-8')
        config.set('backup', 'encryption_key', encryption_key)
        config.save()
    
    # Convert to bytes and hash to get a 32-byte key
    return hashlib.sha256(encryption_key.encode()).digest()

def restore_database(db_backup_path):
    """
    Restore the database from a SQL backup.
    
    Args:
        db_backup_path (str): Path to the database backup file
    """
    config = get_config()
    db_host = config.get('database', 'host', fallback='localhost')
    db_name = config.get('database', 'name', fallback='telegram_manager')
    db_user = config.get('database', 'user', fallback='telemanpro')
    db_password = config.get('database', 'password', fallback='')
    
    # Connect to the database
    conn = mysql.connector.connect(
        host=db_host,
        user=db_user,
        password=db_password
    )
    
    # Get a cursor
    cursor = conn.cursor()
    
    # Read SQL file
    with open(db_backup_path, 'r') as f:
        sql = f.read()
    
    # Split SQL by statements
    statements = sql.split(';')
    
    # Execute each statement
    for statement in statements:
        statement = statement.strip()
        if statement:
            try:
                cursor.execute(statement)
            except Exception as e:
                logger.warning(f"Error executing SQL: {str(e)}")
                logger.debug(f"Statement: {statement}")
    
    # Commit changes
    conn.commit()
    
    # Close connections
    cursor.close()
    conn.close()

def restore_configuration(config_backup_dir):
    """
    Restore configuration files.
    
    Args:
        config_backup_dir (str): Path to the configuration backup directory
    """
    # Restore config.ini
    source_config = os.path.join(config_backup_dir, "config.ini")
    if os.path.exists(source_config):
        dest_config = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "config", "config.ini")
        os.makedirs(os.path.dirname(dest_config), exist_ok=True)
        shutil.copy2(source_config, dest_config)
    
    # Restore web configuration
    source_web_config = os.path.join(config_backup_dir, "db_config.php")
    if os.path.exists(source_web_config):
        dest_web_config = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "web", "includes", "db_config.php")
        os.makedirs(os.path.dirname(dest_web_config), exist_ok=True)
        shutil.copy2(source_web_config, dest_web_config)

def restore_sessions(sessions_backup_dir):
    """
    Restore session files.
    
    Args:
        sessions_backup_dir (str): Path to the sessions backup directory
    """
    dest_sessions = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "sessions")
    os.makedirs(dest_sessions, exist_ok=True)
    
    # Copy session files
    for filename in os.listdir(sessions_backup_dir):
        source_file = os.path.join(sessions_backup_dir, filename)
        if os.path.isfile(source_file):
            shutil.copy2(source_file, os.path.join(dest_sessions, filename))

def clean_old_backups():
    """
    Clean up old backups based on retention policy.
    """
    config = get_config()
    backup_dir = config.get('backup', 'path', fallback='../backups')
    retention = config.getint('backup', 'retention', fallback=5)
    
    if not os.path.exists(backup_dir):
        return
    
    # Get list of backup files
    backup_files = []
    for filename in os.listdir(backup_dir):
        if filename.startswith("telemanpro_backup_") and filename.endswith(".zip"):
            file_path = os.path.join(backup_dir, filename)
            backup_files.append((file_path, os.path.getmtime(file_path)))
    
    # Sort by modification time (newest first)
    backup_files.sort(key=lambda x: x[1], reverse=True)
    
    # Remove old backups
    if len(backup_files) > retention:
        for file_path, _ in backup_files[retention:]:
            logger.info(f"Removing old backup: {file_path}")
            os.remove(file_path)

def main():
    """Main entry point for the script."""
    parser = argparse.ArgumentParser(description="Backup utility for Telegram Member Manager")
    subparsers = parser.add_subparsers(dest="command", help="Command to execute")
    
    # Create backup command
    create_parser = subparsers.add_parser("create", help="Create a backup")
    create_parser.add_argument("--no-encrypt", action="store_true", help="Do not encrypt the backup")
    create_parser.add_argument("--output-dir", help="Directory to store the backup")
    create_parser.add_argument("--output-name", help="Name of the backup file")
    
    # Restore backup command
    restore_parser = subparsers.add_parser("restore", help="Restore from a backup")
    restore_parser.add_argument("backup_path", help="Path to the backup file")
    restore_parser.add_argument("--no-decrypt", action="store_true", help="Do not decrypt the backup")
    
    # Clean backups command
    subparsers.add_parser("clean", help="Clean old backups based on retention policy")
    
    args = parser.parse_args()
    
    try:
        if args.command == "create":
            backup_path = create_backup(
                encrypt=not args.no_encrypt,
                backup_dir=args.output_dir,
                backup_name=args.output_name
            )
            print(f"Backup created successfully: {backup_path}")
        
        elif args.command == "restore":
            restore_backup(args.backup_path, decrypt=not args.no_decrypt)
            print("Backup restored successfully")
        
        elif args.command == "clean":
            clean_old_backups()
            print("Old backups cleaned successfully")
        
        else:
            parser.print_help()
    
    except Exception as e:
        print(f"Error: {str(e)}")
        log_exception(e, "Error in backup utility")
        return 1
    
    return 0

if __name__ == "__main__":
    sys.exit(main())
