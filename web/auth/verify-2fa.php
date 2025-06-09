<?php
// Start session
session_start();

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Include configuration
require_once '../includes/db_config.php';
require_once '../includes/functions.php';

// Handle 2FA verification
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $user_id = $_POST['user_id'] ?? 0;
    $code = $_POST['code'] ?? '';
    
    if (empty($user_id) || empty($code)) {
        $error = 'Please enter the verification code.';
    } else {
        // API request for 2FA verification
        $response = api_request('/api/auth/verify-2fa', 'POST', [
            'user_id' => $user_id,
            'code' => $code
        ], false);
        
        if (isset($response['error'])) {
            $error = $response['error'];
        } elseif (isset($response['access_token'])) {
            // Login successful
            $_SESSION['user_id'] = $response['user']['id'];
            $_SESSION['username'] = $response['user']['username'];
            $_SESSION['role'] = $response['user']['role'];
            $_SESSION['access_token'] = $response['access_token'];
            $_SESSION['last_activity'] = time();
            
            // Redirect to index
            header('Location: ../index.php');
            exit;
        } else {
            $error = 'An unexpected error occurred. Please try again.';
        }
    }
}

// If not a POST request, redirect to login
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_POST['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - Telegram Manager</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/auth.css">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <img src="../assets/img/logo.png" alt="Telegram Manager" class="img-fluid mb-4" style="max-width: 150px;">
                                        <h1 class="h4 text-gray-900 mb-4">Two-Factor Authentication</h1>
                                    </div>
                                    
                                    <?php if ($error): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> Please enter the verification code from your authenticator app.
                                    </div>
                                    
                                    <form class="user" method="POST" action="verify-2fa.php">
                                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                        <div class="form-group mb-3">
                                            <input type="text" class="form-control form-control-user" name="code" placeholder="Enter 2FA Code" required autofocus>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Verify
                                        </button>
                                    </form>
                                    
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="login.php">Back to Login</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
