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
                                <h4 class="page-title">Membership Dues</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-5 col-lg-6">
                            <div class="card cta-box overflow-hidden">
                                <div class="card-body">
                                    <div class="d-flex align-items-center" style="justify-content: space-between;">
                                        <div>
                                            <h3 class="mt-0 fw-normal cta-box-title">Make payment now</h3>
                                            <p class="text-muted fs-14">Click below to pay your membership dues in the amount of <strong>₦5,000.00</strong></p>
                                            <a href="#" class="link-success link-offset-3 fw-bold">Pay now<i
                                                    class="ri-arrow-right-line"></i></a>
                                        </div>
                                        <i class="bi bi-cash-coin ms-3 fs-20" style="font-size: 40px !important;"></i>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Transaction History</h4>
                                    <p class="text-muted fs-14">Find below a list of all the payment regarding your membership dues.
                                    </p>

                                    <table id="scroll-vertical-datatable" class="table table-striped dt-responsive nowrap w-100">
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
                                            <tr>
                                                <td>Tiger Nixon</td>
                                                <td>System Architect</td>
                                                <td>Edinburgh</td>
                                                <td>61</td>
                                                <td>2011/04/25</td>
                                            </tr>
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

</body>

</html>