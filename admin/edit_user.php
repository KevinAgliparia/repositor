<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$userId = $_GET['id'];

// Fetch user details from the database
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password']; // Only hash if new password
    $role = $_POST['role'];

    // Update user details in the database
    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $username, $password, $role, $userId);

    if ($stmt->execute()) {
        header("Location: users.php");
        exit;
    } else {
        $error = "Error updating user.";
    }
}
?>

<?php include 'includes/head.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4">Edit User</h1>
    <form method="POST">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password (Leave blank to keep current password)</label>
            <input type="password" id="password" name="password" class="form-control">
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select id="role" name="role" class="form-control" required>
                <option value="admin" <?php echo ($user['role'] == 'admin' ? 'selected' : ''); ?>>Admin</option>
                <option value="student" <?php echo ($user['role'] == 'student' ? 'selected' : ''); ?>>Student</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
        <?php if (isset($error)) echo "<div class='alert alert-danger mt-3'>$error</div>"; ?>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
