<?php include 'layouts/session.php'; ?>
<?php include 'layouts/main.php'; ?>

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

echo $user_id;
// Fetch user information
$stmt = $pdo->prepare("SELECT u.email, p.full_name, p.phone, p.profile_picture FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default profile picture if none is set
$profile_picture = $user['profile_picture'] ?? '../assets/images/useravatar.jpg';

// Set annual dues amount
$annual_dues = 5000; // ₦5,000


// Fetch payment records for this user
$payments = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = :user_id ORDER BY payment_date DESC");
    $stmt->execute([':user_id' => $user_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alert = 'Error fetching payment history: ' . $e->getMessage();
    $alert_class = 'danger';
}

// Initialize variables
$alert = '';
$alert_class = '';
$paystackSecretKey = PAYSTACK_SECRET_KEY;

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $user_id = $_POST['user_id'];
    $email = $_POST['email'];
    $amount = $_POST['amount'];

    // Generate a unique reference for this transaction
    $reference = 'JCDA_CARD_' . uniqid() . time();

    try {
        // Insert payment record with "cancelled" status
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_date, payment_status, gateway_trn_reference) 
                             VALUES (:user_id, :amount, NOW(), 'cancelled', :reference)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':amount' => $amount,
            ':reference' => $reference
        ]);

        // Store reference in session for verification later
        $_SESSION['payment_reference'] = $reference;

        // Initialize Paystack payment
        $url = "https://api.paystack.co/transaction/initialize";

        $fields = [
            'email' => $email,
            'amount' => $amount * 100, // Paystack uses amount in kobo
            'reference' => $reference,
            'callback_url' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] // Redirect back to this page
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $paystackSecretKey,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("Payment initialization failed: " . $err);
        }

        $result = json_decode($response);
        if ($result->status) {
            // Redirect to Paystack payment page
            header('Location: ' . $result->data->authorization_url);
            exit();
        } else {
            throw new Exception("Paystack Error: " . $result->message);
        }
    } catch (Exception $e) {
        $alert = 'Payment Failed. Please try again. Error: ' . $e->getMessage();
        $alert_class = 'danger';
    }
}

// Check for Paystack callback
if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "authorization: Bearer " . $paystackSecretKey,
            "cache-control: no-cache"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        $alert = 'Payment verification failed. Please try again.';
        $alert_class = 'danger';
    } else {
        $result = json_decode($response);

        if ($result->status && $result->data->status == 'success') {
            try {
                // Update payment record
                $stmt = $pdo->prepare("UPDATE payments 
                      SET payment_status = 'success', 
                          gateway_trn_reference = :gateway_ref,
                          expiry_date = DATE_ADD(NOW(), INTERVAL 1 YEAR)
                      WHERE gateway_trn_reference = :reference");
                $stmt->execute([
                    ':gateway_ref' => $result->data->reference,
                    ':reference' => $reference
                ]);

                $alert = 'Payment Successful.';
                $alert_class = 'success';
            } catch (PDOException $e) {
                $alert = 'Payment record update failed. Please contact support.';
                $alert_class = 'danger';
            }
        } else {
            $alert = 'Payment Failed. Please try again.';
            $alert_class = 'danger';
        }
    }
    if ($alert) {
        $_SESSION['alert'] = $alert;
        $_SESSION['alert_class'] = $alert_class;
        // Redirect to clear the ?reference from URL
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
    }
}

// Check for stored alert in session (display once)
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    $alert_class = $_SESSION['alert_class'];
    unset($_SESSION['alert']); // Clear after showing
    unset($_SESSION['alert_class']);
}
?>

<head>

    <title>Dues</title>
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
                                <h4 class="page-title">Membership Dues</h4>
                            </div>
                        </div>
                    </div>

                    <?php if ($alert): ?>
                        <div class="alert alert-<?php echo $alert_class; ?>"><?php echo $alert; ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xl-5 col-lg-6">
                            <div class="card cta-box overflow-hidden" style="background: #2f7740;">
                            <?php
                            // Check for active payment and get expiry date
                            $activePayment = null;
                            try {
                                $stmt = $pdo->prepare("SELECT expiry_date FROM payments 
                                    WHERE user_id = :user_id 
                                    AND payment_status = 'success'
                                    AND NOW() BETWEEN payment_date AND expiry_date
                                    ORDER BY payment_date DESC 
                                    LIMIT 1");
                                $stmt->execute([':user_id' => $user_id]);
                                $activePayment = $stmt->fetch(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                error_log("Error checking payment status: " . $e->getMessage());
                            }
                            ?>

                            <?php if ($activePayment): ?>
                                <!-- Show payment confirmation with expiry date -->
                                <div
                                    style="background: white; padding: 15px; border-radius: 5px; padding-bottom: 5px; margin-bottom: 10px;">
                                    <h4>Dues Fully Paid.</h4>
                                    <p>You have no outstanding dues to be paid.</p>
                                    <p> <strong>Membership Expiry:
                                            <?php echo date('F j, Y', strtotime($activePayment['expiry_date'])); ?></strong></p>
                                </div>
                            <?php else: ?>
                                <!-- Show payment form -->
                                

                                <div class="card-body">
                                    <div class="d-flex align-items-center" style="justify-content: space-between;">
                                        <div>
                                            <h3 class="mt-0 fw-normal cta-box-title">Make payment now</h3>
                                            <p class="text-muted fs-14">Click below to pay your membership dues in the
                                                amount of <strong>₦<?php echo number_format($annual_dues, 2);?></strong></p>

                                                <form action="payment.php" method="POST">
                                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                                <input type="hidden" name="email" value="<?php echo $user['email']; ?>">
                                                <input type="hidden" name="amount" value="<?php echo $annual_dues; ?>">
                                                <button type="submit" class="link-success link-offset-3 fw-bold" style="background: none;border: none;">Pay now<i
                                                    class="ri-arrow-right-line"></i></button>
                                            </form>
                                        </div>
                                        <i class="bi bi-cash-coin ms-3 fs-20" style="font-size: 40px !important;"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                                <!-- end card-body -->
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Transaction History</h4>
                                    <p class="text-muted fs-14">Find below a list of all the payment regarding your
                                        membership dues.
                                    </p>

                                    <table id="scroll-vertical-datatable"
                                        class="table table-striped dt-responsive nowrap w-100">
                                        <thead>
                                            <tr style="background: #eaf0e9;">
                                                <th>S/N</th>
                                                <th>Reference Number</th>
                                                <th>Amount</th>
                                                <th>Payment Date/Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (empty($payments)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No payment records found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($payments as $index => $payment): ?>
                                <tr>
                                    <th scope="row"><?php echo $index + 1; ?></th>
                                    <td><?php echo htmlspecialchars($payment['gateway_trn_reference'] ?? 'N/A'); ?></td>
                                    <td>₦<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y H:i', strtotime($payment['payment_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php
                                        echo $payment['payment_status'] === 'success' ? 'success' :
                                            ($payment['payment_status'] === 'cancelled' ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo ucfirst($payment['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                                        </tbody>
                                    </table>

                                </div> <!-- end card body-->
                            </div> <!-- end card -->
                        </div><!-- end col-->
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

    <script>
        document.querySelector('form').addEventListener('submit', function (e) {
            // Get the submit button
            const submitBtn = this.querySelector('button[type="submit"]');

            // Disable the button and change text
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Redirecting...';
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    alert.remove();
                }, 5000); // 5000ms = 5 seconds
            }
        });
    </script>

</body>

</html>