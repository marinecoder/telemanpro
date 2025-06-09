<?php
// Get the current page name for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user';
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <a href="../index.php">
                <img src="../assets/images/logo.svg" alt="TeleManPro Logo" class="img-fluid" style="max-width: 120px;">
            </a>
            <h6 class="text-white mt-2">Telegram Member Manager</h6>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="../index.php">
                    <i class="fas fa-tachometer-alt fa-fw me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'accounts.php' ? 'active' : ''; ?>" href="../templates/accounts.php">
                    <i class="fas fa-user-circle fa-fw me-2"></i>
                    Telegram Accounts
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'scrape.php' ? 'active' : ''; ?>" href="../templates/scrape.php">
                    <i class="fas fa-download fa-fw me-2"></i>
                    Scrape Members
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'add.php' ? 'active' : ''; ?>" href="../templates/add.php">
                    <i class="fas fa-user-plus fa-fw me-2"></i>
                    Add Members
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'operations.php' ? 'active' : ''; ?>" href="../templates/operations.php">
                    <i class="fas fa-tasks fa-fw me-2"></i>
                    Operations
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'analytics.php' ? 'active' : ''; ?>" href="../templates/analytics.php">
                    <i class="fas fa-chart-bar fa-fw me-2"></i>
                    Analytics
                </a>
            </li>
            
            <?php if ($userRole == 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>" href="../templates/users.php">
                    <i class="fas fa-users fa-fw me-2"></i>
                    User Management
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>" href="../templates/settings.php">
                    <i class="fas fa-cog fa-fw me-2"></i>
                    Settings
                </a>
            </li>
        </ul>
        
        <hr class="text-light">
        
        <div class="px-3 mb-3">
            <div class="text-white-50 small">
                <div>Logged in as: <span class="fw-bold"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?></span></div>
                <div class="mt-1">Role: <span class="badge bg-primary"><?php echo ucfirst($userRole); ?></span></div>
            </div>
            <div class="d-grid gap-2 mt-3">
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>
