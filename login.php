<?php
// login.php - Secure login for Members and Admins
require_once 'config.php';

// If already logged in, redirect accordingly
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin.php');
    } else {
        redirect('member.php');
    }
}

$error = '';
$success = '';

if (isset($_SESSION['register_success'])) {
    $success = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set sessions
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_status'] = $user['status'];

                if ($user['role'] === 'admin') {
                    redirect('admin.php');
                } else {
                    redirect('member.php');
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "System error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SGIPC Club Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">SGIPC<span>{ }</span></a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                
                <!-- Dropdown -->
                <li class="dropdown">
                    <a href="#" class="dropdown-trigger">People ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="people.php?view=administration">Administration</a></li>
                        <li><a href="people.php?view=members">Members</a></li>
                    </ul>
                </li>
                
                <li><a href="standings.php">Standings</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php" class="active">Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Form Section -->
    <div class="container">
        <div class="glass-card form-box" style="margin-top: 60px;">
            <h2 class="form-title">Portal Login</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span>✅</span> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="enter your registered email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    Log In
                </button>
            </form>

            <div class="form-note">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer" style="margin-top: 100px;">
        <div class="container">
            <div class="footer-logo">SGIPC</div>
            <p>&copy; <?php echo date('Y'); ?> Special Group Interested in Programming Contest. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
