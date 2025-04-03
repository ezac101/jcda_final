<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';

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
            $stmt = $pdo->prepare("SELECT * FROM pending_registrations WHERE email = ? AND otp = ? AND otp_expiry > NOW()");
            $stmt->execute([$email, $otp]);
            $user = $stmt->fetch();

            if ($user) {
                // OTP is valid, complete registration
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$user['username'], $user['email'], $user['password']])) {
                    // Delete from pending_registrations
                    $stmt = $pdo->prepare("DELETE FROM pending_registrations WHERE email = ?");
                    $stmt->execute([$email]);

                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $user['username'];
                    unset($_SESSION['pending_email']);
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Registration failed. Please try again.";
                }
            } else {
                $error = "Invalid or expired OTP. Please try again.";
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
                        <div style="background: #378349 !important;" class="card-header py-3 text-center bg-primary">
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