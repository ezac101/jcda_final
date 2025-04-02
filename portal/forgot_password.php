<?php include 'layouts/session.php'; ?>
<?php include 'layouts/main.php'; ?>

<?php
// filepath: /Users/user/Desktop/JCDA3/dashboard/public/forgot_password.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require 'vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize session securely
if (session_status() == PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// Force HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    // Protocol-relative URL to maintain HTTP/HTTPS as appropriate
    $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirect_url");
    exit;
}

// Add security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: same-origin');

// CSRF token generation/validation
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

$error = '';
$success = '';
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token using constant-time comparison
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));

        if (empty($email)) {
            $error = "Email address is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Delete any existing reset tokens for this email
                    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
                    $stmt->execute(['email' => $email]);
                    
                    // Generate secure token and set expiration
                    $reset_token = bin2hex(random_bytes(32));
                    $reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    // Store token in database
                    $stmt = $pdo->prepare(
                        "INSERT INTO password_resets (email, token, expiry, created_at) 
                         VALUES (:email, :token, :expiry, NOW())"
                    );
                    
                    if ($stmt->execute([
                        'email' => $email, 
                        'token' => $reset_token, 
                        'expiry' => $reset_expiry
                    ])) {
                        if (send_reset_email($email, $reset_token)) {
                            $success = "Password reset instructions have been sent to your email address.";
                            $email_sent = true;
                            
                            // Generate new CSRF token after successful submission
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        } else {
                            $error = "Failed to send email. Please try again or contact support.";
                        }
                    } else {
                        $error = "An error occurred. Please try again later.";
                    }
                } else {
                    // Show same message even if email not found (security best practice)
                    // This prevents user enumeration attacks
                    $success = "If an account exists with this email, password reset instructions will be sent.";
                    $email_sent = true;
                    
                    // Add artificial delay to prevent timing attacks
                    usleep(rand(300000, 600000)); // 300-600ms delay
                }
            } catch (PDOException $e) {
                $error = "An error occurred. Please try again later.";
                log_error('Password reset error: ' . $e->getMessage());
            }
        }
    }
}

/**
 * Send password reset email
 * 
 * @param string $email Recipient email address
 * @param string $token Reset token
 * @return bool Success status
 */
function send_reset_email($email, $token) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = SMTP_PORT;
        
        // Rate limiting for security
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            ]
        ];

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        // Generate message
        $reset_url = 'https://' . $_SERVER['HTTP_HOST'] . '/dashboard/portal/reset_password.php?token=' . urlencode($token);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'JCDA Password Reset Request';
        $mail->Body = <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="https://jcda.com.ng/assets/images/logo.png" alt="JCDA Logo" style="max-width: 150px;">
            </div>
            <h2 style="color: #00a86b;">Password Reset Request</h2>
            <p>Hello,</p>
            <p>We received a request to reset your password for your JCDA account. Click the button below to reset your password:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$reset_url}" style="background-color: #00a86b; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Reset Your Password</a>
            </div>
            <p>This link will expire in 1 hour for security reasons.</p>
            <p>If you did not request a password reset, you can safely ignore this email. Someone may have entered your email address by mistake.</p>
            <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="font-size: 12px; color: #777; text-align: center;">This is an automated email from JCDA. Please do not reply to this email.</p>
        </div>
HTML;

        $mail->AltBody = <<<TEXT
        Password Reset Request
        
        Hello,
        
        We received a request to reset your password for your JCDA account. Please click the link below to reset your password:
        
        {$reset_url}
        
        This link will expire in 1 hour for security reasons.
        
        If you did not request a password reset, you can safely ignore this email. Someone may have entered your email address by mistake.
        
        This is an automated email from JCDA. Please do not reply to this email.
TEXT;

        $mail->send();
        return true;
    } catch (Exception $e) {
        log_error("Failed to send password reset email: {$mail->ErrorInfo}");
        return false;
    }
}
?>

<head>
    <title>Forgot Password</title>
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
                                <span><img src="assets/images/jcda-white.png" alt="logo" height="22"
                                        style="width: 60px;height: 60px;"></span>
                                <p class="text-muted"
                                    style="color: white !important;margin-bottom: 0px;margin-top: 15px;">JCDA.</p>
                            </a>
                        </div>

                        <div class="card-body p-4">

                            <div class="text-center w-75 m-auto">
                                <h4 class="text-dark-50 text-center mt-0 fw-bold">Reset Password</h4>
                                <p class="text-muted mb-4">Enter your email address and we'll send you an email with
                                    instructions to reset your password.</p>
                            </div>

                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <strong>Error - </strong> <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success" role="alert">
                                    <strong>Success - </strong> <?php echo htmlspecialchars($success); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!$email_sent): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="reset-form" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="mb-3">
                                    <label for="emailaddress" class="form-label">Email address</label>
                                    <input class="form-control" type="email" id="email" name="email" required="" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                        placeholder="Enter your email">
                                </div>

                                <div class="mb-0 text-center">
                                    <button class="btn btn-primary" type="submit" style="width: 100%;" >Reset
                                        Password</button>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- end card -->

                    <div class="row mt-3">
                        <div class="col-12 text-center">
                            <p class="text-muted bg-body">Back to <a href="login.php"
                                    class="text-muted ms-1 link-offset-3 text-decoration-underline"><b>Log In</b></a>
                            </p>
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
    <?php if (!$email_sent): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const emailInput = document.getElementById('email');
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Simple email validation
            if (!emailInput.value.trim()) {
                isValid = false;
                showError(emailInput, 'Email address is required');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
                isValid = false;
                showError(emailInput, 'Please enter a valid email address');
            } else {
                removeError(emailInput);
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Show error message
        function showError(input, message) {
            removeError(input);
            input.classList.add('is-invalid');
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            errorDiv.style.color = 'var(--error-color)';
            errorDiv.style.fontSize = '0.875rem';
            errorDiv.style.marginTop = '-0.5rem';
            errorDiv.style.marginBottom = '0.75rem';
            
            input.parentNode.insertBefore(errorDiv, input.nextSibling);
        }
        
        // Remove error message
        function removeError(input) {
            input.classList.remove('is-invalid');
            const errorMessage = input.nextElementSibling;
            if (errorMessage && errorMessage.className === 'error-message') {
                errorMessage.remove();
            }
        }
        
        // Validate on input
        emailInput.addEventListener('input', function() {
            if (this.value.trim()) {
                removeError(this);
            }
        });
        
        // Focus the email input on page load
        emailInput.focus();
    });
    </script>
    <?php endif; ?>

</body>

</html>