

<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require 'vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate input
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
                $stmt->execute(['username' => $username, 'email' => $email]);
                if ($stmt->rowCount() > 0) {
                    $error = "Username or email already exists.";
                } else {
                    // Generate OTP
                    $otp = rand(100000, 999999);
                    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert pending registration
                    $stmt = $pdo->prepare("INSERT INTO pending_registrations (username, email, password, otp, otp_expiry) VALUES (:username, :email, :password, :otp, :otp_expiry)");
                    if ($stmt->execute(['username' => $username, 'email' => $email, 'password' => $hashed_password, 'otp' => $otp, 'otp_expiry' => $otp_expiry])) {
                        // Send OTP to user's email
                        if (send_otp_email($email, $otp)) {
                            $_SESSION['pending_email'] = $email;
                            header("Location: verify_otp.php");
                            exit;
                        } else {
                            $error = "Failed to send verification email. Please try again.";
                        }
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                $error = "An error occurred while processing your request. Please try again later.";
                log_error($e->getMessage()); // Log the error message for debugging
            }
        }
    }
}

// Function to send OTP email using PHPMailer
function send_otp_email($email, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use 'ssl' if PHPMailer version < 6.1
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification for JCDA';
        $mail->Body    = "Your OTP for email verification is: $otp<br>This OTP will expire in 15 minutes.";
        $mail->AltBody = "Your OTP for email verification is: $otp\nThis OTP will expire in 15 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        log_error("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Register</title>
    <?php include 'layouts/title-meta.php'; ?>

    <?php include 'layouts/head-css.php'; ?>
    <style>
        .password-strength {
            margin-bottom: 20px;
        }
        .password-strength span {
            display: block;
            height: 4px;
            background-color: #ddd;
            border-radius: 2px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        .password-strength span.weak { width: 33.33%; background-color: #e74c3c; }
        .password-strength span.medium { width: 66.66%; background-color: #f39c12; }
        .password-strength span.strong { width: 100%; background-color: #27ae60; }
        .password-hints {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
    </style>
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
                                <p class="text-muted" style="color: white !important;margin-bottom: 0px;margin-top: 15px;">JCDA.</p>
                            </a>
                        </div>

                        <div class="card-body p-4">

                            <div class="text-center w-75 m-auto">
                                <h4 class="text-dark-50 text-center mt-0 fw-bold">Create an account</h4>
                                <p class="text-muted mb-4">Join Our Community.</p>
                            </div>

                            <form action="register.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <strong>Error - </strong> <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="fullname" class="form-label">Username</label>
                                    <input class="form-control" type="text" id="username" name="username" placeholder="Enter a username" required="">
                                </div>

                                <div class="mb-3">
                                    <label for="emailaddress" class="form-label">Email address</label>
                                    <input class="form-control" type="email" id="email" name="email" required="" placeholder="Enter your email">
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password">
                                        <div class="input-group-text" data-password="false">
                                            <span class="password-eye"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="password-strength" id="password-strength">
                                    <span></span>
                                </div>

                                <p class="text-muted">
                                        Password must be at least 8 characters long with a mix of uppercase, lowercase, numbers, and special characters.
                                    </p>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Confirm password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Enter your password again">
                                        <div class="input-group-text" data-password="false">
                                            <span class="password-eye"></span>
                                        </div>
                                    </div>
                                </div>
                                    
                                <div class="mb-3 text-center">
                                    <button class="btn btn-primary" type="submit" id="register-button"  style="width: 100%;"> Sign Up </button>
                                </div>

                            </form>
                        </div>
                    </div>
                    <!-- end card -->

                    <div class="row mt-3">
                        <div class="col-12 text-center">
                            <p class="text-muted bg-body">Back to <a href="login.php" class="text-muted ms-1 link-offset-3 text-decoration-underline"><b>Log In</b></a></p>
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
            </script> © JCDA.
        </span>
    </footer>
    <?php include 'layouts/footer-scripts.php'; ?>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordStrength = document.getElementById('password-strength');
        const registerButton = document.getElementById('register-button');

        passwordInput.addEventListener('input', function() {
            const value = passwordInput.value;
            let strength = 0;

            if (value.length >= 8) strength++;
            if (/[A-Z]/.test(value)) strength++;
            if (/[a-z]/.test(value)) strength++;
            if (/[0-9]/.test(value)) strength++;
            if (/[^A-Za-z0-9]/.test(value)) strength++;

            passwordStrength.innerHTML = '<span></span>';
            const span = passwordStrength.querySelector('span');

            if (strength < 3) {
                span.className = 'weak';
                registerButton.disabled = true;
            } else if (strength < 5) {
                span.className = 'medium';
                registerButton.disabled = false;
            } else {
                span.className = 'strong';
                registerButton.disabled = false;
            }
        });

        function togglePasswordVisibility(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling.querySelector('i');
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    </script>

</body>

</html>