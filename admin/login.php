<?php
// Initialize session
session_start();



// Initialize variables
$error = '';
$username = '';
$pdo = null;

// Include database connection
try {
    // Check if file exists before requiring it
    if (!file_exists('includes/db.php')) {
        throw new Exception("Database file not found at 'includes/db.php'");
    }
    require_once('includes/db.php');
    
    // Verify $pdo is set after including db.php
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection not established. Check db.php file.");
    }
} catch (Exception $e) {
    $error = "Database connection error: " . $e->getMessage();
    // Log the error for administrators
    error_log("Login page error: " . $e->getMessage());
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for maximum login attempts
function checkLoginAttempts($db, $ip) {
    if (!$db instanceof PDO) {
        return false; // Can't check attempts without database
    }
    
    try {
        $timeWindow = time() - (15 * 60); // 15 minutes window
        $stmt = $db->prepare("SELECT COUNT(*) as attempts FROM admin_logs WHERE ip_address = ? AND action = 'Failed Login' AND created_at > FROM_UNIXTIME(?)");
        $stmt->execute([$ip, $timeWindow]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($result['attempts']) && $result['attempts'] >= 5; // 5 attempts limit
    } catch (PDOException $e) {
        error_log("Login attempt check failed: " . $e->getMessage());
        return false; // Default to allowing login if check fails
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security verification failed, please try again";
    } else if (!$pdo instanceof PDO) {
        $error = "Cannot process login due to database connection issue";
    } else {
        // Get client IP
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Check for excessive login attempts
        if (checkLoginAttempts($pdo, $ip)) {
            $error = "Too many failed login attempts. Please try again later.";
        } else {
            // Validate input
            $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
            $password = $_POST['password'];
            
            // Basic validation
            if (empty($username) || empty($password)) {
                $error = "Username and password are required";
            } else {
                try {
                    // Check credentials against database
                    $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
                    $stmt->execute([$username]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($admin && password_verify($password, $admin['password'])) {
                        // Log successful login
                        $action = "Admin Login";
                        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                        
                        $log_stmt = $pdo->prepare("INSERT INTO admin_logs (admin_username, action, ip_address) VALUES (?, ?, ?)");
                        $log_stmt->execute([$admin['username'], $action, $ip]);
                        
                        // Set secure session
                        session_regenerate_id(true);
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['last_activity'] = time();
                        
                        // Handle remember me
                        if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
                            $selector = bin2hex(random_bytes(16));
                            $validator = bin2hex(random_bytes(32));
                            $expires = time() + (30 * 24 * 60 * 60); // 30 days
                            
                            // Set cookie
                            $cookie = $selector . ':' . $validator;
                            setcookie('admin_remember', $cookie, $expires, '/', '', true, true);
                        }
                        
                        header("Location: index.php");
                        exit;
                    } else {
                        // Failed login - wait to prevent brute force
                        sleep(1);
                        $error = "Invalid username or password";
                        
                        try {
                            // Log failed attempt
                            $action = "Failed Login";
                            $details = "Invalid credentials";
                            $log_stmt = $pdo->prepare("INSERT INTO admin_logs (admin_username, action, ip_address) VALUES (?, ?, ?)");
                            $log_stmt->execute([$username, $action, $ip]);
                        } catch (PDOException $e) {
                            error_log("Failed to log failed login attempt: " . $e->getMessage());
                        }
                    }
                } catch (PDOException $e) {
                    $error = "Login processing error";
                    error_log("Login error: " . $e->getMessage());
                }
            }
        }
    }
    
    // Regenerate CSRF token after submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Log In | JCDA Admin Portal</title>
    <?php include 'layouts/title-meta.php'; ?>
    <?php include 'layouts/head-css.php'; ?>
</head>

<body class="authentication-bg position-relative">

<?php include 'layouts/background.php'; ?>

    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-4 col-lg-5">
                    <div class="card">

                        <!-- Logo -->
                        <div class="card-header py-4 text-center bg-primary">
                            <a href="index.php">
                                <span><img src="assets/images/logo.png" alt="logo" height="22"></span>
                            </a>
                        </div>

                        <div class="card-body p-4">

                            <div class="text-center w-75 m-auto">
                                <h4 class="text-dark-50 text-center pb-0 fw-bold">Admin Sign In</h4>
                                <p class="text-muted mb-4">Enter your username and password to access admin panel.</p>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>

                            <form method="post">
                                <!-- CSRF Protection -->
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input class="form-control" type="text" id="username" name="username" required 
                                           value="<?php echo htmlspecialchars($username); ?>"
                                           placeholder="Enter your username">
                                </div>

                                <div class="mb-3">
                                    <a href="auth-recoverpw.php" class="text-muted float-end fs-12">Forgot your password?</a>
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
                                        <div class="input-group-text" data-password="false">
                                            <span class="password-eye"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="checkbox-signin" name="remember" checked>
                                        <label class="form-check-label" for="checkbox-signin">Remember me</label>
                                    </div>
                                </div>

                                <div class="mb-3 text-center">
                                    <button class="btn btn-primary" type="submit">Log In</button>
                                </div>

                            </form>
                        </div> <!-- end card-body -->
                    </div>
                    <!-- end card -->

                    <div class="row mt-3">
                        <div class="col-12 text-center">
                            <p class="text-muted bg-body">Access restricted to administrators only.</p>
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->

                </div> <!-- end col -->
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- end page -->

    <footer class="footer footer-alt fw-medium">
        <span class="bg-body">
            <script>document.write(new Date().getFullYear())</script> © JCDA - All Rights Reserved
        </span>
    </footer>
    
    <?php include 'layouts/footer-scripts.php'; ?>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

</body>

</html>