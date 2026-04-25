<?php
// register.php - Handles club registration
require_once 'config.php';

// If already logged in, redirect
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin.php');
    } else {
        redirect('member.php');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and read input
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $student_id = sanitize($_POST['student_id']);
    $department = sanitize($_POST['department']);
    $batch = sanitize($_POST['batch']);
    $codeforces_handle = sanitize($_POST['codeforces_handle']);
    $vjudge_handle = sanitize($_POST['vjudge_handle']);
    $programming_skills = sanitize($_POST['programming_skills']);
    $motivation = sanitize($_POST['motivation']);

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($student_id) || empty($department) || empty($batch)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "An account with this email already exists.";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new applicant (status is 'pending' by default)
                $insert = $pdo->prepare("INSERT INTO users (name, email, password, student_id, department, batch, codeforces_handle, vjudge_handle, programming_skills, motivation, status, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'member')");
                
                $insert->execute([
                    $name,
                    $email,
                    $hashed_password,
                    $student_id,
                    $department,
                    $batch,
                    $codeforces_handle,
                    $vjudge_handle,
                    $programming_skills,
                    $motivation
                ]);

                $_SESSION['register_success'] = "Registration submitted successfully! Please log in to view your application status.";
                redirect('login.php');
            }
        } catch (PDOException $e) {
            $error = "System error during registration: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Registration - SGIPC</title>
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
                <li><a href="register.php" class="active">Register</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Registration Section -->
    <div class="container">
        <div class="glass-card form-box" style="max-width: 750px; margin-top: 40px;">
            <h2 class="form-title">Join SGIPC</h2>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 30px; margin-top: -20px;">
                Apply to become a member of the Special Group Interested in Programming Contest.
            </p>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="registerForm">
                
                <h3 style="margin-bottom: 15px; font-size: 18px; color: var(--primary);">1. Personal Information</h3>
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="John Doe" required value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="johndoe@example.com" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="student_id">Student ID *</label>
                        <input type="text" name="student_id" id="student_id" class="form-control" placeholder="e.g. CSE-2026-004" required value="<?php echo isset($_POST['student_id']) ? sanitize($_POST['student_id']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department *</label>
                        <input type="text" name="department" id="department" class="form-control" placeholder="e.g. CSE" required value="<?php echo isset($_POST['department']) ? sanitize($_POST['department']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="batch">Batch *</label>
                        <input type="text" name="batch" id="batch" class="form-control" placeholder="e.g. 52nd" required value="<?php echo isset($_POST['batch']) ? sanitize($_POST['batch']) : ''; ?>">
                    </div>
                </div>

                <h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 18px; color: var(--primary);">2. Programming Profile</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="codeforces_handle">Codeforces Handle</label>
                        <input type="text" name="codeforces_handle" id="codeforces_handle" class="form-control" placeholder="e.g. tourist" value="<?php echo isset($_POST['codeforces_handle']) ? sanitize($_POST['codeforces_handle']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="vjudge_handle">Vjudge Handle</label>
                        <input type="text" name="vjudge_handle" id="vjudge_handle" class="form-control" placeholder="e.g. member_vj" value="<?php echo isset($_POST['vjudge_handle']) ? sanitize($_POST['vjudge_handle']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="programming_skills">Known Languages & Skills</label>
                    <textarea name="programming_skills" id="programming_skills" class="form-control" placeholder="e.g. C++, Java, Python. Comfortable with basic data structures, search algorithms."><?php echo isset($_POST['programming_skills']) ? sanitize($_POST['programming_skills']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="motivation">Why do you want to join SGIPC? *</label>
                    <textarea name="motivation" id="motivation" class="form-control" required placeholder="Describe your interest in competitive programming and contests..."><?php echo isset($_POST['motivation']) ? sanitize($_POST['motivation']) : ''; ?></textarea>
                </div>

                <h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 18px; color: var(--primary);">3. Security</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password * (min. 6 chars)</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                    Submit Application
                </button>
            </form>

            <div class="form-note">
                Already applied? <a href="login.php">Check status here</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer" style="margin-top: 80px;">
        <div class="container">
            <div class="footer-logo">SGIPC</div>
            <p>&copy; <?php echo date('Y'); ?> Special Group Interested in Programming Contest. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
