<?php
// Check if user is logged in
require_once '../includes/functions.php';
session_start();
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = "Settings";

// Load current settings
$config = loadConfig();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../templates/header_common.php'; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../templates/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Settings</h1>
                </div>

                <!-- Settings Tabs -->
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                            <i class="fas fa-cog fa-fw me-2"></i>General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="telegram-tab" data-bs-toggle="tab" data-bs-target="#telegram" type="button" role="tab" aria-controls="telegram" aria-selected="false">
                            <i class="fab fa-telegram fa-fw me-2"></i>Telegram
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="scraper-tab" data-bs-toggle="tab" data-bs-target="#scraper" type="button" role="tab" aria-controls="scraper" aria-selected="false">
                            <i class="fas fa-download fa-fw me-2"></i>Scraper
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="adder-tab" data-bs-toggle="tab" data-bs-target="#adder" type="button" role="tab" aria-controls="adder" aria-selected="false">
                            <i class="fas fa-user-plus fa-fw me-2"></i>Adder
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="proxy-tab" data-bs-toggle="tab" data-bs-target="#proxy" type="button" role="tab" aria-controls="proxy" aria-selected="false">
                            <i class="fas fa-shield-alt fa-fw me-2"></i>Proxy
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                            <i class="fas fa-lock fa-fw me-2"></i>Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab" aria-controls="backup" aria-selected="false">
                            <i class="fas fa-database fa-fw me-2"></i>Backup
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content p-4 bg-white border border-top-0 rounded-bottom shadow-sm" id="settingsTabContent">
                    <!-- General Settings -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <h4 class="mb-4">General Settings</h4>
                        
                        <form id="generalSettingsForm" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="appName" class="form-label">Application Name</label>
                                    <input type="text" class="form-control" id="appName" name="appName" value="<?php echo htmlspecialchars($config['general']['app_name'] ?? 'Telegram Member Manager'); ?>" required>
                                    <div class="form-text">This name will appear in the browser title and emails.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select class="form-select" id="timezone" name="timezone" required>
                                        <?php
                                        $timezones = timezone_identifiers_list();
                                        $currentTimezone = $config['general']['timezone'] ?? 'UTC';
                                        foreach ($timezones as $tz) {
                                            $selected = ($tz === $currentTimezone) ? 'selected' : '';
                                            echo "<option value=\"$tz\" $selected>$tz</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="form-text">System timezone for date and time calculations.</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="logLevel" class="form-label">Log Level</label>
                                    <select class="form-select" id="logLevel" name="logLevel" required>
                                        <?php
                                        $logLevels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];
                                        $currentLogLevel = $config['general']['log_level'] ?? 'INFO';
                                        foreach ($logLevels as $level) {
                                            $selected = ($level === $currentLogLevel) ? 'selected' : '';
                                            echo "<option value=\"$level\" $selected>$level</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="form-text">Level of detail for system logs.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="logRetention" class="form-label">Log Retention (days)</label>
                                    <input type="number" class="form-control" id="logRetention" name="logRetention" min="1" max="365" value="<?php echo htmlspecialchars($config['general']['log_retention'] ?? '30'); ?>" required>
                                    <div class="form-text">Number of days to keep logs before automatic deletion.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableNotifications" name="enableNotifications" <?php echo ($config['general']['enable_notifications'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enableNotifications">Enable Email Notifications</label>
                                </div>
                                <div class="form-text">Receive email notifications for completed operations and errors.</div>
                            </div>

                            <div class="mb-3">
                                <label for="notificationEmail" class="form-label">Notification Email</label>
                                <input type="email" class="form-control" id="notificationEmail" name="notificationEmail" value="<?php echo htmlspecialchars($config['general']['notification_email'] ?? ''); ?>">
                                <div class="form-text">Email address for system notifications.</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <button type="submit" class="btn btn-primary" id="saveGeneralSettingsBtn">
                                <i class="fas fa-save me-2"></i>Save General Settings
                            </button>
                        </form>
                    </div>
                    
                    <!-- Telegram Settings -->
                    <div class="tab-pane fade" id="telegram" role="tabpanel" aria-labelledby="telegram-tab">
                        <h4 class="mb-4">Telegram API Settings</h4>
                        
                        <form id="telegramSettingsForm" class="needs-validation" novalidate>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                These settings control how the system interacts with Telegram's API. Adjusting these incorrectly may cause operations to fail or lead to account restrictions.
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="apiLayerEnabled" class="form-label">API Layer</label>
                                    <select class="form-select" id="apiLayerEnabled" name="apiLayerEnabled" required>
                                        <option value="mtproto" <?php echo ($config['telegram']['api_layer'] ?? 'mtproto') === 'mtproto' ? 'selected' : ''; ?>>MTProto (Telethon)</option>
                                        <option value="tdlib" <?php echo ($config['telegram']['api_layer'] ?? 'mtproto') === 'tdlib' ? 'selected' : ''; ?>>TDLib (Alternative)</option>
                                    </select>
                                    <div class="form-text">The Telegram API layer to use for communication.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="sessionType" class="form-label">Session Type</label>
                                    <select class="form-select" id="sessionType" name="sessionType" required>
                                        <option value="string" <?php echo ($config['telegram']['session_type'] ?? 'string') === 'string' ? 'selected' : ''; ?>>String Session</option>
                                        <option value="sqlite" <?php echo ($config['telegram']['session_type'] ?? 'string') === 'sqlite' ? 'selected' : ''; ?>>SQLite Session</option>
                                    </select>
                                    <div class="form-text">Type of session storage for Telegram sessions.</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="connectionRetries" class="form-label">Connection Retries</label>
                                    <input type="number" class="form-control" id="connectionRetries" name="connectionRetries" min="1" max="10" value="<?php echo htmlspecialchars($config['telegram']['connection_retries'] ?? '3'); ?>" required>
                                    <div class="form-text">Number of retries when connecting to Telegram.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="connectionTimeout" class="form-label">Connection Timeout (seconds)</label>
                                    <input type="number" class="form-control" id="connectionTimeout" name="connectionTimeout" min="5" max="120" value="<?php echo htmlspecialchars($config['telegram']['connection_timeout'] ?? '30'); ?>" required>
                                    <div class="form-text">Timeout for Telegram connections in seconds.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="useIPv6" name="useIPv6" <?php echo ($config['telegram']['use_ipv6'] ?? false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="useIPv6">Use IPv6 Connections</label>
                                </div>
                                <div class="form-text">Enable IPv6 for Telegram connections (requires IPv6 support on your server).</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableFloodWaitAutoRetry" name="enableFloodWaitAutoRetry" <?php echo ($config['telegram']['flood_wait_auto_retry'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enableFloodWaitAutoRetry">Auto-retry on Flood Wait</label>
                                </div>
                                <div class="form-text">Automatically retry operations after flood wait errors.</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <button type="submit" class="btn btn-primary" id="saveTelegramSettingsBtn">
                                <i class="fas fa-save me-2"></i>Save Telegram Settings
                            </button>
                        </form>
                    </div>
                    
                    <!-- Scraper Settings -->
                    <div class="tab-pane fade" id="scraper" role="tabpanel" aria-labelledby="scraper-tab">
                        <h4 class="mb-4">Scraper Settings</h4>
                        
                        <form id="scraperSettingsForm" class="needs-validation" novalidate>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Higher scraping speeds may trigger Telegram's anti-spam measures. Use caution when adjusting these settings.
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="maxScrapeBatchSize" class="form-label">Max Batch Size</label>
                                    <input type="number" class="form-control" id="maxScrapeBatchSize" name="maxScrapeBatchSize" min="100" max="10000" value="<?php echo htmlspecialchars($config['scraper']['max_batch_size'] ?? '1000'); ?>" required>
                                    <div class="form-text">Maximum number of members to retrieve in a single batch.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="scrapeBatchDelay" class="form-label">Batch Delay (seconds)</label>
                                    <input type="number" class="form-control" id="scrapeBatchDelay" name="scrapeBatchDelay" min="1" max="300" value="<?php echo htmlspecialchars($config['scraper']['batch_delay'] ?? '10'); ?>" required>
                                    <div class="form-text">Delay between batches in seconds.</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="scrapeAccountCooldown" class="form-label">Account Cooldown (minutes)</label>
                                    <input type="number" class="form-control" id="scrapeAccountCooldown" name="scrapeAccountCooldown" min="1" max="1440" value="<?php echo htmlspecialchars($config['scraper']['account_cooldown'] ?? '30'); ?>" required>
                                    <div class="form-text">Time to wait before using the same account again for scraping.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="scrapingMode" class="form-label">Scraping Mode</label>
                                    <select class="form-select" id="scrapingMode" name="scrapingMode" required>
                                        <option value="safe" <?php echo ($config['scraper']['mode'] ?? 'safe') === 'safe' ? 'selected' : ''; ?>>Safe (Slower but safer)</option>
                                        <option value="normal" <?php echo ($config['scraper']['mode'] ?? 'safe') === 'normal' ? 'selected' : ''; ?>>Normal (Balanced)</option>
                                        <option value="aggressive" <?php echo ($config['scraper']['mode'] ?? 'safe') === 'aggressive' ? 'selected' : ''; ?>>Aggressive (Faster but riskier)</option>
                                    </select>
                                    <div class="form-text">Controls the speed and safety of scraping operations.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="scrapeUserDetails" name="scrapeUserDetails" <?php echo ($config['scraper']['scrape_user_details'] ?? false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="scrapeUserDetails">Scrape Detailed User Info</label>
                                </div>
                                <div class="form-text">Collect additional information about users (bio, profile photos, etc.). May slow down scraping.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="filterActiveUsers" name="filterActiveUsers" <?php echo ($config['scraper']['filter_active_users'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="filterActiveUsers">Filter Recently Active Users</label>
                                </div>
                                <div class="form-text">Only scrape users who have been active recently (within the last month).</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <button type="submit" class="btn btn-primary" id="saveScraperSettingsBtn">
                                <i class="fas fa-save me-2"></i>Save Scraper Settings
                            </button>
                        </form>
                    </div>
                    
                    <!-- Adder Settings -->
                    <div class="tab-pane fade" id="adder" role="tabpanel" aria-labelledby="adder-tab">
                        <h4 class="mb-4">Member Adder Settings</h4>
                        
                        <form id="adderSettingsForm" class="needs-validation" novalidate>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Adding too many members too quickly can result in account bans. Always use conservative settings.
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="maxAddBatchSize" class="form-label">Max Adds Per Account Daily</label>
                                    <input type="number" class="form-control" id="maxAddBatchSize" name="maxAddBatchSize" min="1" max="200" value="<?php echo htmlspecialchars($config['adder']['max_adds_per_day'] ?? '40'); ?>" required>
                                    <div class="form-text">Maximum number of users a single account can add per day.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="addDelay" class="form-label">Delay Between Adds (seconds)</label>
                                    <input type="number" class="form-control" id="addDelay" name="addDelay" min="10" max="600" value="<?php echo htmlspecialchars($config['adder']['add_delay'] ?? '60'); ?>" required>
                                    <div class="form-text">Time to wait between adding each member.</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="addAccountCooldown" class="form-label">Account Cooldown (hours)</label>
                                    <input type="number" class="form-control" id="addAccountCooldown" name="addAccountCooldown" min="1" max="72" value="<?php echo htmlspecialchars($config['adder']['account_cooldown'] ?? '24'); ?>" required>
                                    <div class="form-text">Time to wait before using the same account again for adding.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="maxErrorsBeforeSwitch" class="form-label">Max Errors Before Switch</label>
                                    <input type="number" class="form-control" id="maxErrorsBeforeSwitch" name="maxErrorsBeforeSwitch" min="1" max="20" value="<?php echo htmlspecialchars($config['adder']['max_errors_before_switch'] ?? '5'); ?>" required>
                                    <div class="form-text">Number of errors before switching to another account.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="stopOnTooManyFloodWaits" name="stopOnTooManyFloodWaits" <?php echo ($config['adder']['stop_on_too_many_flood_waits'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="stopOnTooManyFloodWaits">Stop on Too Many Flood Waits</label>
                                </div>
                                <div class="form-text">Automatically stop operation if too many flood wait errors occur.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableSmartMode" name="enableSmartMode" <?php echo ($config['adder']['smart_mode'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enableSmartMode">Enable Smart Mode</label>
                                </div>
                                <div class="form-text">Dynamically adjust add speed based on Telegram's responses.</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <button type="submit" class="btn btn-primary" id="saveAdderSettingsBtn">
                                <i class="fas fa-save me-2"></i>Save Adder Settings
                            </button>
                        </form>
                    </div>
                    
                    <!-- Proxy Settings -->
                    <div class="tab-pane fade" id="proxy" role="tabpanel" aria-labelledby="proxy-tab">
                        <h4 class="mb-4">Proxy Settings</h4>
                        
                        <form id="proxySettingsForm" class="needs-validation" novalidate>
                            <div class="alert alert-primary">
                                <i class="fas fa-info-circle me-2"></i>
                                Using proxies can help avoid IP-based restrictions from Telegram.
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableProxies" name="enableProxies" <?php echo ($config['proxy']['enabled'] ?? false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enableProxies">Enable Proxies</label>
                                </div>
                                <div class="form-text">Use proxies for Telegram connections.</div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="proxyType" class="form-label">Proxy Type</label>
                                    <select class="form-select" id="proxyType" name="proxyType">
                                        <option value="socks5" <?php echo ($config['proxy']['type'] ?? 'socks5') === 'socks5' ? 'selected' : ''; ?>>SOCKS5</option>
                                        <option value="http" <?php echo ($config['proxy']['type'] ?? 'socks5') === 'http' ? 'selected' : ''; ?>>HTTP</option>
                                        <option value="mtproto" <?php echo ($config['proxy']['type'] ?? 'socks5') === 'mtproto' ? 'selected' : ''; ?>>MTProto</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="proxyRotation" class="form-label">Rotation Strategy</label>
                                    <select class="form-select" id="proxyRotation" name="proxyRotation">
                                        <option value="round_robin" <?php echo ($config['proxy']['rotation'] ?? 'round_robin') === 'round_robin' ? 'selected' : ''; ?>>Round Robin</option>
                                        <option value="random" <?php echo ($config['proxy']['rotation'] ?? 'round_robin') === 'random' ? 'selected' : ''; ?>>Random</option>
                                        <option value="sticky" <?php echo ($config['proxy']['rotation'] ?? 'round_robin') === 'sticky' ? 'selected' : ''; ?>>Sticky (per account)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="proxyList" class="form-label">Proxy List</label>
                                <textarea class="form-control" id="proxyList" name="proxyList" rows="5" placeholder="Format: ip:port:username:password (one per line)"><?php echo htmlspecialchars($config['proxy']['list'] ?? ''); ?></textarea>
                                <div class="form-text">List of proxies, one per line. Format: ip:port:username:password (username and password are optional)</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="testProxiesBeforeUse" name="testProxiesBeforeUse" <?php echo ($config['proxy']['test_before_use'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="testProxiesBeforeUse">Test Proxies Before Use</label>
                                </div>
                                <div class="form-text">Verify that proxies are working before using them.</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <button type="submit" class="btn btn-primary" id="saveProxySettingsBtn">
                                <i class="fas fa-save me-2"></i>Save Proxy Settings
                            </button>
                            <button type="button" class="btn btn-outline-secondary ms-2" id="testProxiesBtn">
                                <i class="fas fa-vial me-2"></i>Test All Proxies
                            </button>
                        </form>
                    </div>
                    
                    <!-- Security Settings -->
                    <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                        <h4 class="mb-4">Security Settings</h4>
                        
                        <form id="securitySettingsForm" class="needs-validation" novalidate>
                            <div class="alert alert-info">
                                <i class="fas fa-shield-alt me-2"></i>
                                Strong security settings help protect your accounts and data.
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="sessionEncryption" class="form-label">Session Encryption</label>
                                    <select class="form-select" id="sessionEncryption" name="sessionEncryption" required>
                                        <option value="none" <?php echo ($config['security']['session_encryption'] ?? 'none') === 'none' ? 'selected' : ''; ?>>None</option>
                                        <option value="basic" <?php echo ($config['security']['session_encryption'] ?? 'none') === 'basic' ? 'selected' : ''; ?>>Basic (Password-based)</option>
                                        <option value="strong" <?php echo ($config['security']['session_encryption'] ?? 'none') === 'strong' ? 'selected' : ''; ?>>Strong (Key file)</option>
                                    </select>
                                    <div class="form-text">Method to encrypt stored Telegram sessions.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="require2FA" class="form-label">2FA Requirement</label>
                                    <select class="form-select" id="require2FA" name="require2FA" required>
                                        <option value="none" <?php echo ($config['security']['require_2fa'] ?? 'none') === 'none' ? 'selected' : ''; ?>>Optional for all users</option>
                                        <option value="admin" <?php echo ($config['security']['require_2fa'] ?? 'none') === 'admin' ? 'selected' : ''; ?>>Required for admins only</option>
                                        <option value="all" <?php echo ($config['security']['require_2fa'] ?? 'none') === 'all' ? 'selected' : ''; ?>>Required for all users</option>
                                    </select>
                                    <div class="form-text">Two-factor authentication requirements for users.</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="passwordPolicy" class="form-label">Password Policy</label>
                                    <select class="form-select" id="passwordPolicy" name="passwordPolicy" required>
                                        <option value="basic" <?php echo ($config['security']['password_policy'] ?? 'medium') === 'basic' ? 'selected' : ''; ?>>Basic (8+ characters)</option>
                                        <option value="medium" <?php echo ($config['security']['password_policy'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium (8+ chars, mixed case, numbers)</option>
                                        <option value="strong" <?php echo ($config['security']['password_policy'] ?? 'medium') === 'strong' ? 'selected' : ''; ?>>Strong (12+ chars, mixed case, numbers, symbols)</option>
                                    </select>
                                    <div class="form-text">Password strength requirements for users.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="sessionTimeout" class="form-label">Session Timeout (minutes)</label>
                                    <input type="number" class="form-control" id="sessionTimeout" name="sessionTimeout" min="5" max="1440" value="<?php echo htmlspecialchars($config['security']['session_timeout'] ?? '60'); ?>" required>
                                    <div class="form-text">Minutes of inactivity before a user is logged out.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableIpWhitelist" name="enableIpWhitelist" <?php echo ($config['security']['ip_whitelist_enabled'] ?? false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enableIpWhitelist">Enable IP Whitelist</label>
                                </div>
                                <div class="form-text">Restrict access to specific IP addresses.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ipWhitelist" class="form-label">IP Whitelist</label>
                                <textarea class="form-control" id="ipWhitelist" name="ipWhitelist" rows="3" placeholder="One IP address or CIDR range per line"><?php echo htmlspecialchars($config['security']['ip_whitelist'] ?? ''); ?></textarea>
                                <div class="form-text">List of allowed IP addresses or CIDR ranges (e.g., 192.168.1.0/24), one per line.</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <button type="submit" class="btn btn-primary" id="saveSecuritySettingsBtn">
                                <i class="fas fa-save me-2"></i>Save Security Settings
                            </button>
                        </form>
                    </div>
                    
                    <!-- Backup Settings -->
                    <div class="tab-pane fade" id="backup" role="tabpanel" aria-labelledby="backup-tab">
                        <h4 class="mb-4">Backup & Restore</h4>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Regular backups help protect your data from loss. Configure automatic backups below.
                        </div>
                        
                        <form id="backupSettingsForm" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableAutoBackups" name="enableAutoBackups" <?php echo ($config['backup']['auto_backup_enabled'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enableAutoBackups">Enable Automatic Backups</label>
                                </div>
                                <div class="form-text">Create regular backups automatically.</div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="backupFrequency" class="form-label">Backup Frequency</label>
                                    <select class="form-select" id="backupFrequency" name="backupFrequency">
                                        <option value="daily" <?php echo ($config['backup']['frequency'] ?? 'weekly') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="weekly" <?php echo ($config['backup']['frequency'] ?? 'weekly') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="monthly" <?php echo ($config['backup']['frequency'] ?? 'weekly') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="backupRetention" class="form-label">Backup Retention Count</label>
                                    <input type="number" class="form-control" id="backupRetention" name="backupRetention" min="1" max="100" value="<?php echo htmlspecialchars($config['backup']['retention'] ?? '5'); ?>">
                                    <div class="form-text">Number of backups to keep before deleting old ones.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backupPath" class="form-label">Backup Directory</label>
                                <input type="text" class="form-control" id="backupPath" name="backupPath" value="<?php echo htmlspecialchars($config['backup']['path'] ?? '../backups'); ?>">
                                <div class="form-text">Path where backups will be stored.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="encryptBackups" name="encryptBackups" <?php echo ($config['backup']['encrypt'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="encryptBackups">Encrypt Backups</label>
                                </div>
                                <div class="form-text">Encrypt backup files for additional security.</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <button type="submit" class="btn btn-primary" id="saveBackupSettingsBtn">
                                <i class="fas fa-save me-2"></i>Save Backup Settings
                            </button>
                            
                            <hr class="my-4">
                            
                            <h5 class="mb-3">Manual Backup & Restore</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Create Backup</h5>
                                            <p class="card-text">Create a full backup of your system, including database, configuration, and sessions.</p>
                                            <button type="button" class="btn btn-success" id="createBackupBtn">
                                                <i class="fas fa-download me-2"></i>Create Backup
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Restore Backup</h5>
                                            <p class="card-text">Restore your system from a previous backup file.</p>
                                            <div class="mb-3">
                                                <input class="form-control" type="file" id="backupFile">
                                            </div>
                                            <button type="button" class="btn btn-warning" id="restoreBackupBtn">
                                                <i class="fas fa-upload me-2"></i>Restore Backup
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set up form submission handlers for each settings tab
            setupFormHandlers();
            
            // Handle backup operations
            setupBackupHandlers();
            
            // Handle proxy testing
            document.getElementById('testProxiesBtn').addEventListener('click', testProxies);
        });
        
        // Set up form submission handlers
        function setupFormHandlers() {
            const forms = [
                { id: 'generalSettingsForm', endpoint: 'general', button: 'saveGeneralSettingsBtn' },
                { id: 'telegramSettingsForm', endpoint: 'telegram', button: 'saveTelegramSettingsBtn' },
                { id: 'scraperSettingsForm', endpoint: 'scraper', button: 'saveScraperSettingsBtn' },
                { id: 'adderSettingsForm', endpoint: 'adder', button: 'saveAdderSettingsBtn' },
                { id: 'proxySettingsForm', endpoint: 'proxy', button: 'saveProxySettingsBtn' },
                { id: 'securitySettingsForm', endpoint: 'security', button: 'saveSecuritySettingsBtn' },
                { id: 'backupSettingsForm', endpoint: 'backup', button: 'saveBackupSettingsBtn' }
            ];
            
            forms.forEach(form => {
                document.getElementById(form.button).addEventListener('click', function(e) {
                    e.preventDefault();
                    saveSettings(form.id, form.endpoint);
                });
            });
        }
        
        // Save settings via API
        function saveSettings(formId, endpoint) {
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            const data = {};
            
            // Convert FormData to JSON object
            for (const [key, value] of formData.entries()) {
                // Handle checkboxes specially
                if (form.elements[key].type === 'checkbox') {
                    data[key] = form.elements[key].checked;
                } else {
                    data[key] = value;
                }
            }
            
            // Send settings to API
            fetch(`/api/settings/${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', `${endpoint.charAt(0).toUpperCase() + endpoint.slice(1)} settings saved successfully.`);
                } else {
                    showAlert('error', `Failed to save settings: ${data.message}`);
                }
            })
            .catch(error => {
                console.error(`Error saving ${endpoint} settings:`, error);
                showAlert('error', `Failed to save settings: ${error.message}`);
            });
        }
        
        // Set up backup handlers
        function setupBackupHandlers() {
            // Create backup
            document.getElementById('createBackupBtn').addEventListener('click', function() {
                fetch('/api/backup/create', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Backup created successfully. Downloading...');
                        
                        // Download the backup file
                        window.location.href = `/api/backup/download/${data.filename}`;
                    } else {
                        showAlert('error', `Failed to create backup: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error creating backup:', error);
                    showAlert('error', `Failed to create backup: ${error.message}`);
                });
            });
            
            // Restore backup
            document.getElementById('restoreBackupBtn').addEventListener('click', function() {
                const fileInput = document.getElementById('backupFile');
                if (!fileInput.files || fileInput.files.length === 0) {
                    showAlert('error', 'Please select a backup file to restore.');
                    return;
                }
                
                const file = fileInput.files[0];
                const formData = new FormData();
                formData.append('backup_file', file);
                
                // Confirm before proceeding
                if (!confirm('Warning: Restoring a backup will overwrite your current data. Are you sure you want to continue?')) {
                    return;
                }
                
                fetch('/api/backup/restore', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Backup restored successfully. The page will reload in 5 seconds.');
                        
                        // Reload the page after 5 seconds
                        setTimeout(() => {
                            window.location.reload();
                        }, 5000);
                    } else {
                        showAlert('error', `Failed to restore backup: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error restoring backup:', error);
                    showAlert('error', `Failed to restore backup: ${error.message}`);
                });
            });
        }
        
        // Test proxies
        function testProxies() {
            const proxyList = document.getElementById('proxyList').value.trim();
            
            if (!proxyList) {
                showAlert('error', 'Please enter at least one proxy to test.');
                return;
            }
            
            const proxyType = document.getElementById('proxyType').value;
            
            showAlert('info', 'Testing proxies. This may take a moment...');
            
            fetch('/api/proxy/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    proxyList: proxyList,
                    proxyType: proxyType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let message = `Proxy test results: ${data.results.working} working, ${data.results.failed} failed.`;
                    showAlert('success', message);
                    
                    // If there are failed proxies, show them
                    if (data.results.failed > 0 && data.results.failedProxies) {
                        console.log('Failed proxies:', data.results.failedProxies);
                        showAlert('warning', `Failed proxies: ${data.results.failedProxies.join(', ')}`);
                    }
                } else {
                    showAlert('error', `Failed to test proxies: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Error testing proxies:', error);
                showAlert('error', `Failed to test proxies: ${error.message}`);
            });
        }
        
        // Show alert function
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : type === 'info' ? 'info' : type === 'warning' ? 'warning' : 'danger'} alert-dismissible fade show`;
            alertDiv.setAttribute('role', 'alert');
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            const main = document.querySelector('main');
            main.insertBefore(alertDiv, main.firstChild);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 5000);
        }
        
        // Helper function to load config (this would normally be from PHP)
        function loadConfig() {
            // For demonstration purposes, we're using a placeholder
            // In a real implementation, this would be populated by PHP
            return {
                general: {},
                telegram: {},
                scraper: {},
                adder: {},
                proxy: {},
                security: {},
                backup: {}
            };
        }
    </script>
</body>
</html>
