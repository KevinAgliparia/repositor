<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

?>

<?php include 'includes/head.php'; ?>

<?php include 'includes/navbar.php'; ?>

<!-- Main Content -->
<div class="container mt-5 main-content">
    <h1 class="mb-4">Manage Users</h1>
    <!-- Add New User Button -->
    <a href="add_user.php" class="btn btn-primary mb-3"><i class="bi bi-person-plus"></i> Add New User</a>
    
    <!-- User Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM users");
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['user_id']}</td>
                            <td>{$row['username']}</td>
                            <td>{$row['role']}</td>
                            <td>
                                <a href='edit_user.php?id={$row['user_id']}' class='btn btn-warning btn-sm'>
                                    <i class='bi bi-pencil'></i> Edit
                                </a>
                                <button class='btn btn-danger btn-sm' data-bs-toggle='modal' data-bs-target='#deleteModal-{$row['user_id']}'>
                                    <i class='bi bi-trash'></i> Delete
                                </button>
                            </td>
                        </tr>";

                    // Delete Modal
                    echo "<div class='modal fade' id='deleteModal-{$row['user_id']}' tabindex='-1' aria-labelledby='deleteModalLabel' aria-hidden='true'>
                            <div class='modal-dialog'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <h5 class='modal-title' id='deleteModalLabel'>Confirm Deletion</h5>
                                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                    </div>
                                    <div class='modal-body'>
                                        Are you sure you want to delete this user? This action cannot be undone.
                                    </div>
                                    <div class='modal-footer'>
                                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                                        <a href='delete_user.php?id={$row['user_id']}' class='btn btn-danger'>Delete</a>
                                    </div>
                                </div>
                            </div>
                        </div>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
