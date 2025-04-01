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
                                <h4 class="page-title">Profile Information</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">

                                    <form class="row">
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="simpleinput" class="form-label">Surname</label>
                                                <input type="text" id="simpleinput" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label for="example-date" class="form-label">Date of Birth</label>
                                                <input class="form-control" id="example-date" type="date" name="date">
                                            </div>
                                            <div class="mb-3">
                                                <label for="example-select" class="form-label">State</label>
                                                <select class="form-select" id="example-select">
                                                    <option>Male</option>
                                                    <option>Female</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="simpleinput" class="form-label">First Name</label>
                                                <input type="text" id="simpleinput" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label for="example-select" class="form-label">Gender</label>
                                                <select class="form-select" id="example-select">
                                                    <option>Male</option>
                                                    <option>Female</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="example-select" class="form-label">LGA</label>
                                                <select class="form-select" id="example-select">
                                                    <option>Male</option>
                                                    <option>Female</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="simpleinput" class="form-label">Last Name</label>
                                                <input type="text" id="simpleinput" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label for="simpleinput" class="form-label">Highest Academic
                                                    Qualification:</label>
                                                <input type="text" id="simpleinput" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label for="simpleinput" class="form-label">Street Address</label>
                                                <input type="text" id="simpleinput" class="form-control">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="example-fileinput" class="form-label">Profile Picture</label>
                                            <input type="file" id="example-fileinput" class="form-control">
                                        </div>

                                    </form>
                                    <!-- end row-->
                                </div> <!-- end card-body -->
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