
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

// Handle search
$search = '';
$params = [];
$where_clause = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $where_clause = " WHERE username LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total user count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users" . $where_clause);
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Get users for current page
$user_query = "SELECT * FROM users" . $where_clause . " ORDER BY id DESC LIMIT ?, ?";
$stmt = $pdo->prepare($user_query);

// Add pagination parameters
$pagination_params = array_merge($params, [$offset, $per_page]);

// PDO doesn't support binding LIMIT parameters directly, need to bind by position
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i]);
}
$stmt->bindValue(count($params) + 1, $offset, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $per_page, PDO::PARAM_INT);

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Management | JCDA Admin Portal</title>
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
                                        <li class="breadcrumb-item active">User Management</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">User Management</h4>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Display success/error messages -->
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="ri-check-double-line me-1"></i>
                            <?php echo $_SESSION['success']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ri-error-warning-line me-1"></i>
                            <?php echo $_SESSION['error']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Action buttons -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row justify-content-between">
                                        <div class="col-md-6">
                                            <form method="get" class="d-flex">
                                                <input type="text" name="search" class="form-control me-2" 
                                                       placeholder="Search by username or email" 
                                                       value="<?php echo htmlspecialchars($search); ?>">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-search-line"></i> Search
                                                </button>
                                            </form>
                                        </div>
                                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                            <a href="user-add.php" class="btn btn-success me-2">
                                                <i class="ri-user-add-line"></i> Add New User
                                            </a>
                                            <a href="reports.php?export=users" class="btn btn-info">
                                                <i class="ri-file-download-line"></i> Export Users
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">User List</h4>
                                    <p class="text-muted mb-0">
                                        Showing <?php echo min($total_users, $per_page); ?> of <?php echo $total_users; ?> users
                                    </p>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-centered table-striped table-hover dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Role</th>
                                                    <th>Registration Date</th>
                                                    <th>Profile</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(count($users) > 0): ?>
                                                    <?php foreach($users as $user): ?>
                                                        <?php
                                                        // Check if user has a profile with PDO
                                                        $profile_query = $pdo->prepare("SELECT updated FROM profiles WHERE user_id = ?");
                                                        $profile_query->execute([$user['id']]);
                                                        $profile_result = $profile_query->fetch(PDO::FETCH_ASSOC);
                                                        
                                                        if ($profile_result) {
                                                            $profile_status = $profile_result['updated'] ? 
                                                                '<span class="badge bg-success">Complete</span>' : 
                                                                '<span class="badge bg-warning">Incomplete</span>';
                                                        } else {
                                                            $profile_status = '<span class="badge bg-danger">Missing</span>';
                                                        }
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $user['id']; ?></td>
                                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['role'] ?? 'Member'); ?></td>
                                                            <td><?php echo date('Y-m-d H:i', strtotime($user['registration_date'])); ?></td>
                                                            <td><?php echo $profile_status; ?></td>
                                                            <td class="table-action">
                                                                <a href="user-view.php?id=<?php echo $user['id']; ?>" class="action-icon" title="View"> 
                                                                    <i class="ri-eye-line"></i>
                                                                </a>
                                                                <a href="user-edit.php?id=<?php echo $user['id']; ?>" class="action-icon" title="Edit"> 
                                                                    <i class="ri-pencil-line"></i>
                                                                </a>
                                                                <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')" class="action-icon" title="Delete"> 
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">No users found</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Pagination -->
                                <?php if($total_pages > 1): ?>
                                <div class="card-footer">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                        <i class="ri-arrow-left-s-line"></i> Previous
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            // Show limited page numbers with ellipsis
                                            $start_page = max(1, min($page - 2, $total_pages - 4));
                                            $end_page = min($total_pages, max($page + 2, 5));
                                            
                                            if ($start_page > 1) {
                                                echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . '">1</a></li>';
                                                if ($start_page > 2) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                            }
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++) {
                                                echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '">';
                                                echo '<a class="page-link" href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $i . '</a>';
                                                echo '</li>';
                                            }
                                            
                                            if ($end_page < $total_pages) {
                                                if ($end_page < $total_pages - 1) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $total_pages . '</a></li>';
                                            }
                                            ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                        Next <i class="ri-arrow-right-s-line"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
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
        // Enhanced delete confirmation function
        function confirmDelete(userId, username) {
            Swal.fire({
                title: 'Delete User',
                html: 'Are you sure you want to delete user <strong>' + username + '</strong>?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                showCloseButton: true,
                footer: '<a href="user-view.php?id=' + userId + '">View user details first</a>'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show second confirmation with delete options
                    Swal.fire({
                        title: 'Select Deletion Type',
                        html: '<div class="text-start">' +
                              '<div class="form-check mb-2">' +
                              '<input class="form-check-input" type="radio" name="deleteType" id="softDelete" value="soft" checked>' +
                              '<label class="form-check-label" for="softDelete">' +
                              '<strong>Soft Delete</strong> - Mark as deleted but keep records' +
                              '</label>' +
                              '</div>' +
                              '<div class="form-check">' +
                              '<input class="form-check-input" type="radio" name="deleteType" id="hardDelete" value="hard">' +
                              '<label class="form-check-label" for="hardDelete">' +
                              '<strong>Hard Delete</strong> - Permanently remove all user data' +
                              '</label>' +
                              '</div>' +
                              '</div>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Proceed with deletion',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Get delete type
                            const deleteType = document.querySelector('input[name="deleteType"]:checked').value;
                            
                            // Proceed to delete page with selected deletion type
                            window.location.href = 'user-delete.php?id=' + userId + '&type=' + deleteType;
                        }
                    });
                }
            });
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltips = document.querySelectorAll('[title]');
            tooltips.forEach(function(tooltip) {
                new bootstrap.Tooltip(tooltip);
            });
        });

        // Auto-dismiss alerts after 5 seconds
        window.setTimeout(function() {
            document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>