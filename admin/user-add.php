

<?php
// Include database connection
try {
    if (!file_exists('includes/db.php')) {
        throw new Exception("Database file not found at 'includes/db.php'");
    }
    require_once('includes/db.php');
    
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection not established. Check db.php file.");
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$errors = [];
$success_message = '';
$account_statuses = ['active', 'pending', 'suspended'];
$roles = ['admin', 'member', 'moderator'];

// Generate a random password
function generateRandomPassword($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

// Set default values
$username = '';
$email = '';
$role = 'member';
$account_status = 'active';
$send_welcome_email = false;
$create_profile = false;
$random_password = generateRandomPassword();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'member';
    $account_status = isset($_POST['account_status']) ? trim($_POST['account_status']) : 'active';
    $send_welcome_email = isset($_POST['send_welcome_email']);
    $create_profile = isset($_POST['create_profile']);
    
    // Use custom password or random password
    $use_random_password = isset($_POST['use_random_password']) && $_POST['use_random_password'] == '1';
    $password = $use_random_password ? $random_password : trim($_POST['password']);
    $confirm_password = $use_random_password ? $random_password : trim($_POST['confirm_password']);
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    }
    
    // Check if username already exists
    $check_username = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check_username->execute([$username]);
    if ($check_username->rowCount() > 0) {
        $errors[] = "Username already exists. Please choose a different one.";
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email already exists
    $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->execute([$email]);
    if ($check_email->rowCount() > 0) {
        $errors[] = "Email already exists. Please choose a different one.";
    }
    
    // Validate role
    if (!in_array($role, $roles)) {
        $errors[] = "Invalid role selected";
    }
    
    // Validate account status
    if (!in_array($account_status, $account_statuses)) {
        $errors[] = "Invalid account status selected";
    }
    
    // Validate password if not using random password
    if (!$use_random_password) {
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        
        // Check if passwords match
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
    }
    
    // If there are no errors, create the user
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Create new user
            $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, account_status, registration_date) 
                VALUES (?, ?, ?, ?, ?, NOW())");
            $insert_stmt->execute([$username, $email, $hashed_password, $role, $account_status]);
            
            $user_id = $pdo->lastInsertId();
            
            // Log admin action
            $admin_id = $_SESSION['admin_id'];
            $action = "Created new user: " . $username;
            $log_stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, user_id) VALUES (?, ?, ?)");
            $log_stmt->execute([$admin_id, $action, $user_id]);
            
            // Create empty profile if requested
            if ($create_profile) {
                $profile_stmt = $pdo->prepare("INSERT INTO profiles (user_id, updated) VALUES (?, 0)");
                $profile_stmt->execute([$user_id]);
            }
            
            // Send welcome email if requested
            if ($send_welcome_email) {
                // You can implement email sending here
                // For now, just log it
                $email_log_stmt = $pdo->prepare("INSERT INTO email_logs (recipient_id, email_type, status) VALUES (?, 'welcome', 'queued')");
                $email_log_stmt->execute([$user_id]);
            }
            
            $pdo->commit();
            
            // Set success message
            if ($use_random_password) {
                $success_message = "User created successfully! The temporary password is: <strong>" . htmlspecialchars($random_password) . "</strong>";
            } else {
                $success_message = "User created successfully!";
            }
            
            // Reset form fields for new submission
            if (!$use_random_password) {
                $username = '';
                $email = '';
                $role = 'member';
                $account_status = 'active';
                $send_welcome_email = false;
                $create_profile = false;
                $random_password = generateRandomPassword(); // Generate new random password
            }
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
            error_log("User creation error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New User | JCDA Admin Portal</title>
    <?php include 'layouts/title-meta.php'; ?>
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

                    <!-- Page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                                        <li class="breadcrumb-item active">Add User</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Add New User</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Create User Account</h4>
                                    <p class="text-muted mb-0">Create a new user account by filling out the form below.</p>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($success_message)): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="ri-check-double-line me-1"></i>
                                            <?php echo $success_message; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <ul class="mb-0">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?php echo $error; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <form method="post" action="user-add.php">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                                    <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                                                    <small class="text-muted">Must be unique, 3-50 characters</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                                    <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="role" class="form-label">User Role</label>
                                                    <select name="role" id="role" class="form-select">
                                                        <?php foreach ($roles as $role_option): ?>
                                                            <option value="<?php echo $role_option; ?>" <?php echo ($role === $role_option) ? 'selected' : ''; ?>>
                                                                <?php echo ucfirst($role_option); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="account_status" class="form-label">Account Status</label>
                                                    <select name="account_status" id="account_status" class="form-select">
                                                        <?php foreach ($account_statuses as $status): ?>
                                                            <option value="<?php echo $status; ?>" <?php echo ($account_status === $status) ? 'selected' : ''; ?>>
                                                                <?php echo ucfirst($status); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="use_random_password" name="use_random_password" value="1" onchange="togglePasswordFields(this.checked)">
                                                <label class="form-check-label" for="use_random_password">Generate random password</label>
                                            </div>
                                        </div>

                                        <div id="password_fields">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                                        <input type="password" name="password" id="password" class="form-control" minlength="8">
                                                        <small class="text-muted">Minimum 8 characters</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="random_password_info" style="display: none;">
                                            <div class="alert alert-info" role="alert">
                                                <h5 class="alert-heading">Random Password</h5>
                                                <p class="mb-0">A secure random password will be generated when the user is created. You will see it after submission.</p>
                                            </div>
                                        </div>

                                        <div class="mt-4 mb-3">
                                            <h5>Additional Options</h5>
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" id="send_welcome_email" name="send_welcome_email" <?php echo $send_welcome_email ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="send_welcome_email">Send welcome email with login details</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="create_profile" name="create_profile" <?php echo $create_profile ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="create_profile">Create profile record for this user</label>
                                                <small class="d-block text-muted">This will create an empty profile that the user can complete later.</small>
                                            </div>
                                        </div>

                                        <div class="text-end">
                                            <a href="users.php" class="btn btn-secondary me-1">Cancel</a>
                                            <button type="submit" class="btn btn-primary">Create User</button>
                                        </div>
                                    </form>
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

    <script>
        // Toggle password fields based on random password checkbox
        function togglePasswordFields(checked) {
            document.getElementById('password_fields').style.display = checked ? 'none' : 'block';
            document.getElementById('random_password_info').style.display = checked ? 'block' : 'none';
            
            // Update required attribute on password fields
            const passwordField = document.getElementById('password');
            const confirmField = document.getElementById('confirm_password');
            
            if (checked) {
                passwordField.removeAttribute('required');
                confirmField.removeAttribute('required');
            } else {
                passwordField.setAttribute('required', 'required');
                confirmField.setAttribute('required', 'required');
            }
        }
        
        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>