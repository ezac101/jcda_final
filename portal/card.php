
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if ($_SESSION['userLoggedIn'] == false) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$user_id = $result ? (int) $result['id'] : null;

// Fetch user information
$stmt = $pdo->prepare("SELECT u.email, p.surname, p.firstname, p.other_names, p.phone, p.occupation, p.profile_picture, p.membership_id_no, p.card_issue_date, p.card_expiry_date, p.card_issued FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default profile picture if none is set
$profile_picture = $user['profile_picture'] ?? '../assets/images/useravatar.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['membership_no'])) {
    $currentDate = date('Y-m-d');
    $expiryDate = date('Y-m-d', strtotime('+1 year'));

    $stmt = $pdo->prepare("
        UPDATE profiles 
        SET 
            card_issue_date = ?,
            card_expiry_date = ?,
            card_issued = 1 
        WHERE membership_id_no = ?
    ");
    $stmt->execute([$currentDate, $expiryDate, $_POST['membership_no']]);

    // Return success (or fetch updated user data if needed)
    echo json_encode(['status' => 'success']);
    exit;
}

// Check if membership is paid and not expired
$isPaid = false;
$expiryDate = null;

try {
    $stmt = $pdo->prepare("SELECT expiry_date FROM payments 
                          WHERE user_id = :user_id 
                          AND payment_status = 'success'
                          AND NOW() BETWEEN payment_date AND expiry_date
                          ORDER BY payment_date DESC 
                          LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $isPaid = true;
        $expiryDate = $result['expiry_date'];
    }
} catch (PDOException $e) {
    error_log("Error checking payment status: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <title>Dashboard | Attex - Bootstrap 5 Admin & Dashboard Template</title>
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
                                <h4 class="page-title">Membership Card</h4>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-9 col-lg-6">
                        <?php
                        // Safest check for NULL/0/"0" with VARCHAR field
                        $cardNotIssued = !isset($user['card_issued']) ||
                            $user['card_issued'] === null ||
                            $user['card_issued'] === '0' ||
                            $user['card_issued'] === 0 ||
                            $user['card_issued'] === '';

                        if ($cardNotIssued): ?>
                            <div class="card cta-box overflow-hidden">
                                <div class="card-body">
                                    <div class="d-flex align-items-center" style="justify-content: space-between;">
                                        <div>
                                            <h3 class="mt-0 fw-normal cta-box-title">Generate your membership card</h3>
                                            <p class="text-muted fs-14">Make sure the following requirements are fulfilled
                                                before you can generate your ID card</p>

                                            <?php if (!empty($user['surname'])): ?>
                                                <p><img src="assets/images/success.svg"
                                                        style="max-width: 20px;margin-right: 10px;" alt="">Profile Exists!</p>
                                            <?php else: ?>
                                                <p>
                                                    <img src="assets/images/pending.svg"
                                                        style="max-width: 20px;margin-right: 10px;" alt="">No Profile Found.
                                                    Your profile information must be complete
                                                    <a href="profile.php" class="btn btn-sm btn-outline-secondary"
                                                        style="margin-left: 10px;font-size: 12px;background: #7b8271;color: white;border: none;">Edit
                                                        profile</a>
                                                </p>
                                            <?php endif; ?>

                                            <?php if (!empty($user['profile_picture'])): ?>
                                                <p><img src="assets/images/success.svg"
                                                        style="max-width: 20px;margin-right: 10px;" alt="">Profile
                                                    Picture Available.</p>
                                            <?php else: ?>
                                                <p>
                                                    <img src="assets/images/pending.svg"
                                                        style="max-width: 20px;margin-right: 10px;" alt="">Profile
                                                    Picture must be set
                                                    <a href="profile.php" class="btn btn-sm btn-outline-secondary"
                                                        style="margin-left: 10px;font-size: 12px;background: #7b8271;color: white;border: none;">Upload
                                                        profile picture</a>
                                                </p>
                                            <?php endif; ?>

                                            <?php if ($isPaid): ?>
                                                <p>
                                                    <img src="assets/images/success.svg"
                                                        style="max-width: 20px;margin-right: 10px;" alt="">Membership dues
                                                    successfully paid
                                                </p>
                                            <?php else: ?>
                                                <p>
                                                    <img src="assets/images/pending.svg"
                                                        style="max-width: 20px;margin-right: 10px;" alt="">
                                                    Membership dues not paid
                                                    <a href="payment.php" class="btn btn-sm btn-outline-secondary"
                                                        style="margin-left: 10px;font-size: 12px;background: #7b8271;color: white;border: none;">
                                                        Pay Dues
                                                    </a>
                                                </p>
                                            <?php endif; ?>


                                            <?php if (!empty($user['surname']) && !empty($user['profile_picture'])): ?>
                                                <button type="button" id="generate-btn" style="background: none;border: none;"
                                                    class="link-success link-offset-3 fw-bold">Generate your card now<i
                                                        class="ri-arrow-right-line"></i></button>
                                            <?php endif; ?>
                                            <div id="card-data" style="display: none;">
                                                <span
                                                    id="fullname"><?php echo htmlspecialchars($user['firstname'] . (!empty($user['other_names']) ? ' ' . $user['other_names'] : '') . ' ' . $user['surname']); ?></span>
                                                <span
                                                    id="profile_picture"><?php echo htmlspecialchars($user['profile_picture'] ?? ''); ?></span>
                                                <span
                                                    id="occupation"><?php echo htmlspecialchars($user['occupation'] ?? ''); ?></span>
                                                <span
                                                    id="membership_no"><?php echo htmlspecialchars($user['membership_id_no'] ?? ''); ?></span>
                                                <span
                                                    id="card_issue_date"><?php echo htmlspecialchars($user['card_issue_date'] ?? ''); ?></span>
                                                <span
                                                    id="card_expiry_date"><?php echo htmlspecialchars($user['card_expiry_date'] ?? ''); ?></span>
                                            </div>

                                            <script>
                                                document.getElementById('generate-btn').addEventListener('click', function () {
    const membershipNo = document.getElementById('membership_no').textContent;

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'membership_no': membershipNo
        })
    })
    .then(response => response.text()) // Get response as text
    .then(text => {
        // Simple check if response contains "success" (case sensitive)
        if (text.includes('"success"')) {
            // Collect data for card-template.html
            const cardData = {
                fullname: document.getElementById('fullname').textContent,
                profile_picture: document.getElementById('profile_picture').textContent,
                membership_no: membershipNo,
                occupation: document.getElementById('occupation').textContent,
                card_issue_date: new Date().toISOString().split('T')[0],
                card_expiry_date: new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0]
            };

            // Create and submit the form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'card-template.php';

            Object.keys(cardData).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = cardData[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        } else {
            console.error('Server did not return success');
            alert('Failed to generate card. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to generate card. Please try again.');
    });
});
                                            </script>
                                        </div>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                        <?php else: ?>
                            <div class="card cta-box overflow-hidden">
                                <div class="card-body">
                                    <div class="d-flex align-items-center" style="justify-content: space-between;">
                                        <div>
                                            <h3 class="mt-0 fw-normal cta-box-title">View Membership Card</h3>
                                            <p class="text-muted fs-14">Your card has been successfully generated. Click
                                                below to preview and download it.</p>


                                            <button type="button" id="view-card" style="background: none;border: none;"
                                                class="link-success link-offset-3 fw-bold">View your card<i
                                                    class="ri-arrow-right-line"></i></button>
                                            <div id="card-data" style="display: none;">
                                                <span
                                                    id="fullname"><?php echo htmlspecialchars($user['firstname'] . (!empty($user['other_names']) ? ' ' . $user['other_names'] : '') . ' ' . $user['surname']); ?></span>
                                                <span
                                                    id="profile_picture"><?php echo htmlspecialchars($user['profile_picture'] ?? ''); ?></span>
                                                <span
                                                    id="occupation"><?php echo htmlspecialchars($user['occupation'] ?? ''); ?></span>
                                                <span
                                                    id="membership_no"><?php echo htmlspecialchars($user['membership_id_no'] ?? ''); ?></span>
                                                <span
                                                    id="card_issue_date"><?php echo htmlspecialchars($user['card_issue_date'] ?? ''); ?></span>
                                                <span
                                                    id="card_expiry_date"><?php echo htmlspecialchars($user['card_expiry_date'] ?? ''); ?></span>
                                            </div>

                                            <script>
                                                document.getElementById('view-card').addEventListener('click', function () {
                                                    // Collect all data from hidden spans
                                                    const cardData = {
                                                        fullname: document.getElementById('fullname').textContent,
                                                        profile_picture: document.getElementById('profile_picture').textContent,
                                                        membership_no: document.getElementById('membership_no').textContent,
                                                        occupation: document.getElementById('occupation').textContent,
                                                        card_issue_date: document.getElementById('card_issue_date').textContent,
                                                        card_expiry_date: document.getElementById('card_expiry_date').textContent
                                                    };

                                                    // Create a dynamic form
                                                    const form = document.createElement('form');
                                                    form.method = 'POST';
                                                    form.action = 'card-template.php';
                                                    form.style.display = 'none';

                                                    // Add all data as hidden inputs
                                                    Object.keys(cardData).forEach(key => {
                                                        const input = document.createElement('input');
                                                        input.type = 'hidden';
                                                        input.name = key;
                                                        input.value = cardData[key];
                                                        form.appendChild(input);
                                                    });

                                                    // Submit the form
                                                    document.body.appendChild(form);
                                                    form.submit();
                                                });
                                            </script>

                                        </div>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                        <?php endif; ?>
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