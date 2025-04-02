
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

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
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Add security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com');

// CSRF token generation - use existing or generate new
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Invalid CSRF token.";
    } else {
        $token = sanitize_input($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate inputs
        if (empty($token) || empty($password) || empty($confirm_password)) {
            $error = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            try {
                // Use prepared statements with parameter binding
                $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token AND expiry > NOW() LIMIT 1");
                $stmt->execute(['token' => $token]);
                $reset = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($reset) {
                    // Hash the new password with appropriate cost
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

                    // Begin transaction for atomic operations
                    $pdo->beginTransaction();

                    try {
                        // Update the user's password
                        $stmt = $pdo->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE email = :email");
                        $stmt->execute(['password' => $hashed_password, 'email' => $reset['email']]);

                        // Delete the reset token
                        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
                        $stmt->execute(['token' => $token]);

                        // Commit transaction
                        $pdo->commit();

                        // Clear session data related to password reset
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        $success = "Your password has been reset successfully. You can now login.";
                    } catch (PDOException $e) {
                        // Roll back transaction on error
                        $pdo->rollBack();
                        throw $e;
                    }
                } else {
                    $error = "Invalid or expired token.";
                }
            } catch (PDOException $e) {
                $error = "An error occurred while processing your request. Please try again later.";
                log_error($e->getMessage()); // Log the error message for debugging
            }
        }
    }
}

/**
 * Validate password strength
 * @param string $password
 * @return bool
 */
function validate_password_strength($password)
{
    // Password must be at least 8 characters long and include:
    // - Uppercase letter
    // - Lowercase letter
    // - Number
    // - Special character
    return strlen($password) >= 8 &&
        preg_match('/[A-Z]/', $password) &&
        preg_match('/[a-z]/', $password) &&
        preg_match('/[0-9]/', $password) &&
        preg_match('/[^A-Za-z0-9]/', $password);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset</title>
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

        .password-strength span.weak {
            width: 33.33%;
            background-color: #e74c3c;
        }

        .password-strength span.medium {
            width: 66.66%;
            background-color: #f39c12;
        }

        .password-strength span.strong {
            width: 100%;
            background-color: #27ae60;
        }

        .password-hints {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
    </style>

<?php if (!empty($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hide the reset form
            document.getElementById('reset-form').style.display = 'none';
            
            // Create and show redirect message
            const redirectDiv = document.createElement('div');
            redirectDiv.id = 'redirectParent';
            redirectDiv.className = 'alert alert-success text-center';
            redirectDiv.innerHTML = '<?php echo addslashes($success); ?>';
            
            // Insert after the card header
            const cardBody = document.querySelector('.card-body');
            cardBody.insertBefore(redirectDiv, cardBody.firstChild);
        });
    </script>
<?php endif; ?>
</head>
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


                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST"
                                id="reset-form" novalidate>
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="token"
                                    value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="password" name="password" class="form-control"
                                            placeholder="Enter your new password">
                                        <div class="input-group-text" data-password="false">
                                            <span class="password-eye"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="password-strength">
                                    <span id="password-strength-meter"></span>
                                </div>

                                <p class="text-muted">
                                    Password must be at least 8 characters long with a mix of uppercase, lowercase,
                                    numbers, and special characters.
                                </p>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Confirm password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="confirm_password" name="confirm_password"
                                            class="form-control" placeholder="Enter your password again">
                                        <div class="input-group-text" data-password="false">
                                            <span class="password-eye"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-0 text-center">
                                    <button class="btn btn-primary" type="submit" style="width: 100%;"
                                        id="reset-button">Reset
                                        Password</button>
                                </div>
                            </form>

                            <p id="redirectParent" class="text-center">Go to your account <a href="login.php" class="text-muted ms-1 link-offset-3 text-decoration-underline"><b>Login</b></a></p>
                        </div>
                    </div>
                    <!-- end card -->

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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Cache DOM elements
            const form = document.getElementById('reset-form');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrengthMeter = document.getElementById('password-strength-meter');
            const resetButton = document.getElementById('reset-button');
            const togglePasswordVisibility = document.getElementById('toggle-password-visibility');

            // Password validation criteria
            const criteria = {
                length: { regex: /.{8,}/, weight: 1 },
                uppercase: { regex: /[A-Z]/, weight: 1 },
                lowercase: { regex: /[a-z]/, weight: 1 },
                numbers: { regex: /[0-9]/, weight: 1 },
                special: { regex: /[^A-Za-z0-9]/, weight: 1 }
            };

            // Functions
            function validatePassword() {
    const value = passwordInput.value;
    let meetsRequirements = true;
    
    // Check all requirements (same as PHP)
    const hasUppercase = /[A-Z]/.test(value);
    const hasLowercase = /[a-z]/.test(value);
    const hasNumber = /[0-9]/.test(value);
    const hasSpecial = /[^A-Za-z0-9]/.test(value);
    const hasLength = value.length >= 8;
    
    // Calculate how many requirements are met (out of 5 total)
    const requirementsMet = [hasUppercase, hasLowercase, hasNumber, hasSpecial, hasLength].filter(Boolean).length;
    const percentScore = (requirementsMet / 5) * 100;
    
    // Update UI
    passwordStrengthMeter.style.width = percentScore + '%';
    passwordStrengthMeter.className = '';
    
    // Adjusted thresholds (65-70% range)
    if (percentScore < 65) {  // Changed from 60 to 65
        passwordStrengthMeter.classList.add('weak');
        meetsRequirements = false;
    } else if (percentScore < 100) {
        passwordStrengthMeter.classList.add('medium');
        meetsRequirements = percentScore >= 70; // Changed from 80 to 70
    } else {
        passwordStrengthMeter.classList.add('strong');
        meetsRequirements = true;
    }
    
    return meetsRequirements;
}

            function validateForm() {
                const isPasswordValid = validatePassword();
                const doPasswordsMatch = passwordInput.value === confirmPasswordInput.value;

                resetButton.disabled = !(isPasswordValid && doPasswordsMatch &&
                    passwordInput.value.length > 0 &&
                    confirmPasswordInput.value.length > 0);
            }

            // Event listeners
            passwordInput.addEventListener('input', validateForm);
            confirmPasswordInput.addEventListener('input', validateForm);

            togglePasswordVisibility.addEventListener('change', function () {
                const type = this.checked ? 'text' : 'password';
                passwordInput.type = type;
                confirmPasswordInput.type = type;
            });

            form.addEventListener('submit', function (e) {
                if (!validatePassword()) {
                    e.preventDefault();
                    alert('Please choose a stronger password.');
                }
            });

            // Initial validation
            validateForm();
        });
    </script>

</body>

</html>