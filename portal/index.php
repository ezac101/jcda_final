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

// Regenerate session ID to prevent session fixation
// if (!isset($_SESSION['regenerated'])) {
//     session_regenerate_id(true);
//     $_SESSION['regenerated'] = true;
// }

// Check if user is logged in
if ($_SESSION['userLoggedIn'] == false) {
    header("Location: login.php");
    exit;
}

// Implement session timeout
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
$_SESSION['last_activity'] = time();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];


// Fetch user profile information
$stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch latest payment information
$stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_payment = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default profile picture if none is set
$profile_picture = $profile['profile_picture'] ?? 'assets/images/useravatar.jpg';

// Query to get the most recent payment (regardless of status)
$query = "SELECT expiry_date, payment_status 
          FROM payments 
          WHERE user_id = :user_id
          ORDER BY payment_date DESC 
          LIMIT 1";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine status
if ($payment) {
    if (!empty($payment['expiry_date'])) {
        // Has expiry date - check if active or expired
        $expiryDate = new DateTime($payment['expiry_date']);
        $currentDate = new DateTime();

        $status = ($expiryDate > $currentDate) ? 'Active' : 'Expired';
        $formattedDate = $expiryDate->format('d/m/Y');
    } else {
        // Payment exists but has no expiry date
        $status = 'Pending';
        $formattedDate = 'Payment processing';
    }
} else {
    // No payment records exist at all
    $status = 'Pending';
    $formattedDate = 'No payment submitted';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <title>Dashboard | JCDA</title>
    <?php include 'layouts/title-meta.php'; ?>

    <!-- Daterangepicker css -->
    <link rel="stylesheet" href="assets/vendor/daterangepicker/daterangepicker.css">

    <!-- Vector Map css -->
    <link rel="stylesheet" href="assets/vendor/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css">

    <?php include 'layouts/head-css.php'; ?>
</head>

<body>
    <!-- Begin page -->
    <div class="wrapper">

        <?php include 'layouts/menu.php'; ?>

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <form class="d-flex">
                                        <a href="" class="btn btn-primary ms-2">
                                            <i class="ri-refresh-line"></i>
                                        </a>
                                    </form>
                                </div>
                                <h4 class="page-title">Welcome!</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-6 col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-16">

                                            <?php if ($profile): ?>

                                                <h5 class="text-uppercase fs-13 mt-0 text-truncate" title="Active Users">
                                                    Profile Summary</h5>
                                                <h2 class="my-2 py-1 mb-0" id="active-users-count">
                                                    <?php echo htmlspecialchars($profile['firstname'] . (!empty($profile['other_names']) ? ' ' . $profile['other_names'] : '') . ' ' . $profile['surname']); ?>
                                                </h2>
                                                <span
                                                    class="text-nowrap"><?php echo htmlspecialchars($profile['occupation']); ?></span>
                                            <?php else: ?>
                                                <p class="text-nowrap mb-1" style="font-size: 26px;">Profile incomplete..
                                                </p>
                                                <p class="text-nowrap mb-1">Click below to complete your profile.</p>
                                                <a href="profile.php" class="link-success link-offset-3 fw-bold">Edit
                                                    profile <i class="ri-arrow-right-line"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-12">
                                            <h5 class="text-uppercase fs-13 mt-0 text-truncate" title="Active Users">
                                                Membership Status</h5>
                                            <h2 class="my-2 py-1 mb-0" id="active-users-count"><?php echo $status; ?>
                                            </h2>
                                            <span class="text-wrap"><?php if ($status === 'Pending'): ?>
                                                    Your membership is pending
                                                <?php else: ?>
                                                    Your membership
                                                    <?php echo $status === 'Active' ? 'expires' : 'expired'; ?> on
                                                    <?php echo htmlspecialchars($formattedDate); ?>
                                                <?php endif; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>





                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                </div>
                                <h4 class="page-title">Actions</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-5 col-lg-6">
                            <div class="card cta-box overflow-hidden">
                                <div class="card-body">
                                    <div class="d-flex align-items-center" style="justify-content: space-between;">
                                        <div>
                                            <h3 class="mt-0 fw-normal cta-box-title">View your ID card</h3>
                                            <a href="card.php" class="link-success link-offset-3 fw-bold">View card <i
                                                    class="ri-arrow-right-line"></i></a>
                                        </div>
                                        <i class="bi bi-card-heading ms-3 fs-20"
                                            style="font-size: 40px !important;"></i>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                        </div>
                        <div class="col-xl-5 col-lg-6">
                            <div class="card cta-box overflow-hidden">
                                <div class="card-body">
                                    <div class="d-flex align-items-center" style="justify-content: space-between;">
                                        <div>
                                            <h3 class="mt-0 fw-normal cta-box-title">Manage your membership dues</h3>
                                            <a href="payment.php" class="link-success link-offset-3 fw-bold">Manage dues
                                                <i class="ri-arrow-right-line"></i></a>
                                        </div>
                                        <i class="bi bi-cash-coin ms-3 fs-20" style="font-size: 40px !important;"></i>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- container -->

        </div>
        <!-- content -->

        <?php include 'layouts/footer.php'; ?>

    </div>

    <!-- ============================================================== -->
    <!-- End Page content -->
    <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <?php include 'layouts/right-sidebar.php'; ?>

    <?php include 'layouts/footer-scripts.php'; ?>

    <!-- Daterangepicker js -->
    <script src="assets/vendor/daterangepicker/moment.min.js"></script>
    <script src="assets/vendor/daterangepicker/daterangepicker.js"></script>

    <!-- Apex Charts js -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>

    <!-- Vector Map js -->
    <script src="assets/vendor/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js"></script>
    <script src="assets/vendor/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js"></script>

    <!-- Dashboard App js -->
    <script src="assets/js/pages/demo.dashboard.js"></script>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

</body>

</html>