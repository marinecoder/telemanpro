#!/usr/bin/env python3
import argparse
import asyncio
import os
import sys
import logging
import time
from datetime import datetime

# Add parent directory to path for imports
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from core.database import DB
from core.account_manager import AccountRotator, NoUsableAccountsError
from core.scraper import Scraper
from core.adder import Adder
from core.telethon_client import TelethonClient

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("../logs/cli.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("cli")

class TelegramManagerCLI:
    def __init__(self):
        """Initialize the CLI manager"""
        self.db = DB.get_instance()
        self.account_rotator = AccountRotator()
        self.scraper = Scraper()
        self.adder = Adder()
        
        # Current active operation
        self.active_operation = None
        self.operation_task = None
    
    async def scrape_command(self, args):
        """Handle scrape command"""
        if not args.source or not args.output:
            logger.error("Source channel and output file are required")
            return
        
        source = args.source
        output_file = args.output
        limit = args.limit if args.limit else 0
        
        print(f"Starting scrape operation for {source}")
        print(f"Results will be saved to {output_file}")
        print(f"Limit: {'No limit' if limit == 0 else limit} members")
        
        # Default user ID for CLI operations
        user_id = 1
        
        # Start scraping in a background task
        self.active_operation = 'scrape'
        self.operation_task = asyncio.create_task(
            self.scraper.scrape_channel(source, output_file, user_id, limit)
        )
        
        try:
            # Wait for the operation to complete
            success, message = await self.operation_task
            
            if success:
                print(f"✅ Scraping completed: {message}")
            else:
                print(f"❌ Scraping failed: {message}")
        except KeyboardInterrupt:
            print("\nScraping operation interrupted by user")
            self.scraper.stop_scraping()
            await self.operation_task
            print("Scraping operation stopped")
        except Exception as e:
            logger.error(f"Error in scrape operation: {str(e)}")
            print(f"❌ Error: {str(e)}")
        finally:
            self.active_operation = None
            self.operation_task = None
    
    async def add_command(self, args):
        """Handle add command"""
        if not args.target or not args.members:
            logger.error("Target channel and members file are required")
            return
        
        target = args.target
        members_file = args.members
        
        if not os.path.exists(members_file):
            logger.error(f"Members file not found: {members_file}")
            print(f"❌ Error: Members file not found: {members_file}")
            return
        
        print(f"Starting add operation for {target}")
        print(f"Members will be added from {members_file}")
        
        # Default user ID for CLI operations
        user_id = 1
        
        # Start adding in a background task
        self.active_operation = 'add'
        self.operation_task = asyncio.create_task(
            self.adder.add_members(target, members_file, user_id)
        )
        
        try:
            # Wait for the operation to complete
            success, message = await self.operation_task
            
            if success:
                print(f"✅ Adding completed: {message}")
            else:
                print(f"❌ Adding failed: {message}")
        except KeyboardInterrupt:
            print("\nAdding operation interrupted by user")
            self.adder.stop_adding()
            await self.operation_task
            print("Adding operation stopped")
        except Exception as e:
            logger.error(f"Error in add operation: {str(e)}")
            print(f"❌ Error: {str(e)}")
        finally:
            self.active_operation = None
            self.operation_task = None
    
    async def accounts_command(self, args):
        """Handle accounts command"""
        action = args.action if hasattr(args, 'action') else 'list'
        
        if action == 'list':
            # List all accounts
            accounts = self.account_rotator.load_accounts()
            
            if not accounts:
                print("No accounts found")
                return
            
            print(f"\n{'ID':<4} {'Phone':<15} {'Status':<12} {'Last Used':<20} {'Cooldown Until':<20}")
            print("-" * 75)
            
            for account in accounts:
                print(f"{account['id']:<4} {account['phone']:<15} {account['status']:<12} "
                      f"{account['last_used']:<20} {account.get('cooldown_until', 'N/A'):<20}")
            
            print(f"\nTotal accounts: {len(accounts)}")
            
        elif action == 'add':
            # Add a new account
            if not args.phone or not args.api_id or not args.api_hash:
                print("Phone, API ID, and API hash are required")
                return
            
            try:
                # Check if account already exists
                existing = self.account_rotator.get_account_by_phone(args.phone)
                if existing:
                    print(f"Account with phone {args.phone} already exists")
                    return
                
                # Add the account
                account_id = self.account_rotator.add_account(args.phone, args.api_id, args.api_hash)
                
                # Create TelethonClient to test and authorize the account
                account = self.db.get_account(account_id)
                client = TelethonClient(account)
                
                # Connect to the account
                connected = await client.connect()
                
                if not connected:
                    print("Account added but not authorized. Starting authorization...")
                    
                    # Try to authorize
                    authorized = await client.authorize()
                    
                    if authorized:
                        print("✅ Account successfully authorized")
                    else:
                        print("❌ Authorization failed. You may need to authorize later")
                else:
                    print("✅ Account successfully added and authorized")
                
                # Disconnect
                await client.disconnect()
                
            except Exception as e:
                logger.error(f"Error adding account: {str(e)}")
                print(f"❌ Error: {str(e)}")
                
        elif action == 'delete':
            # Delete an account
            if not args.id:
                print("Account ID is required")
                return
            
            try:
                # Check if account exists
                account = self.db.get_account(args.id)
                if not account:
                    print(f"Account with ID {args.id} not found")
                    return
                
                # Delete the account
                deleted = self.account_rotator.delete_account(args.id)
                
                if deleted:
                    print(f"✅ Account {args.id} ({account['phone']}) deleted")
                else:
                    print(f"❌ Failed to delete account {args.id}")
                
            except Exception as e:
                logger.error(f"Error deleting account: {str(e)}")
                print(f"❌ Error: {str(e)}")
    
    async def serve_command(self, args):
        """Start the web UI"""
        print("Starting web UI...")
        
        # Determine the port to use
        port = args.port if hasattr(args, 'port') and args.port else 8000
        
        # Start Flask API server
        import subprocess
        
        try:
            # Start Flask API in background
            api_process = subprocess.Popen(
                ["python", "../web/api/app.py", "--port", str(port + 1)],
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE
            )
            
            print(f"API server started on port {port + 1}")
            
            # Start PHP server
            php_process = subprocess.Popen(
                ["php", "-S", f"0.0.0.0:{port}", "-t", "../web"],
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE
            )
            
            print(f"Web UI started on port {port}")
            print(f"Access the web interface at http://localhost:{port}")
            print("Press Ctrl+C to stop the servers")
            
            # Keep the process running until interrupted
            while True:
                await asyncio.sleep(1)
                
        except KeyboardInterrupt:
            print("\nStopping servers...")
            api_process.terminate()
            php_process.terminate()
            print("Servers stopped")
        except Exception as e:
            logger.error(f"Error starting servers: {str(e)}")
            print(f"❌ Error: {str(e)}")

def main():
    """Main entry point for the CLI"""
    parser = argparse.ArgumentParser(description='Telegram Member Manager CLI')
    subparsers = parser.add_subparsers(dest='command', help='Command to run')
    
    # Scrape command
    scrape_parser = subparsers.add_parser('scrape', help='Scrape members from a channel')
    scrape_parser.add_argument('source', help='Source channel (e.g., @channelname)')
    scrape_parser.add_argument('output', help='Output CSV file')
    scrape_parser.add_argument('--limit', type=int, help='Maximum number of members to scrape')
    
    # Add command
    add_parser = subparsers.add_parser('add', help='Add members to a channel')
    add_parser.add_argument('target', help='Target channel (e.g., @channelname)')
    add_parser.add_argument('members', help='CSV file with members to add')
    
    # Accounts command
    accounts_parser = subparsers.add_parser('accounts', help='Manage accounts')
    accounts_subparsers = accounts_parser.add_subparsers(dest='action', help='Action to perform')
    
    # List accounts
    accounts_subparsers.add_parser('list', help='List all accounts')
    
    # Add account
    add_account_parser = accounts_subparsers.add_parser('add', help='Add a new account')
    add_account_parser.add_argument('--phone', required=True, help='Phone number with country code')
    add_account_parser.add_argument('--api-id', required=True, help='Telegram API ID')
    add_account_parser.add_argument('--api-hash', required=True, help='Telegram API hash')
    
    # Delete account
    delete_account_parser = accounts_subparsers.add_parser('delete', help='Delete an account')
    delete_account_parser.add_argument('--id', required=True, type=int, help='Account ID to delete')
    
    # Serve command
    serve_parser = subparsers.add_parser('serve', help='Start the web UI')
    serve_parser.add_argument('--port', type=int, default=8000, help='Port to run the web UI on')
    
    args = parser.parse_args()
    
    # Create CLI manager
    cli = TelegramManagerCLI()
    
    # Run the appropriate command
    if args.command == 'scrape':
        asyncio.run(cli.scrape_command(args))
    elif args.command == 'add':
        asyncio.run(cli.add_command(args))
    elif args.command == 'accounts':
        asyncio.run(cli.accounts_command(args))
    elif args.command == 'serve':
        asyncio.run(cli.serve_command(args))
    else:
        parser.print_help()

if __name__ == '__main__':
    main()
