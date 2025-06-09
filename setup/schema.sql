-- Drop database if it exists (only for development purposes)
-- DROP DATABASE IF EXISTS telegram_manager;

-- Create database
CREATE DATABASE IF NOT EXISTS telegram_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE telegram_manager;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('admin', 'user') DEFAULT 'user',
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(100),
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Accounts table
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    phone VARCHAR(20) NOT NULL UNIQUE,
    api_id VARCHAR(50) NOT NULL,
    api_hash VARCHAR(100) NOT NULL,
    status ENUM('active', 'restricted', 'banned') DEFAULT 'active',
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cooldown_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Operations table
CREATE TABLE IF NOT EXISTS operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('scrape', 'add') NOT NULL,
    target VARCHAR(255) NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed', 'stopped') DEFAULT 'pending',
    progress INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Logs table
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT,
    account_id INT,
    action VARCHAR(50) NOT NULL,
    status ENUM('success', 'warning', 'error') DEFAULT 'success',
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operation_id) REFERENCES operations(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL
);

-- Create a default admin user (password: admin123)
INSERT INTO users (username, password, email, first_name, last_name, role)
VALUES ('admin', '$2y$10$KxrV9KpIxjVVv5DMiGJ5W.0MbM9H3GQ/qDGZBw8E8RmaInumJxBF.', 'admin@example.com', 'Admin', 'User', 'admin');

-- Create indexes for better performance
CREATE INDEX idx_accounts_status ON accounts(status);
CREATE INDEX idx_accounts_cooldown ON accounts(cooldown_until);
CREATE INDEX idx_operations_user_id ON operations(user_id);
CREATE INDEX idx_operations_status ON operations(status);
CREATE INDEX idx_logs_operation_id ON logs(operation_id);
CREATE INDEX idx_logs_account_id ON logs(account_id);
CREATE INDEX idx_logs_created_at ON logs(created_at);
