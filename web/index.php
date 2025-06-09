<?php
// Start session
session_start();

// Include configuration
require_once 'includes/db_config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: auth/login.php');
    exit;
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    // Session expired, log out
    session_unset();
    session_destroy();
    header('Location: auth/login.php?timeout=1');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Get user information
$user_id = $_SESSION['user_id'];
$user = get_user_by_id($user_id);

// Check if user exists
if (!$user) {
    // User not found, log out
    session_unset();
    session_destroy();
    header('Location: auth/login.php?error=1');
    exit;
}

// Check for mobile device
require_once 'includes/mobile_detect.php';
$detect = new Mobile_Detect;
$isMobile = $detect->isMobile();
$isTablet = $detect->isTablet();
$deviceType = ($isTablet ? 'tablet' : ($isMobile ? 'mobile' : 'desktop'));

// Get current page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Validate page
$allowed_pages = ['dashboard', 'accounts', 'operations', 'scrape', 'add', 'logs', 'settings'];
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// Page title
$page_titles = [
    'dashboard' => 'Dashboard',
    'accounts' => 'Accounts',
    'operations' => 'Operations',
    'scrape' => 'Scrape Members',
    'add' => 'Add Members',
    'logs' => 'Logs',
    'settings' => 'Settings'
];

$page_title = $page_titles[$page] ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Manager - <?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
</head>
<body class="<?php echo $deviceType; ?>-view">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="assets/img/logo.png" alt="Telegram Manager" class="img-fluid" style="max-width: 150px;">
                        <h4 class="text-white mt-2">Telegram Manager</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'accounts' ? 'active' : ''; ?>" href="index.php?page=accounts">
                                <i class="fas fa-user-circle me-2"></i> Accounts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'operations' ? 'active' : ''; ?>" href="index.php?page=operations">
                                <i class="fas fa-tasks me-2"></i> Operations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'scrape' ? 'active' : ''; ?>" href="index.php?page=scrape">
                                <i class="fas fa-download me-2"></i> Scrape Members
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'add' ? 'active' : ''; ?>" href="index.php?page=add">
                                <i class="fas fa-user-plus me-2"></i> Add Members
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'logs' ? 'active' : ''; ?>" href="index.php?page=logs">
                                <i class="fas fa-list-alt me-2"></i> Logs
                            </a>
                        </li>
                        <?php if ($user['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'settings' ? 'active' : ''; ?>" href="index.php?page=settings">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <hr class="text-white-50">
                    
                    <div class="d-flex justify-content-between align-items-center px-3 mt-4 mb-2 text-white">
                        <div>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <small class="d-block text-white-50"><?php echo htmlspecialchars($user['role']); ?></small>
                        </div>
                        <a href="auth/logout.php" class="text-white" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </nav>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Mobile Header -->
                <div class="d-md-none p-3 bg-dark text-white d-flex justify-content-between align-items-center">
                    <div>
                        <img src="assets/img/logo.png" alt="Telegram Manager" height="30">
                        <span class="ms-2">Telegram Manager</span>
                    </div>
                    <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $page_title; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <?php if ($page == 'operations'): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary refresh-btn">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($page == 'scrape'): ?>
                            <button type="button" class="btn btn-sm btn-primary" id="startScrapeBtn">
                                <i class="fas fa-play"></i> Start Scraping
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($page == 'add'): ?>
                            <button type="button" class="btn btn-sm btn-primary" id="startAddBtn">
                                <i class="fas fa-user-plus"></i> Start Adding
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Page Content -->
                <div class="content-container">
                    <?php include_once "templates/{$page}.php"; ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
    
    <!-- Page specific script -->
    <?php if (file_exists("assets/js/{$page}.js")): ?>
    <script src="assets/js/<?php echo $page; ?>.js"></script>
    <?php endif; ?>
</body>
</html>
