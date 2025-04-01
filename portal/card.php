<?php include 'layouts/session.php'; ?>
<?php include 'layouts/main.php'; ?>

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
                        <div class="card cta-box overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-center" style="justify-content: space-between;">
                                    <div>
                                        <h3 class="mt-0 fw-normal cta-box-title">Generate your membership card</h3>
                                        <p class="text-muted fs-14">Make sure the following requirements are fulfilled
                                            before you can generate your ID card</p>

                                        <p><img src="assets/images/success.svg"
                                                style="max-width: 20px;margin-right: 10px;" alt="">Profile Exists!</p>
                                        <p>
                                            <img src="assets/images/pending.svg"
                                                style="max-width: 20px;margin-right: 10px;" alt="">No Profile Found.
                                            Your profile information must be complete
                                            <a href="profile.php" class="btn btn-sm btn-outline-secondary"
                                                style="margin-left: 10px;font-size: 12px;background: #7b8271;color: white;border: none;">Edit
                                                profile</a>
                                        </p>


                                        <p><img src="assets/images/success.svg"
                                                style="max-width: 20px;margin-right: 10px;" alt="">Profile
                                            Picture Available.</p>
                                        <p>
                                            <img src="assets/images/pending.svg"
                                                style="max-width: 20px;margin-right: 10px;" alt="">Profile
                                            Picture must be set
                                            <a href="profile.php" class="btn btn-sm btn-outline-secondary"
                                                style="margin-left: 10px;font-size: 12px;background: #7b8271;color: white;border: none;">Upload
                                                profile picture</a>
                                        </p>


                                        <p>
                                            <img src="assets/images/success.svg"
                                                style="max-width: 20px;margin-right: 10px;" alt="">Membership dues
                                            successfully paid
                                        </p>
                                        <p>
                                            <img src="assets/images/pending.svg"
                                                style="max-width: 20px;margin-right: 10px;" alt="">
                                            Membership dues not paid
                                            <a href="payment.php" class="btn btn-sm btn-outline-secondary"
                                                style="margin-left: 10px;font-size: 12px;background: #7b8271;color: white;border: none;">
                                                Pay Dues
                                            </a>
                                        </p>



                                        <a href="#" class="link-success link-offset-3 fw-bold">Generate your card now<i
                                                class="ri-arrow-right-line"></i></a>
                                    </div>
                                </div>
                            </div>
                            <!-- end card-body -->
                        </div>

                        <div class="card cta-box overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-center" style="justify-content: space-between;">
                                    <div>
                                        <h3 class="mt-0 fw-normal cta-box-title">View Membership Card</h3>
                                        <p class="text-muted fs-14">Your card has been successfully generated. Click below to preview and download it.</p>


                                        <a href="#" class="link-success link-offset-3 fw-bold">View your card now<i
                                                class="ri-arrow-right-line"></i></a>
                                    </div>
                                </div>
                            </div>
                            <!-- end card-body -->
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