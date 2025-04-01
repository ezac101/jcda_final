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
                                <h4 class="page-title">Welcome Tosha!</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-6 col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-16">
                                            <h5 class="text-uppercase fs-13 mt-0 text-truncate" title="Active Users">
                                                Profile Summary</h5>
                                            <h2 class="my-2 py-1 mb-0" id="active-users-count">Tosha Lydia</h2>
                                            <span class="text-nowrap">Web Developer</span>
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
                                            <h2 class="my-2 py-1 mb-0" id="active-users-count">Active</h2>
                                            <span class="text-wrap">Your annual membership expires on 24/09/2026</span>
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
                                            <a href="card.php" class="link-success link-offset-3 fw-bold">View card <i class="ri-arrow-right-line"></i></a>
                                        </div>
                                        <i class="bi bi-card-heading ms-3 fs-20" style="font-size: 40px !important;"></i>
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
                                            <a href="payment.php" class="link-success link-offset-3 fw-bold">Manage dues <i class="ri-arrow-right-line"></i></a>
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