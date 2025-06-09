#!/bin/bash
# Quick start script for Telegram Member Manager Pro

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
echo "      Telegram Member Manager Pro Quick Start     "
echo "=================================================="
echo ""

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check for Docker
if ! command_exists docker || ! command_exists docker-compose; then
    echo "Docker and/or docker-compose not found. Would you like to use the native setup instead? (y/n)"
    read -r use_native
    
    if [[ "$use_native" != "y" && "$use_native" != "Y" ]]; then
        echo "Please install Docker and docker-compose, then run this script again."
        exit 1
    fi
    
    # Native setup
    echo "Starting native setup..."
    
    # Check for PHP
    if ! command_exists php; then
        echo "PHP not found. Please install PHP 7.4 or higher."
        exit 1
    fi
    
    # Check for Python
    if ! command_exists python3; then
        echo "Python 3 not found. Please install Python 3.8 or higher."
        exit 1
    fi
    
    # Check for MySQL client
    if ! command_exists mysql; then
        echo "MySQL client not found. Please install MySQL client."
        exit 1
    fi
    
    # Install Python dependencies
    echo "Installing Python dependencies..."
    pip3 install -r requirements.txt
    
    # Setup database
    echo "Setting up database..."
    echo "Please enter your MySQL root password (leave blank if none):"
    read -rs mysql_password
    
    if [[ -z "$mysql_password" ]]; then
        mysql -u root < setup/schema.sql
    else
        mysql -u root -p"$mysql_password" < setup/schema.sql
    fi
    
    # Configure database connection
    echo "Configuring database connection..."
    if [[ -f ".env.example" && ! -f ".env" ]]; then
        cp .env.example .env
        echo "Please update the .env file with your database credentials."
    fi
    
    # Start API server
    echo "Starting API server..."
    python3 web/api/app.py &
    api_pid=$!
    
    # Start PHP development server
    echo "Starting PHP server..."
    cd web && php -S localhost:8000 &
    php_pid=$!
    
    echo ""
    echo "Telegram Member Manager Pro is now running!"
    echo "Access the web interface at: http://localhost:8000"
    echo "API server is running at: http://localhost:5000"
    echo ""
    echo "Default login:"
    echo "  Username: admin"
    echo "  Password: admin123"
    echo ""
    echo "Press Ctrl+C to stop the servers."
    
    # Wait for user to stop
    trap "kill $api_pid $php_pid; echo 'Servers stopped.'; exit 0" INT
    wait
else
    # Docker setup
    echo "Starting Docker setup..."
    
    # Check if docker-compose.yml exists
    if [[ ! -f "docker-compose.yml" ]]; then
        echo "docker-compose.yml not found. Please make sure you're in the project root directory."
        exit 1
    fi
    
    # Start Docker containers
    echo "Starting Docker containers..."
    docker-compose up -d
    
    echo ""
    echo "Telegram Member Manager Pro is now running in Docker!"
    echo "Access the web interface at: http://localhost"
    echo "API server is running at: http://localhost:5000"
    echo ""
    echo "Default login:"
    echo "  Username: admin"
    echo "  Password: admin123"
    echo ""
    echo "To stop the servers, run: docker-compose down"
fi
