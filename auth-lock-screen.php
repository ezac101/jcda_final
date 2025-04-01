<?php include 'layouts/session.php'; ?>
<?php include 'layouts/main.php'; ?>

    <head>
        <title>Lock Screen | Attex - Bootstrap 5 Admin & Dashboard Template</title>
    <?php include 'layouts/title-meta.php'; ?>

    <?php include 'layouts/head-css.php'; ?>
    </head>

    <body class="authentication-bg">

    <?php include 'layouts/background.php'; ?>

        <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xxl-4 col-lg-5">
                        <div class="card">
                            <!-- Logo -->
                            <div class="card-header py-4 text-center bg-primary">
                                <a href="index.php">
                                    <span><img src="assets/images/logo.png" alt="logo" height="22"></span>
                                </a>
                            </div>

                            <div class="card-body p-4">
                                
                                <div class="text-center w-75 m-auto">
                                    <img src="assets/images/users/avatar-1.jpg" height="64" alt="user-image" class="rounded-circle shadow">
                                    <h4 class="text-dark-50 text-center mt-3 fw-bold">Hi ! Tosha </h4>
                                    <p class="text-muted mb-4">Enter your password to access the admin.</p>
                                </div>

                                <form action="#">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input class="form-control" type="password" required="" id="password" placeholder="Enter your password">
                                    </div>

                                    <div class="mb-0 text-center">
                                        <button class="btn btn-primary" type="submit">Log In</button>
                                    </div>
                                </form>
                                
                            </div> <!-- end card-body-->
                        </div>
                        <!-- end card-->

                        <div class="row mt-3">
                            <div class="col-12 text-center">
                                <p class="text-muted bg-body">Not you? return <a href="auth-login.php" class="text-muted ms-1 link-offset-3 text-decoration-underline"><b>Sign In</b></a></p>
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
            <span class="bg-body"><script>document.write(new Date().getFullYear())</script> © Attex - Coderthemes.com</span>
        </footer>

        <?php include 'layouts/footer-scripts.php'; ?>
        
        <!-- App js -->
        <script src="assets/js/app.min.js"></script>

    </body>
</html>
