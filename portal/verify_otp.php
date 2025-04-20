<?php
// Start session first, before any output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';
$debug_output = ''; // For development debugging

// Check if pending_email exists in session
if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = sanitize_input($_POST['otp']);
    $email = $_SESSION['pending_email'];

    if (empty($otp)) {
        $error = "Please enter the OTP.";
    } else {
        try {
            // STEP 1: Fetch the record first without conditions to see what we have
            $stmt = $pdo->prepare("SELECT * FROM pending_registrations WHERE email = ? ORDER BY otp_expiry DESC LIMIT 1");
            $stmt->execute([$email]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // For debugging only - remove in production
            /*
            $debug_output = "Submitted OTP: '$otp' (type: ".gettype($otp).")<br>";
            if ($registration) {
                $debug_output .= "DB OTP: '".$registration['otp']."' (type: ".gettype($registration['otp']).")<br>";
                $debug_output .= "Current time: ".date('Y-m-d H:i:s')."<br>";
                $debug_output .= "Expiry time: ".$registration['otp_expiry']."<br>";
                $debug_output .= "Compare: ".($registration['otp'] === $otp ? "MATCH" : "NO MATCH")."<br>";
                $debug_output .= "Expired: ".($registration['otp_expiry'] < date('Y-m-d H:i:s') ? "YES" : "NO")."<br>";
            } else {
                $debug_output .= "No registration found for email: $email";
            }
            */
            
            // STEP 2: Now do the verification safely
            if ($registration) {
                // Use strict string comparison for OTP
                $db_otp = trim($registration['otp']); // Remove any whitespace
                $user_otp = trim($otp);
                
                $current_time = date('Y-m-d H:i:s');
                $is_expired = strtotime($registration['otp_expiry']) < strtotime($current_time);
                
                if (strcmp(trim($db_otp), trim($user_otp)) === 0 && !$is_expired) {
                    // OTP is valid, complete registration
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    if ($stmt->execute([$registration['username'], $registration['email'], $registration['password']])) {
                        // Delete from pending_registrations
                        $stmt = $pdo->prepare("DELETE FROM pending_registrations WHERE email = ?");
                        $stmt->execute([$email]);

                        $_SESSION['user_id'] = $pdo->lastInsertId();
                        $_SESSION['username'] = $registration['username'];
                        unset($_SESSION['pending_email']);
                        header("Location: index.php");
                        exit;
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                } else {
                    if ($db_otp !== $user_otp) {
                        $error = "Invalid OTP. Please check and try again.";
                    } else {
                        $error = "OTP has expired. Please request a new one.";
                    }
                }
            } else {
                $error = "No pending registration found. Please register again.";
            }
        } catch (PDOException $e) {
            $error = "An error occurred while processing your request. Please try again later.";
            log_error($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify OTP</title>
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
                        <div style="background: #783340 !important;" class="card-header py-3 text-center bg-primary">
                            <a href="index.php">
                                <span><img src="assets/images/jcda-white.png" alt="logo" height="22" style="width: 60px;height: 60px;"></span>
                                <p class="text-muted" style="color: white !important;margin-bottom: 0px;margin-top: 15px;">JCDA.</p>
                            </a>
                        </div>

                        <div class="card-body p-4">

                            <div class="text-center w-75 m-auto">
                                <h4 class="text-dark-50 text-center mt-0 fw-bold">Verify Your Email</h4>
                                <p class="text-muted mb-4">Please enter the OTP sent to your email.</p>
                            </div>
                            <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <strong>Error - </strong> <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>
                            <form action="verify_otp.php" method="POST">
                                <div class="mb-3">
                                    <label for="emailaddress" class="form-label">OTP</label>
                                    <input class="form-control" type="text" id="otp" name="otp" required="" placeholder="Enter OTP Code">
                                </div>

                                <div class="mb-0 text-center">
                                    <button class="btn btn-primary" type="submit" style="width: 100%;">Verify</button>
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
            </script> © JCDA
        </span>
    </footer>
    <?php include 'layouts/footer-scripts.php'; ?>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

</body>

</html>