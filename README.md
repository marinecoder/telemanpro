# Telegram Member Manager Pro (TeleManPro)

A complete solution for managing Telegram members with account rotation, scraping, and adding capabilities.

## Features

- 📱 **Responsive Web Interface**: Optimized for both desktop and mobile
- 🔄 **Account Rotation**: Avoid rate limits by rotating between multiple Telegram accounts
- 📥 **Member Scraping**: Extract members from Telegram channels and groups
- 📤 **Member Adding**: Add members to your channels with smart rate limiting
- 👥 **User Management**: Role-based access control for team collaboration
- 📊 **Analytics & Reporting**: Visualize operations and track performance
- ⚙️ **Advanced Settings**: Customize all aspects of the system
- 🔒 **Two-Factor Authentication**: Enhanced security for user accounts
- 💾 **Backup & Restore**: Protect your data with easy backup functionality
- 🛠️ **CLI Interface**: Command-line interface for power users

## System Requirements

- PHP 7.4+ with MySQL extension
- Python 3.8+
- MySQL/MariaDB 5.7+
- Web server (Apache/Nginx)
- 1GB+ RAM
- 10GB+ disk space (for large member databases)

## Installation

### Quick Start

1. Clone the repository:
```bash
git clone https://github.com/yourusername/telemanpro.git
cd telemanpro
```

2. Install Python dependencies:
```bash
pip install -r requirements.txt
```

3. Run the installation script:
```bash
chmod +x setup/install.sh
./setup/install.sh
```

4. Follow the on-screen instructions to configure your database and web server.

### Manual Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/telemanpro.git
cd telemanpro
```

2. Install Python dependencies:
```bash
pip install -r requirements.txt
```

3. Create and configure the database:
```bash
mysql -u root -p < setup/schema.sql
```

4. Configure the system:
   - Edit `config/config.ini` and update database values
   - Edit `web/includes/db_config.php` and update database values

5. Set up the web server (Apache example):
```apache
<VirtualHost *:80>
    ServerName telemanpro.local
    DocumentRoot /path/to/telemanpro/web
    
    <Directory /path/to/telemanpro/web>
        AllowOverride All
        Require all granted
    </Directory>
    
    ProxyPass /api http://localhost:5000
    ProxyPassReverse /api http://localhost:5000
</VirtualHost>
```

6. Start the API server:
```bash
cd /path/to/telemanpro
python web/api/app.py
```

## Development Environment Setup

For quick development and testing without a full web server:

1. Install Python dependencies:
```bash
pip install -r requirements.txt
```

2. Set up the database:
```bash
mysql -u root -p < setup/schema.sql
```

3. Configure database connections:
   - Edit `config/config.ini` with your database credentials
   - Edit `web/includes/db_config.php` with your database credentials

4. Start the Flask API server:
```bash
python web/api/app.py
```

5. Start a PHP development server for the web interface:
```bash
cd web
php -S localhost:8000
```

6. Access the web interface at `http://localhost:8000` and the API at `http://localhost:5000`

## Quick Start Guide

### Starting the System

1. Start the API server:
```bash
cd /path/to/telemanpro
python web/api/app.py
```

2. Access the web interface at `http://yourdomain.com` or `http://localhost:8000` if using PHP's built-in server.

3. Log in with the default admin credentials:
   - Username: `admin`
   - Password: `admin123`
   - **Important**: Change the default password immediately after first login!

### Using the CLI Interface

The CLI interface provides command-line access to all system functions:

```bash
# Get help
python cli/manager.py --help

# Add a Telegram account
python cli/manager.py account add --phone +1234567890 --api-id YOUR_API_ID --api-hash YOUR_API_HASH

# Scrape members from a channel
python cli/manager.py scrape --target https://t.me/channel_name --limit 1000

# Add members to a channel
python cli/manager.py add --target https://t.me/your_channel --source members.json --limit 50
```

## Adding Telegram Accounts

Before you can start scraping or adding members, you need to add Telegram accounts to the system:

1. Go to https://my.telegram.org/auth and log in
2. Create a new application to get your API ID and API Hash
3. In TeleManPro, go to the "Accounts" page
4. Click "Add Account" and enter:
   - Phone number (with country code)
   - API ID and API Hash from step 2
5. Follow the verification process to add the account

It's recommended to add multiple accounts for better performance and to avoid rate limits.

## Troubleshooting

### API Server Won't Start
- Check Python version (`python --version`): Must be 3.8+
- Verify all dependencies are installed: `pip install -r requirements.txt`
- Check logs in `logs/api.log` for specific errors

### Can't Connect to Database
- Verify MySQL is running: `systemctl status mysql`
- Check database credentials in `config/config.ini` and `web/includes/db_config.php`
- Test connection: `mysql -u your_user -p your_database`

### Web Interface Issues
- Check web server logs (Apache: `/var/log/apache2/error.log`, Nginx: `/var/log/nginx/error.log`)
- Verify PHP is working: `php -v`
- Ensure file permissions are correct: `chmod -R 755 /path/to/telemanpro/web`

### Telegram Operations Fail
- Check if accounts are valid and active
- Verify internet connection and proxy settings
- Look for flood wait errors in logs
- Ensure API ID and Hash are correct for each account

## Project Structure
- `/cli`: Command-line interface
- `/config`: Configuration files
- `/core`: Python core modules
- `/logs`: Log files
- `/sessions`: Telegram session files
- `/setup`: Installation and setup files
- `/utils`: Utility scripts
- `/web`: Web interface and API server

## Security Notice

This tool is for educational and legitimate marketing purposes only. Misuse of this tool may violate Telegram's Terms of Service and could result in account restrictions. Always respect privacy and obtain proper authorization before adding users to channels.