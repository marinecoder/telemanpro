#!/usr/bin/env python3
import os
import sys
import logging
import asyncio
import json
import time
from datetime import datetime, timedelta, timezone
import argparse
import threading

# Add parent directory to path for imports
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))

from flask import Flask, jsonify, request, send_file
from flask_jwt_extended import JWTManager, create_access_token, get_jwt_identity, jwt_required
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from werkzeug.utils import secure_filename
from functools import wraps

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
        logging.FileHandler("../../logs/api.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("api")

# Initialize Flask app
app = Flask(__name__)
app.config['JWT_SECRET_KEY'] = os.getenv('JWT_SECRET_KEY', 'super-secret')  # Change this in production
app.config['JWT_ACCESS_TOKEN_EXPIRES'] = timedelta(hours=1)
app.config['UPLOAD_FOLDER'] = os.path.abspath(os.path.join(os.path.dirname(__file__), '../../uploads'))

# Ensure upload folder exists
os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)

# Initialize JWT
jwt = JWTManager(app)

# Initialize rate limiter
limiter = Limiter(
    app=app,
    key_func=get_remote_address,
    default_limits=["200 per day", "50 per hour"]
)

# Initialize database
db = DB.get_instance()

# Initialize managers
account_rotator = AccountRotator()
scraper = Scraper()
adder = Adder()

# Active operations
active_operations = {}

# Cross-Origin Resource Sharing (CORS) handling
@app.after_request
def after_request(response):
    response.headers.add('Access-Control-Allow-Origin', '*')
    response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization')
    response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE')
    return response

# Helper function to check admin role
def admin_required(fn):
    @wraps(fn)
    @jwt_required()
    def wrapper(*args, **kwargs):
        # Get current user
        current_user_id = get_jwt_identity()
        user = db.get_one("SELECT * FROM users WHERE id = %s", (current_user_id,))
        
        if not user or user['role'] != 'admin':
            return jsonify({"error": "Admin privileges required"}), 403
        
        return fn(*args, **kwargs)
    
    return wrapper

# Authentication routes
@app.route('/api/auth/login', methods=['POST'])
@limiter.limit("5 per minute")
def login():
    """Authenticate user and return JWT token"""
    if not request.is_json:
        return jsonify({"error": "Missing JSON in request"}), 400
    
    username = request.json.get('username', None)
    password = request.json.get('password', None)
    
    if not username or not password:
        return jsonify({"error": "Missing username or password"}), 400
    
    # Get user from database
    user = db.get_one("SELECT * FROM users WHERE username = %s", (username,))
    
    if not user:
        return jsonify({"error": "Invalid username or password"}), 401
    
    # Verify password (PHP password_verify equivalent in Python would be implemented here)
    # For now, we'll use a simple comparison for demo purposes
    # In production, use proper password verification with bcrypt
    if password != 'admin123':  # This is a placeholder for proper password verification
        return jsonify({"error": "Invalid username or password"}), 401
    
    # Check if 2FA is enabled
    if user['two_factor_enabled']:
        return jsonify({
            "requires_2fa": True,
            "user_id": user['id']
        }), 200
    
    # Create access token
    access_token = create_access_token(identity=user['id'])
    
    # Update last login
    db.execute("UPDATE users SET last_login = %s WHERE id = %s", 
               (datetime.now().strftime('%Y-%m-%d %H:%M:%S'), user['id']))
    
    return jsonify({
        "access_token": access_token,
        "user": {
            "id": user['id'],
            "username": user['username'],
            "email": user['email'],
            "first_name": user['first_name'],
            "last_name": user['last_name'],
            "role": user['role']
        }
    }), 200

@app.route('/api/auth/verify-2fa', methods=['POST'])
@limiter.limit("5 per minute")
def verify_2fa():
    """Verify 2FA code and complete login"""
    if not request.is_json:
        return jsonify({"error": "Missing JSON in request"}), 400
    
    user_id = request.json.get('user_id', None)
    code = request.json.get('code', None)
    
    if not user_id or not code:
        return jsonify({"error": "Missing user ID or code"}), 400
    
    # Get user from database
    user = db.get_one("SELECT * FROM users WHERE id = %s", (user_id,))
    
    if not user:
        return jsonify({"error": "User not found"}), 404
    
    # Verify 2FA code
    # In a real implementation, you would use a proper 2FA library
    # For demo purposes, we'll accept any code
    if code != '123456':  # This is a placeholder for proper 2FA verification
        return jsonify({"error": "Invalid 2FA code"}), 401
    
    # Create access token
    access_token = create_access_token(identity=user['id'])
    
    # Update last login
    db.execute("UPDATE users SET last_login = %s WHERE id = %s", 
               (datetime.now().strftime('%Y-%m-%d %H:%M:%S'), user['id']))
    
    return jsonify({
        "access_token": access_token,
        "user": {
            "id": user['id'],
            "username": user['username'],
            "email": user['email'],
            "first_name": user['first_name'],
            "last_name": user['last_name'],
            "role": user['role']
        }
    }), 200

# User routes
@app.route('/api/users/me', methods=['GET'])
@jwt_required()
def get_current_user():
    """Get current user information"""
    current_user_id = get_jwt_identity()
    user = db.get_one("SELECT id, username, email, first_name, last_name, role, two_factor_enabled, last_login, created_at FROM users WHERE id = %s", (current_user_id,))
    
    if not user:
        return jsonify({"error": "User not found"}), 404
    
    return jsonify({"user": user}), 200

@app.route('/api/users', methods=['GET'])
@admin_required
def get_users():
    """Get all users (admin only)"""
    users = db.query("SELECT id, username, email, first_name, last_name, role, two_factor_enabled, last_login, created_at FROM users")
    return jsonify({"users": users}), 200

@app.route('/api/users', methods=['POST'])
@admin_required
def create_user():
    """Create a new user (admin only)"""
    if not request.is_json:
        return jsonify({"error": "Missing JSON in request"}), 400
    
    username = request.json.get('username', None)
    password = request.json.get('password', None)
    email = request.json.get('email', None)
    first_name = request.json.get('first_name', None)
    last_name = request.json.get('last_name', None)
    role = request.json.get('role', 'user')
    
    if not username or not password or not email:
        return jsonify({"error": "Missing required fields"}), 400
    
    # Check if username or email already exists
    existing_user = db.get_one("SELECT * FROM users WHERE username = %s OR email = %s", (username, email))
    if existing_user:
        return jsonify({"error": "Username or email already exists"}), 409
    
    # Create user
    # In production, hash the password properly with bcrypt
    try:
        user_id = db.insert('users', {
            'username': username,
            'password': password,  # This should be hashed in production
            'email': email,
            'first_name': first_name,
            'last_name': last_name,
            'role': role
        })
        
        return jsonify({
            "message": "User created successfully",
            "user_id": user_id
        }), 201
    except Exception as e:
        logger.error(f"Error creating user: {str(e)}")
        return jsonify({"error": "Failed to create user"}), 500

# Account routes
@app.route('/api/accounts', methods=['GET'])
@jwt_required()
def get_accounts():
    """Get all accounts for current user"""
    current_user_id = get_jwt_identity()
    user = db.get_one("SELECT role FROM users WHERE id = %s", (current_user_id,))
    
    if user['role'] == 'admin':
        # Admin sees all accounts
        accounts = db.query("SELECT * FROM accounts")
    else:
        # Regular user sees only their accounts
        accounts = db.query("SELECT * FROM accounts WHERE user_id = %s", (current_user_id,))
    
    return jsonify({"accounts": accounts}), 200

@app.route('/api/accounts', methods=['POST'])
@jwt_required()
def create_account():
    """Create a new account"""
    if not request.is_json:
        return jsonify({"error": "Missing JSON in request"}), 400
    
    current_user_id = get_jwt_identity()
    phone = request.json.get('phone', None)
    api_id = request.json.get('api_id', None)
    api_hash = request.json.get('api_hash', None)
    
    if not phone or not api_id or not api_hash:
        return jsonify({"error": "Missing required fields"}), 400
    
    # Check if account already exists
    existing_account = db.get_one("SELECT * FROM accounts WHERE phone = %s", (phone,))
    if existing_account:
        return jsonify({"error": "Account with this phone number already exists"}), 409
    
    # Create account
    try:
        account_data = {
            'user_id': current_user_id,
            'phone': phone,
            'api_id': api_id,
            'api_hash': api_hash,
            'status': 'active',
            'last_used': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
        
        account_id = db.insert('accounts', account_data)
        
        return jsonify({
            "message": "Account created successfully",
            "account_id": account_id
        }), 201
    except Exception as e:
        logger.error(f"Error creating account: {str(e)}")
        return jsonify({"error": "Failed to create account"}), 500

@app.route('/api/accounts/<int:account_id>', methods=['DELETE'])
@jwt_required()
def delete_account(account_id):
    """Delete an account"""
    current_user_id = get_jwt_identity()
    user = db.get_one("SELECT role FROM users WHERE id = %s", (current_user_id,))
    
    # Get the account
    account = db.get_one("SELECT * FROM accounts WHERE id = %s", (account_id,))
    
    if not account:
        return jsonify({"error": "Account not found"}), 404
    
    # Check if user has permission to delete this account
    if user['role'] != 'admin' and account['user_id'] != current_user_id:
        return jsonify({"error": "Not authorized to delete this account"}), 403
    
    # Delete the account
    try:
        db.execute("DELETE FROM accounts WHERE id = %s", (account_id,))
        return jsonify({"message": "Account deleted successfully"}), 200
    except Exception as e:
        logger.error(f"Error deleting account: {str(e)}")
        return jsonify({"error": "Failed to delete account"}), 500

# Operation routes
@app.route('/api/operations', methods=['GET'])
@jwt_required()
def get_operations():
    """Get all operations for current user"""
    current_user_id = get_jwt_identity()
    user = db.get_one("SELECT role FROM users WHERE id = %s", (current_user_id,))
    
    if user['role'] == 'admin':
        # Admin sees all operations
        operations = db.query("SELECT * FROM operations ORDER BY started_at DESC")
    else:
        # Regular user sees only their operations
        operations = db.query("SELECT * FROM operations WHERE user_id = %s ORDER BY started_at DESC", (current_user_id,))
    
    return jsonify({"operations": operations}), 200

@app.route('/api/operations/<int:operation_id>', methods=['GET'])
@jwt_required()
def get_operation(operation_id):
    """Get operation details"""
    current_user_id = get_jwt_identity()
    user = db.get_one("SELECT role FROM users WHERE id = %s", (current_user_id,))
    
    # Get the operation
    operation = db.get_one("SELECT * FROM operations WHERE id = %s", (operation_id,))
    
    if not operation:
        return jsonify({"error": "Operation not found"}), 404
    
    # Check if user has permission to view this operation
    if user['role'] != 'admin' and operation['user_id'] != current_user_id:
        return jsonify({"error": "Not authorized to view this operation"}), 403
    
    # Get logs for this operation
    logs = db.query("SELECT * FROM logs WHERE operation_id = %s ORDER BY created_at DESC", (operation_id,))
    
    # Return operation with logs
    return jsonify({
        "operation": operation,
        "logs": logs
    }), 200

@app.route('/api/operations/<int:operation_id>/stop', methods=['POST'])
@jwt_required()
def stop_operation(operation_id):
    """Stop an ongoing operation"""
    current_user_id = get_jwt_identity()
    user = db.get_one("SELECT role FROM users WHERE id = %s", (current_user_id,))
    
    # Get the operation
    operation = db.get_one("SELECT * FROM operations WHERE id = %s", (operation_id,))
    
    if not operation:
        return jsonify({"error": "Operation not found"}), 404
    
    # Check if user has permission to stop this operation
    if user['role'] != 'admin' and operation['user_id'] != current_user_id:
        return jsonify({"error": "Not authorized to stop this operation"}), 403
    
    # Check if operation is running
    if operation['status'] != 'running':
        return jsonify({"error": "Operation is not running"}), 400
    
    # Stop the operation
    if operation['type'] == 'scrape':
        scraper.stop_scraping()
    elif operation['type'] == 'add':
        adder.stop_adding()
    
    # Update operation status
    db.update_operation_progress(operation_id, operation['progress'], 'stopped')
    
    return jsonify({"message": "Operation stopped successfully"}), 200

# Scrape operations
@app.route('/api/scrape', methods=['POST'])
@jwt_required()
def start_scrape():
    """Start a scraping operation"""
    if not request.is_json:
        return jsonify({"error": "Missing JSON in request"}), 400
    
    current_user_id = get_jwt_identity()
    channel = request.json.get('channel', None)
    limit = request.json.get('limit', 0)
    
    if not channel:
        return jsonify({"error": "Channel is required"}), 400
    
    # Create output file path
    output_dir = os.path.join(app.config['UPLOAD_FOLDER'], str(current_user_id))
    os.makedirs(output_dir, exist_ok=True)
    
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    channel_name = channel.replace('@', '').replace('/', '_')
    output_file = os.path.join(output_dir, f"scrape_{channel_name}_{timestamp}.csv")
    
    # Create operation record
    operation_id = db.create_operation(current_user_id, 'scrape', channel)
    
    # Start scraping in a background thread
    def run_scrape():
        loop = asyncio.new_event_loop()
        asyncio.set_event_loop(loop)
        
        try:
            success, message = loop.run_until_complete(
                scraper.scrape_channel(channel, output_file, current_user_id, limit, operation_id)
            )
            
            logger.info(f"Scraping completed: {message}")
        except Exception as e:
            logger.error(f"Error in scrape operation: {str(e)}")
        finally:
            loop.close()
    
    thread = threading.Thread(target=run_scrape)
    thread.daemon = True
    thread.start()
    
    return jsonify({
        "message": "Scraping operation started",
        "operation_id": operation_id
    }), 200

@app.route('/api/operations/<int:operation_id>/download', methods=['GET'])
@jwt_required()
def download_operation_file(operation_id):
    """Download the result file of a completed operation"""
    current_user_id = get_jwt_identity()
    user = db.get_one("SELECT role FROM users WHERE id = %s", (current_user_id,))
    
    # Get the operation
    operation = db.get_one("SELECT * FROM operations WHERE id = %s", (operation_id,))
    
    if not operation:
        return jsonify({"error": "Operation not found"}), 404
    
    # Check if user has permission to download this file
    if user['role'] != 'admin' and operation['user_id'] != current_user_id:
        return jsonify({"error": "Not authorized to download this file"}), 403
    
    # Check if operation is completed
    if operation['status'] not in ['completed', 'stopped']:
        return jsonify({"error": "Operation is not completed"}), 400
    
    # Find the file
    output_dir = os.path.join(app.config['UPLOAD_FOLDER'], str(operation['user_id']))
    channel_name = operation['target'].replace('@', '').replace('/', '_')
    
    # Look for files matching the pattern
    import glob
    files = glob.glob(os.path.join(output_dir, f"scrape_{channel_name}_*.csv"))
    
    if not files:
        return jsonify({"error": "File not found"}), 404
    
    # Get the most recent file
    file_path = max(files, key=os.path.getctime)
    
    # Return the file
    return send_file(file_path, as_attachment=True)

# Add operations
@app.route('/api/add', methods=['POST'])
@jwt_required()
def start_add():
    """Start an add operation"""
    current_user_id = get_jwt_identity()
    
    # Check if members file was uploaded
    if 'members_file' not in request.files:
        return jsonify({"error": "No file uploaded"}), 400
    
    file = request.files['members_file']
    
    if file.filename == '':
        return jsonify({"error": "No file selected"}), 400
    
    # Get the target channel
    target = request.form.get('target', None)
    
    if not target:
        return jsonify({"error": "Target channel is required"}), 400
    
    # Save the uploaded file
    upload_dir = os.path.join(app.config['UPLOAD_FOLDER'], str(current_user_id))
    os.makedirs(upload_dir, exist_ok=True)
    
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    filename = secure_filename(f"members_{timestamp}_{file.filename}")
    file_path = os.path.join(upload_dir, filename)
    
    file.save(file_path)
    
    # Create operation record
    operation_id = db.create_operation(current_user_id, 'add', target)
    
    # Start adding in a background thread
    def run_add():
        loop = asyncio.new_event_loop()
        asyncio.set_event_loop(loop)
        
        try:
            success, message = loop.run_until_complete(
                adder.add_members(target, file_path, current_user_id, operation_id)
            )
            
            logger.info(f"Adding completed: {message}")
        except Exception as e:
            logger.error(f"Error in add operation: {str(e)}")
        finally:
            loop.close()
    
    thread = threading.Thread(target=run_add)
    thread.daemon = True
    thread.start()
    
    return jsonify({
        "message": "Adding operation started",
        "operation_id": operation_id
    }), 200

# Dashboard statistics
@app.route('/api/stats', methods=['GET'])
@jwt_required()
def get_stats():
    """Get dashboard statistics"""
    current_user_id = get_jwt_identity()
    user = db.get_one("SELECT role FROM users WHERE id = %s", (current_user_id,))
    
    # Base statistics query
    accounts_query = "SELECT COUNT(*) as count, status FROM accounts"
    operations_query = "SELECT COUNT(*) as count, status FROM operations"
    recent_operations_query = "SELECT * FROM operations ORDER BY started_at DESC LIMIT 5"
    
    # Modify queries based on user role
    if user['role'] != 'admin':
        accounts_query += " WHERE user_id = %s"
        operations_query += " WHERE user_id = %s"
        recent_operations_query = "SELECT * FROM operations WHERE user_id = %s ORDER BY started_at DESC LIMIT 5"
    
    # Get account statistics
    if user['role'] == 'admin':
        account_stats = db.query(accounts_query + " GROUP BY status")
        operation_stats = db.query(operations_query + " GROUP BY status")
        recent_operations = db.query(recent_operations_query)
    else:
        account_stats = db.query(accounts_query + " GROUP BY status", (current_user_id,))
        operation_stats = db.query(operations_query + " GROUP BY status", (current_user_id,))
        recent_operations = db.query(recent_operations_query, (current_user_id,))
    
    # Format statistics
    accounts = {
        'total': sum(stat['count'] for stat in account_stats),
        'active': next((stat['count'] for stat in account_stats if stat['status'] == 'active'), 0),
        'restricted': next((stat['count'] for stat in account_stats if stat['status'] == 'restricted'), 0),
        'banned': next((stat['count'] for stat in account_stats if stat['status'] == 'banned'), 0)
    }
    
    operations = {
        'total': sum(stat['count'] for stat in operation_stats),
        'pending': next((stat['count'] for stat in operation_stats if stat['status'] == 'pending'), 0),
        'running': next((stat['count'] for stat in operation_stats if stat['status'] == 'running'), 0),
        'completed': next((stat['count'] for stat in operation_stats if stat['status'] == 'completed'), 0),
        'failed': next((stat['count'] for stat in operation_stats if stat['status'] == 'failed'), 0),
        'stopped': next((stat['count'] for stat in operation_stats if stat['status'] == 'stopped'), 0)
    }
    
    return jsonify({
        "accounts": accounts,
        "operations": operations,
        "recent_operations": recent_operations
    }), 200

# Error handling
@app.errorhandler(404)
def not_found(e):
    return jsonify({"error": "Not found"}), 404

@app.errorhandler(500)
def server_error(e):
    return jsonify({"error": "Internal server error"}), 500

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Telegram Manager API Server')
    parser.add_argument('--host', default='127.0.0.1', help='Host to run the server on')
    parser.add_argument('--port', type=int, default=5000, help='Port to run the server on')
    args = parser.parse_args()
    
    app.run(host=args.host, port=args.port, debug=True)
