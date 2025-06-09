#!/bin/bash
# TeleManPro Installation Script
# This script will set up and configure the Telegram Member Manager Pro system

# Display banner
echo "=================================================="
echo "  _______   _      __  __             _____       "
echo " |__   __| | |    |  \/  |           |  __ \      "
echo "    | | ___| | ___| \  / | __ _ _ __ | |__) |_ _  "
echo "    | |/ _ \ |/ _ \ |\/| |/ _\` | '_ \|  ___/ _\` | "
echo "    | |  __/ |  __/ |  | | (_| | | | | |  | (_| | "
echo "    |_|\___|_|\___|_|  |_|\__,_|_| |_|_|   \__,_| "
echo "                                                  "
echo "=================================================="
echo "      Telegram Member Manager Pro Installer       "
echo "=================================================="
echo ""

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to install dependencies
install_dependencies() {
    echo "Checking and installing dependencies..."
    
    # Check for PHP
    if ! command_exists php; then
        echo "PHP not found. Installing PHP..."
        sudo apt-get update
        sudo apt-get install -y php php-cli php-fpm php-json php-common php-mysql php-zip php-gd php-mbstring php-curl php-xml
    else
        echo "PHP is already installed."
    fi
    
    # Check for Python
    if ! command_exists python3; then
        echo "Python 3 not found. Installing Python 3..."
        sudo apt-get update
        sudo apt-get install -y python3 python3-pip python3-venv
    else
        echo "Python 3 is already installed."
    fi
    
    # Check for MySQL
    if ! command_exists mysql; then
        echo "MySQL not found. Installing MySQL..."
        sudo apt-get update
        sudo apt-get install -y mysql-server
    else
        echo "MySQL is already installed."
    fi
    
    # Check for Nginx
    if ! command_exists nginx; then
        echo "Nginx not found. Installing Nginx..."
        sudo apt-get update
        sudo apt-get install -y nginx
    else
        echo "Nginx is already installed."
    fi
    
    echo "All system dependencies installed."
}

# Function to set up Python environment
setup_python_env() {
    echo "Setting up Python virtual environment..."
    
    # Create virtual environment
    python3 -m venv venv
    
    # Activate virtual environment
    source venv/bin/activate
    
    # Install Python dependencies
    pip install -r requirements.txt
    
    echo "Python environment set up successfully."
}

# Function to set up database
setup_database() {
    echo "Setting up database..."
    
    # Get database credentials
    read -p "Enter MySQL root password: " MYSQL_ROOT_PASSWORD
    read -p "Enter database name (default: telegram_manager): " DB_NAME
    DB_NAME=${DB_NAME:-telegram_manager}
    read -p "Enter database user (default: telemanpro): " DB_USER
    DB_USER=${DB_USER:-telemanpro}
    read -s -p "Enter database password: " DB_PASSWORD
    echo ""
    
    # Create database and user
    echo "Creating database and user..."
    mysql -u root -p${MYSQL_ROOT_PASSWORD} <<EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    # Import database schema
    echo "Importing database schema..."
    mysql -u ${DB_USER} -p${DB_PASSWORD} ${DB_NAME} < setup/schema.sql
    
    # Update database configuration
    echo "Updating database configuration..."
    sed -i "s/DB_HOST=.*/DB_HOST=localhost/" config/config.ini
    sed -i "s/DB_NAME=.*/DB_NAME=${DB_NAME}/" config/config.ini
    sed -i "s/DB_USER=.*/DB_USER=${DB_USER}/" config/config.ini
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" config/config.ini
    
    # Update PHP database configuration
    cat > web/includes/db_config.php <<EOF
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '${DB_NAME}');
define('DB_USER', '${DB_USER}');
define('DB_PASSWORD', '${DB_PASSWORD}');

// Create connection
\$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if (\$conn->connect_error) {
    die("Connection failed: " . \$conn->connect_error);
}

// Set character set
\$conn->set_charset("utf8mb4");
?>
EOF
    
    echo "Database setup completed successfully."
}

# Function to set up web server
setup_web_server() {
    echo "Setting up web server..."
    
    # Get web server configuration
    read -p "Enter domain name (default: telemanpro.local): " DOMAIN
    DOMAIN=${DOMAIN:-telemanpro.local}
    read -p "Enter web root path (default: $(pwd)/web): " WEB_ROOT
    WEB_ROOT=${WEB_ROOT:-$(pwd)/web}
    
    # Create Nginx configuration
    echo "Creating Nginx configuration..."
    sudo tee /etc/nginx/sites-available/telemanpro <<EOF
server {
    listen 80;
    server_name ${DOMAIN};
    root ${WEB_ROOT};
    
    index index.php index.html;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    location /api/ {
        proxy_pass http://127.0.0.1:5000/;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
    }
}
EOF
    
    # Enable site
    sudo ln -sf /etc/nginx/sites-available/telemanpro /etc/nginx/sites-enabled/
    
    # Test and restart Nginx
    sudo nginx -t && sudo systemctl restart nginx
    
    echo "Web server setup completed successfully."
}

# Function to set up API server
setup_api_server() {
    echo "Setting up API server..."
    
    # Create systemd service file
    sudo tee /etc/systemd/system/telemanpro-api.service <<EOF
[Unit]
Description=TeleManPro API Server
After=network.target

[Service]
User=$(whoami)
WorkingDirectory=$(pwd)
ExecStart=$(pwd)/venv/bin/python web/api/app.py
Restart=on-failure
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
EOF
    
    # Enable and start service
    sudo systemctl enable telemanpro-api.service
    sudo systemctl start telemanpro-api.service
    
    echo "API server setup completed successfully."
}

# Function to set up final configuration
setup_final_config() {
    echo "Setting up final configuration..."
    
    # Create admin user
    read -p "Enter admin username (default: admin): " ADMIN_USER
    ADMIN_USER=${ADMIN_USER:-admin}
    read -s -p "Enter admin password: " ADMIN_PASSWORD
    echo ""
    read -p "Enter admin email: " ADMIN_EMAIL
    
    # Hash the password
    ADMIN_PASSWORD_HASH=$(php -r "echo password_hash('${ADMIN_PASSWORD}', PASSWORD_BCRYPT);")
    
    # Update admin user in database
    mysql -u root -p${MYSQL_ROOT_PASSWORD} ${DB_NAME} <<EOF
UPDATE users SET username='${ADMIN_USER}', password='${ADMIN_PASSWORD_HASH}', email='${ADMIN_EMAIL}' WHERE id=1;
EOF
    
    # Set permissions
    echo "Setting file permissions..."
    find . -type d -exec chmod 755 {} \;
    find . -type f -exec chmod 644 {} \;
    chmod 755 cli/manager.py
    chmod 755 web/api/app.py
    
    echo "Final configuration completed successfully."
}

# Main installation process
main() {
    echo "Starting installation process..."
    
    # Check if the script is run in the project directory
    if [ ! -f "requirements.txt" ] || [ ! -d "web" ] || [ ! -d "core" ]; then
        echo "Error: Script must be run from the project root directory."
        exit 1
    fi
    
    # Install dependencies
    install_dependencies
    
    # Set up Python environment
    setup_python_env
    
    # Set up database
    setup_database
    
    # Set up web server
    setup_web_server
    
    # Set up API server
    setup_api_server
    
    # Set up final configuration
    setup_final_config
    
    echo "Installation completed successfully!"
    echo "You can now access the system at http://${DOMAIN}"
}

# Run the installation
main
