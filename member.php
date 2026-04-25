<?php
// member.php - Member panel
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Redirect admin to admin panel
if (is_admin()) {
    redirect('admin.php');
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle profile updates (for approved members)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $cf_handle = sanitize($_POST['codeforces_handle']);
    $vj_handle = sanitize($_POST['vjudge_handle']);
    $skills = sanitize($_POST['programming_skills']);

    try {
        $update = $pdo->prepare("UPDATE users SET codeforces_handle = ?, vjudge_handle = ?, programming_skills = ? WHERE id = ? AND status = 'approved'");
        $update->execute([$cf_handle, $vj_handle, $skills, $userId]);
        $success = "Profile updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Fetch current user details from database (ensuring fresh status check)
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirect('logout.php');
    }
    
    // Update session status just in case
    $_SESSION['user_status'] = $user['status'];
    
    // Fetch resources if approved
    $resources = [];
    if ($user['status'] === 'approved') {
        $res_stmt = $pdo->query("SELECT * FROM resources ORDER BY id DESC");
        $resources = $res_stmt->fetchAll();
    }
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Panel - SGIPC</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-profile">
                <img src="https://api.dicebear.com/7.x/bottts/svg?seed=<?php echo urlencode($user['name']); ?>" alt="Avatar" class="sidebar-avatar">
                <div class="sidebar-name"><?php echo sanitize($user['name']); ?></div>
                <div class="sidebar-role">
                    <?php if ($user['status'] === 'approved'): ?>
                        <?php echo $user['designation'] ? sanitize($user['designation']) : 'Club Member'; ?>
                    <?php else: ?>
                        Applicant
                    <?php endif; ?>
                </div>
                <div style="margin-top: 10px;">
                    <?php if ($user['status'] === 'pending'): ?>
                        <span class="status-badge status-pending">Pending</span>
                    <?php elseif ($user['status'] === 'approved'): ?>
                        <span class="status-badge status-approved">Approved</span>
                    <?php else: ?>
                        <span class="status-badge status-rejected">Rejected</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li class="active"><a href="member.php">Dashboard</a></li>
                <li><a href="standings.php">Global Standings</a></li>
                <li><a href="people.php?view=members">Members Directory</a></li>
                <li><a href="index.php">Public Home</a></li>
                <li style="margin-top: auto;"><a href="logout.php" class="logout-link">Log Out</a></li>
            </ul>
        </aside>

        <!-- Main Content Panel -->
        <main class="main-content">
            <header style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 28px;">Member Dashboard</h1>
                    <p style="color: var(--text-muted);">Welcome back, <?php echo sanitize($user['name']); ?>.</p>
                </div>
                <div class="code-font" style="color: var(--primary);">
                    const int member_id = <?php echo $user['id']; ?>;
                </div>
            </header>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span>✅</span> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($user['status'] === 'pending'): ?>
                <!-- PENDING VIEW -->
                <div class="glass-card" style="border-left: 4px solid var(--warning);">
                    <h3 style="color: var(--warning); margin-bottom: 12px; font-size: 20px;">⏳ Application Under Review</h3>
                    <p style="margin-bottom: 20px;">
                        Thank you for applying to SGIPC! Your registration request has been submitted and is currently being processed by the executive administration.
                    </p>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 10px;">
                        <strong>Submitted Details:</strong>
                    </p>
                    <ul style="list-style: none; font-size: 14px; color: var(--text-muted); line-height: 2;">
                        <li><strong>Student ID:</strong> <?php echo sanitize($user['student_id']); ?></li>
                        <li><strong>Department & Batch:</strong> <?php echo sanitize($user['department']); ?>, Batch <?php echo sanitize($user['batch']); ?></li>
                        <li><strong>Codeforces Handle:</strong> <?php echo $user['codeforces_handle'] ? sanitize($user['codeforces_handle']) : 'Not provided'; ?></li>
                        <li><strong>Statement of Interest:</strong> "<?php echo sanitize($user['motivation']); ?>"</li>
                    </ul>
                    <p style="margin-top: 20px; font-size: 14px;">
                        Once approved, you will have access to the member portal resources, and your member card will be unlocked.
                    </p>
                </div>

            <?php elseif ($user['status'] === 'rejected'): ?>
                <!-- REJECTED VIEW -->
                <div class="glass-card" style="border-left: 4px solid var(--danger);">
                    <h3 style="color: var(--danger); margin-bottom: 12px; font-size: 20px;">❌ Application Not Approved</h3>
                    <p style="margin-bottom: 16px;">
                        We regret to inform you that your application for membership in SGIPC has not been approved at this time.
                    </p>
                    <p style="color: var(--text-muted); font-size: 14px;">
                        Our membership intake is based on criteria regarding capacity, competitive programming focus, and institutional registration availability. You may contact the committee if you believe this was in error.
                    </p>
                    <a href="index.php" class="btn btn-secondary btn-sm" style="margin-top: 20px;">Return to Homepage</a>
                </div>

            <?php else: ?>
                <!-- APPROVED MEMBER VIEW -->
                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px;">
                    
                    <!-- Left Column: Resources and Tools -->
                    <div>
                        <div class="glass-card" style="margin-bottom: 30px;">
                            <h3 style="margin-bottom: 8px; font-size: 20px; color: var(--primary);">📝 Club Resources & Contests</h3>
                            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
                                Exclusive programming materials, links, and Vjudge passcode sheets provided by administrators.
                            </p>

                            <?php if (empty($resources)): ?>
                                <div style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 14px;">
                                    No resources have been shared yet. Check back soon!
                                </div>
                            <?php else: ?>
                                <div class="resources-list">
                                    <?php foreach ($resources as $res): ?>
                                        <div class="glass-card resource-item" style="padding: 16px; background: rgba(255,255,255,0.01);">
                                            <div class="resource-info">
                                                <h4><?php echo sanitize($res['title']); ?></h4>
                                                <?php if (!empty($res['description'])): ?>
                                                    <p><?php echo sanitize($res['description']); ?></p>
                                                <?php endif; ?>
                                                <small style="color: var(--primary); font-family: var(--font-mono); font-size: 11px;">
                                                    Shared on: <?php echo date('M d, Y', strtotime($res['created_at'])); ?>
                                                </small>
                                            </div>
                                            <a href="<?php echo sanitize($res['link']); ?>" target="_blank" class="btn btn-primary btn-sm" style="color:#070a13">Open Link</a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Update Profile Form -->
                        <div class="glass-card">
                            <h3 style="margin-bottom: 16px; font-size: 20px; color: var(--success);">🔧 Update Programming Handles</h3>
                            <form action="member.php" method="POST">
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="codeforces_handle">Codeforces Handle</label>
                                        <input type="text" name="codeforces_handle" id="codeforces_handle" class="form-control" value="<?php echo sanitize($user['codeforces_handle']); ?>" placeholder="e.g. tourist">
                                    </div>
                                    <div class="form-group">
                                        <label for="vjudge_handle">Vjudge Handle</label>
                                        <input type="text" name="vjudge_handle" id="vjudge_handle" class="form-control" value="<?php echo sanitize($user['vjudge_handle']); ?>" placeholder="e.g. tour_vj">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="programming_skills">Known Languages & Skills</label>
                                    <textarea name="programming_skills" id="programming_skills" class="form-control" placeholder="C++, Algorithms..."><?php echo sanitize($user['programming_skills']); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-success btn-sm">Save Changes</button>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column: Member ID Badge -->
                    <div>
                        <div class="glass-card profile-badge-card">
                            <div class="badge-logo">SGIPC</div>
                            
                            <img src="https://api.dicebear.com/7.x/bottts/svg?seed=<?php echo urlencode($user['name']); ?>" alt="Member Avatar" style="width: 120px; height: 120px; border-radius: 50%; margin-bottom: 16px; background: rgba(0, 242, 254, 0.05); border: 2px solid var(--primary);">
                            
                            <h3 style="font-size: 22px; margin-bottom: 4px;"><?php echo sanitize($user['name']); ?></h3>
                            <p style="color: var(--primary); font-size: 14px; font-weight: 600; margin-bottom: 16px;">
                                <?php echo $user['designation'] ? sanitize($user['designation']) : 'Active Member'; ?>
                            </p>
                            
                            <div class="member-id">
                                ID: SGIPC-<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?>
                            </div>

                            <table style="width: 100%; text-align: left; font-size: 13px; color: var(--text-muted); border-collapse: collapse; margin-top: 10px;">
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 8px 0;"><strong>Student ID:</strong></td>
                                    <td style="padding: 8px 0; text-align: right; color: var(--text-main);"><?php echo sanitize($user['student_id']); ?></td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 8px 0;"><strong>Dept & Batch:</strong></td>
                                    <td style="padding: 8px 0; text-align: right; color: var(--text-main);"><?php echo sanitize($user['department']); ?>, Batch <?php echo sanitize($user['batch']); ?></td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 8px 0;"><strong>Email:</strong></td>
                                    <td style="padding: 8px 0; text-align: right; color: var(--text-main);"><?php echo sanitize($user['email']); ?></td>
                                </tr>
                                <?php if (!empty($user['codeforces_handle'])): ?>
                                <tr>
                                    <td style="padding: 8px 0;"><strong>Codeforces:</strong></td>
                                    <td style="padding: 8px 0; text-align: right;">
                                        <a href="https://codeforces.com/profile/<?php echo urlencode($user['codeforces_handle']); ?>" target="_blank" style="color: #ff5b5b; font-family: var(--font-mono);">
                                            <?php echo sanitize($user['codeforces_handle']); ?> ↗
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
