<?php include 'layouts/session.php'; ?>
<?php include 'layouts/main.php'; ?>

<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if session is not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Force HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is already logged in via session or cookie
if (isset($_SESSION['user_id'])) {
    $error = "You are already logged in.";
} elseif (isset($_COOKIE['remember_me'])) {
    // Validate the remember me cookie
    list($selector, $authenticator) = explode(':', $_COOKIE['remember_me']);
    $stmt = $pdo->prepare("SELECT * FROM auth_tokens WHERE selector = :selector");
    $stmt->execute(['selector' => $selector]);
    $token = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($token && hash_equals($token['token'], hash('sha256', $authenticator)) && $token['expires'] >= date('Y-m-d H:i:s')) {
        // Log the user in
        $_SESSION['user_id'] = $token['user_id'];
        header("Location: index.php");
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $username = sanitize_input($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        if (empty($username) || empty($password)) {
            $error = "Both username and password are required.";
        } else {
            try {
                // Check if username exists
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
                $stmt->execute(['username' => $username, 'email' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);


                    if ($remember) {
                        // Generate a new remember me token
                        $selector = bin2hex(random_bytes(8));
                        $authenticator = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

                        // Store the token in the database
                        $stmt = $pdo->prepare("INSERT INTO auth_tokens (selector, token, user_id, expires) VALUES (:selector, :token, :user_id, :expires)");
                        $stmt->execute([
                            'selector' => $selector,
                            'token' => hash('sha256', $authenticator),
                            'user_id' => $user['id'],
                            'expires' => $expires
                        ]);

                        // Set the cookie
                        setcookie(
                            'remember_me',
                            $selector . ':' . $authenticator,
                            time() + 86400 * 30, // 30 days
                            '/',
                            $_SERVER['HTTP_HOST'],
                            isset($_SERVER['HTTPS']),
                            true // HTTP only
                        );
                    }

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['userLoggedIn'] = true;
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Invalid username or password.";
                }
            } catch (PDOException $e) {
                $error = "An error occurred while processing your request. Please try again later.";
                log_error($e->getMessage()); // Log the error message for debugging
            }
        }
    }
}
?>

<head>
    <title>Log In | JCDA</title>
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
                        <div style="background: #378349 !important;" class="card-header py-3 text-center bg-primary">
                            <a href="index.php">
                                <span><img src="assets/images/jcda-white.png" alt="logo" height="22" style="width: 60px;height: 60px;"></span>
                                <p class="text-muted" style="color: white !important;margin-bottom: 0px;margin-top: 15px;">JCDA USER LOGIN.</p>
                            </a>
                        </div>

                        <div class="card-body p-4">

                            <div class="text-center w-75 m-auto">
                                <h4 class="text-dark-50 text-center pb-0 fw-bold">Sign In</h4>
                                <p class="text-muted mb-4">Enter your email address and password to access your account.</p>
                            </div>

                            <form action="login.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <strong>Login Failed - </strong> <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="emailaddress" class="form-label">Email address</label>
                                    <input class="form-control" type="text" id="username" name="username" required="" placeholder="Enter your email">
                                </div>

                                <div class="mb-3">
                                    <a href="forgot_password.php" class="text-muted float-end fs-12">Forgot your password?</a>
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password">
                                        <div class="input-group-text" data-password="false">
                                            <span class="password-eye"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember" checked>
                                        <label class="form-check-label" for="checkbox-signin">Remember me</label>
                                    </div>
                                </div>

                                <div class="mb-3 mb-0 text-center">
                                    <button class="btn btn-primary" type="submit" style="width: 100%;"> Log In </button>
                                </div>

                            </form>
                        </div> <!-- end card-body -->
                    </div>
                    <!-- end card -->

                    <div class="row mt-3">
                        <div class="col-12 text-center">
                            <p class="text-muted bg-body">Don't have an account? <a href="register.php" class="text-muted ms-1 link-offset-3 text-decoration-underline"><b>Sign Up</b></a></p>
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
            <script>
                document.write(new Date().getFullYear())
            </script> © JCDA
        </span>
    </footer>
    <?php include 'layouts/footer-scripts.php'; ?>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

</body>

</html>