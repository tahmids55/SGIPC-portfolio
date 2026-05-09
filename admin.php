<?php
// admin.php - Administrator control panel
require_once 'config.php';

// Check authorization
if (!is_logged_in() || !is_admin()) {
    redirect('login.php');
}

$success = '';
$error = '';

// Handle Application status updates & removals
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $targetId = (int)$_GET['id'];
    
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ? AND role = 'member'");
            $stmt->execute([$targetId]);
            $success = "Application approved successfully!";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ? AND role = 'member'");
            $stmt->execute([$targetId]);
            $success = "Application rejected.";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'member'");
            $stmt->execute([$targetId]);
            $success = "Member removed.";
        } elseif ($action === 'delete_resource') {
            $stmt = $pdo->prepare("DELETE FROM resources WHERE id = ?");
            $stmt->execute([$targetId]);
            $success = "Resource removed.";
        } elseif ($action === 'delete_score') {
            $stmt = $pdo->prepare("DELETE FROM score_logs WHERE id = ?");
            $stmt->execute([$targetId]);
            $success = "Score entry deleted successfully.";
        } elseif ($action === 'delete_gallery') {
            // Fetch image path to delete the physical file
            $get_stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
            $get_stmt->execute([$targetId]);
            $img = $get_stmt->fetch();
            if ($img) {
                $filePath = __DIR__ . '/' . $img['image_path'];
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                }
                $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
                $stmt->execute([$targetId]);
                $success = "Gallery photo deleted.";
            }
        }
    } catch (PDOException $e) {
        $error = "Action failed: " . $e->getMessage();
    }
}

// Handle Designation Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_designation'])) {
    $memberId = (int)$_POST['member_id'];
    $designation = sanitize($_POST['designation']);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET designation = ? WHERE id = ? AND status = 'approved'");
        $stmt->execute([$designation, $memberId]);
        $success = "Designation updated successfully!";
    } catch (PDOException $e) {
        $error = "Failed to update designation: " . $e->getMessage();
    }
}

// Handle Resource Posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource'])) {
    $title = sanitize($_POST['title']);
    $link = sanitize($_POST['link']);
    $description = sanitize($_POST['description']);
    
    if (empty($title) || empty($link)) {
        $error = "Resource title and link are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO resources (title, link, description) VALUES (?, ?, ?)");
            $stmt->execute([$title, $link, $description]);
            $success = "Resource shared successfully with members!";
        } catch (PDOException $e) {
            $error = "Failed to add resource: " . $e->getMessage();
        }
    }
}

// Handle Contest Score Adding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_score'])) {
    $memberId = (int)$_POST['score_member_id'];
    $contestName = sanitize($_POST['contest_name']);
    $points = (int)$_POST['score_points'];
    
    if (empty($contestName) || empty($memberId)) {
        $error = "Contest name and member select are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO score_logs (user_id, contest_name, points) VALUES (?, ?, ?)");
            $stmt->execute([$memberId, $contestName, $points]);
            $success = "Contest score logged successfully!";
        } catch (PDOException $e) {
            $error = "Failed to log score: " . $e->getMessage();
        }
    }
}

// Handle Gallery Image Upload / URL Adding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_gallery'])) {
    $caption = sanitize($_POST['caption']);
    
    if (isset($_FILES['gallery_file']) && $_FILES['gallery_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['gallery_file']['tmp_name'];
        $fileName = $_FILES['gallery_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = time() . '_' . md5(uniqid()) . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/uploads/gallery/';
            
            // Auto create folder if missing
            if (!file_exists($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $db_path = 'uploads/gallery/' . $newFileName;
                try {
                    $stmt = $pdo->prepare("INSERT INTO gallery (image_path, caption) VALUES (?, ?)");
                    $stmt->execute([$db_path, $caption]);
                    $success = "Gallery photo uploaded successfully!";
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            } else {
                $error = "Error moving the uploaded file to destination folder.";
            }
        } else {
            $error = "Invalid file extension. Allowed: " . implode(', ', $allowedExtensions);
        }
    } else {
        // Fallback to URL
        $imageUrl = sanitize($_POST['image_url']);
        if (!empty($imageUrl)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO gallery (image_path, caption) VALUES (?, ?)");
                $stmt->execute([$imageUrl, $caption]);
                $success = "Gallery photo URL added successfully!";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Please upload an image file or provide an image URL.";
        }
    }
}

// Query statistics & listings
try {
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'member'");
    $total_members = $total_stmt->fetchColumn();

    $pending_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending' AND role = 'member'");
    $pending_count = $pending_stmt->fetchColumn();

    $approved_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'approved' AND role = 'member'");
    $approved_count = $approved_stmt->fetchColumn();
    
    // Fetch pending applications
    $pending_list = $pdo->query("SELECT * FROM users WHERE status = 'pending' AND role = 'member' ORDER BY id DESC")->fetchAll();
    
    // Fetch approved members
    $approved_list = $pdo->query("SELECT * FROM users WHERE status = 'approved' AND role = 'member' ORDER BY id DESC")->fetchAll();

    // Fetch shared resources
    $resources_list = $pdo->query("SELECT * FROM resources ORDER BY id DESC")->fetchAll();

    // Fetch recent standings logs
    $scores_list = $pdo->query("SELECT s.*, u.name, u.student_id FROM score_logs s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 15")->fetchAll();

    // Fetch gallery photos
    $gallery_list = $pdo->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    die("Error retrieving data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - SGIPC</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-profile">
                <img src="https://api.dicebear.com/7.x/bottts/svg?seed=Admin" alt="Avatar" class="sidebar-avatar">
                <div class="sidebar-name">SGIPC Admin</div>
                <div class="sidebar-role">Administrator</div>
            </div>
            
            <ul class="sidebar-nav">
                <li class="active"><a href="admin.php">Admin Panel</a></li>
                <li><a href="standings.php">Global Standings</a></li>
                <li><a href="people.php?view=members">Members Directory</a></li>
                <li><a href="index.php">Public Home</a></li>
                <li style="margin-top: auto;"><a href="logout.php" class="logout-link">Log Out</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 28px;">Admin Control Panel</h1>
                    <p style="color: var(--text-muted);">Manage member registrations, standings points, photo gallery, and resources.</p>
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

            <!-- Stats Overview Cards -->
            <div class="stats-grid" style="margin-bottom: 40px;">
                <div class="glass-card stat-card" style="padding: 16px;">
                    <div class="stat-number" style="font-size: 32px; color: var(--primary);"><?php echo $total_members; ?></div>
                    <div class="stat-label" style="font-size: 11px;">Total Applicants</div>
                </div>
                <div class="glass-card stat-card" style="padding: 16px;">
                    <div class="stat-number" style="font-size: 32px; color: var(--warning);"><?php echo $pending_count; ?></div>
                    <div class="stat-label" style="font-size: 11px;">Pending Review</div>
                </div>
                <div class="glass-card stat-card" style="padding: 16px;">
                    <div class="stat-number" style="font-size: 32px; color: var(--success);"><?php echo $approved_count; ?></div>
                    <div class="stat-label" style="font-size: 11px;">Approved Members</div>
                </div>
            </div>

            <!-- Pending Registrations Section -->
            <section class="glass-card" style="margin-bottom: 40px;">
                <h3 style="color: var(--warning); border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-size: 18px;">
                    ⏳ Pending Registration Requests (<?php echo count($pending_list); ?>)
                </h3>
                
                <?php if (empty($pending_list)): ?>
                    <p style="padding: 20px 0; color: var(--text-muted); font-size: 14px;">No pending registration requests at the moment.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Student & Dept</th>
                                    <th>Handles</th>
                                    <th>Skills & Motivation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_list as $row): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: #fff;"><?php echo sanitize($row['name']); ?></strong><br>
                                            <small><?php echo sanitize($row['email']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo sanitize($row['student_id']); ?><br>
                                            <small><?php echo sanitize($row['department']); ?> (Batch: <?php echo sanitize($row['batch']); ?>)</small>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['codeforces_handle'])): ?>
                                                <small style="color: #ff5b5b; font-family: var(--font-mono);">CF: <?php echo sanitize($row['codeforces_handle']); ?></small><br>
                                            <?php endif; ?>
                                            <?php if (!empty($row['vjudge_handle'])): ?>
                                                <small style="color: var(--primary); font-family: var(--font-mono);">VJ: <?php echo sanitize($row['vjudge_handle']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width: 250px; font-size: 13px;">
                                            <strong>Skills:</strong> <?php echo sanitize($row['programming_skills']); ?><br>
                                            <strong>Motivation:</strong> "<?php echo sanitize($row['motivation']); ?>"
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <a href="admin.php?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm confirm-action" style="padding: 6px 12px; font-size: 12px;">Approve</a>
                                                <a href="admin.php?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm confirm-action" style="padding: 6px 12px; font-size: 12px;">Reject</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Approved Members & Designations Assignment -->
            <section class="glass-card" style="margin-bottom: 40px;">
                <h3 style="color: var(--success); border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-size: 18px;">
                    👥 Club Members & Executive Designations (<?php echo count($approved_list); ?>)
                </h3>
                
                <?php if (empty($approved_list)): ?>
                    <p style="padding: 20px 0; color: var(--text-muted); font-size: 14px;">No approved members yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Codeforces</th>
                                    <th>Designation</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approved_list as $member): ?>
                                    <tr>
                                        <td class="code-font" style="color: var(--primary);">
                                            SGIPC-<?php echo str_pad($member['id'], 3, '0', STR_PAD_LEFT); ?>
                                        </td>
                                        <td>
                                            <strong style="color: #fff;"><?php echo sanitize($member['name']); ?></strong><br>
                                            <small><?php echo sanitize($member['email']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo sanitize($member['department']); ?> (Batch: <?php echo sanitize($member['batch']); ?>)
                                        </td>
                                        <td>
                                            <?php if (!empty($member['codeforces_handle'])): ?>
                                                <a href="https://codeforces.com/profile/<?php echo urlencode($member['codeforces_handle']); ?>" target="_blank" style="color: #ff5b5b; font-family: var(--font-mono);">
                                                    <?php echo sanitize($member['codeforces_handle']); ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form action="admin.php" method="POST" class="designation-form">
                                                <input type="hidden" name="update_designation" value="1">
                                                <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                <select name="designation" class="designation-select">
                                                    <option value="" <?php echo empty($member['designation']) ? 'selected' : ''; ?>>None (General Member)</option>
                                                    <option value="President" <?php echo $member['designation'] === 'President' ? 'selected' : ''; ?>>President</option>
                                                    <option value="General Secretary" <?php echo $member['designation'] === 'General Secretary' ? 'selected' : ''; ?>>General Secretary</option>
                                                    <option value="Treasurer" <?php echo $member['designation'] === 'Treasurer' ? 'selected' : ''; ?>>Treasurer</option>
                                                    <option value="Trainer" <?php echo $member['designation'] === 'Trainer' ? 'selected' : ''; ?>>Trainer</option>
                                                    <option value="Web Master" <?php echo $member['designation'] === 'Web Master' ? 'selected' : ''; ?>>Web Master</option>
                                                    <option value="Executive Member" <?php echo $member['designation'] === 'Executive Member' ? 'selected' : ''; ?>>Executive Member</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary btn-sm" style="padding: 4px 10px; font-size: 11px;">Assign</button>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="admin.php?action=delete&id=<?php echo $member['id']; ?>" class="btn btn-danger btn-sm confirm-delete" style="padding: 4px 8px; font-size: 11px;">Remove</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Leaderboard & Score Management Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px; margin-bottom: 40px;">
                <!-- Add points form -->
                <section class="glass-card">
                    <h3 style="color: var(--primary); border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-size: 18px; margin-bottom: 20px;">
                        🏆 Log Contest Points
                    </h3>
                    <form action="admin.php" method="POST">
                        <input type="hidden" name="log_score" value="1">
                        
                        <div class="form-group">
                            <label for="score_member_id">Select Member *</label>
                            <select name="score_member_id" id="score_member_id" class="form-control" required>
                                <option value="">-- Choose Member --</option>
                                <?php foreach ($approved_list as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo sanitize($member['name']); ?> (<?php echo sanitize($member['student_id']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="contest_name">Contest Name / Identifier *</label>
                            <input type="text" name="contest_name" id="contest_name" class="form-control" placeholder="e.g. Mock Contest #12, Codeforces Round 910" required>
                        </div>

                        <div class="form-group">
                            <label for="score_points">Points added (Can be negative for penalties) *</label>
                            <input type="number" name="score_points" id="score_points" class="form-control" placeholder="e.g. 500 or -50" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">Add Points</button>
                    </form>
                </section>

                <!-- Points History Logs -->
                <section class="glass-card">
                    <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-size: 18px; margin-bottom: 20px;">
                        ⚡ Recent Scores Added (<?php echo count($scores_list); ?>)
                    </h3>
                    
                    <?php if (empty($scores_list)): ?>
                        <p style="color: var(--text-muted); font-size: 14px;">No score logs tracked yet.</p>
                    <?php else: ?>
                        <div style="max-height: 350px; overflow-y: auto;">
                            <ul style="list-style: none;">
                                <?php foreach ($scores_list as $sc): ?>
                                    <li style="padding: 10px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                                        <div>
                                            <strong style="color: #fff;"><?php echo sanitize($sc['name']); ?></strong><br>
                                            <small style="color: var(--text-muted);"><?php echo sanitize($sc['contest_name']); ?></small>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <span class="code-font" style="font-weight: bold; color: <?php echo $sc['points'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                                                <?php echo ($sc['points'] >= 0 ? '+' : '') . $sc['points']; ?>
                                            </span>
                                            <a href="admin.php?action=delete_score&id=<?php echo $sc['id']; ?>" class="btn btn-danger btn-sm confirm-delete" style="padding: 2px 6px; font-size: 10px;">✕</a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Gallery management Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px; margin-bottom: 40px;">
                <!-- Add image form -->
                <section class="glass-card">
                    <h3 style="color: var(--success); border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-size: 18px; margin-bottom: 20px;">
                        🖼️ Add Photo to Gallery
                    </h3>
                    <form action="admin.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_gallery" value="1">
                        
                        <div class="form-group">
                            <label for="gallery_file">Upload local image file (Allowed: JPG, PNG, WEBP)</label>
                            <input type="file" name="gallery_file" id="gallery_file" class="form-control">
                        </div>

                        <div style="text-align: center; margin: 10px 0; color: var(--text-muted); font-size: 12px;">-- OR PROVIDE IMAGE URL --</div>

                        <div class="form-group">
                            <label for="image_url">Remote Image Web URL</label>
                            <input type="url" name="image_url" id="image_url" class="form-control" placeholder="https://images.unsplash.com/photo-...">
                        </div>

                        <div class="form-group">
                            <label for="caption">Photo Caption</label>
                            <input type="text" name="caption" id="caption" class="form-control" placeholder="e.g. National Programming Contest 2026 participants">
                        </div>

                        <button type="submit" class="btn btn-success btn-sm" style="width: 100%;">Add to Gallery</button>
                    </form>
                </section>

                <!-- Gallery photo items -->
                <section class="glass-card">
                    <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-size: 18px; margin-bottom: 20px;">
                        📷 Active Gallery items (<?php echo count($gallery_list); ?>)
                    </h3>
                    
                    <?php if (empty($gallery_list)): ?>
                        <p style="color: var(--text-muted); font-size: 14px;">No images in the gallery yet.</p>
                    <?php else: ?>
                        <div style="max-height: 400px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px;">
                            <?php foreach ($gallery_list as $gal): ?>
                                <div class="glass-card" style="padding: 8px; position: relative; display: flex; flex-direction: column;">
                                    <img src="<?php echo sanitize($gal['image_path']); ?>" alt="Gallery" style="width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 6px; margin-bottom: 6px;">
                                    <div style="font-size: 11px; color: var(--text-muted); line-height: 1.2; height: 36px; overflow: hidden; margin-bottom: 8px;">
                                        <?php echo !empty($gal['caption']) ? sanitize($gal['caption']) : 'No caption'; ?>
                                    </div>
                                    <a href="admin.php?action=delete_gallery&id=<?php echo $gal['id']; ?>" class="btn btn-danger btn-sm confirm-delete" style="width: 100%; padding: 4px; font-size: 10px; text-align: center; margin-top: auto;">Delete</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Resource Management Section -->
            <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px;">
                <!-- Post a new Resource -->
                <section class="glass-card">
                    <h3 style="color: var(--primary); border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-size: 18px; margin-bottom: 20px;">
                        📌 Share Club Resource / Contest
                    </h3>
                    <form action="admin.php" method="POST">
                        <input type="hidden" name="add_resource" value="1">
                        
                        <div class="form-group">
                            <label for="title">Resource Title *</label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Vjudge Weekly Contest #5" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="link">Resource Link (URL) *</label>
                            <input type="url" name="link" id="link" class="form-control" placeholder="https://vjudge.net/..." required>
                        </div>

                        <div class="form-group">
                            <label for="description">Short Description / Instructions</label>
                            <textarea name="description" id="description" class="form-control" placeholder="Contest passcode is SGIPC52. Solve at least 3 problems."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">Publish Resource</button>
                    </form>
                </section>

                <!-- List shared resources -->
                <section class="glass-card">
                    <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-size: 18px; margin-bottom: 20px;">
                        📂 Active Shares (<?php echo count($resources_list); ?>)
                    </h3>
                    
                    <?php if (empty($resources_list)): ?>
                        <p style="color: var(--text-muted); font-size: 14px;">No resources shared yet.</p>
                    <?php else: ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <ul style="list-style: none;">
                                <?php foreach ($resources_list as $res): ?>
                                    <li style="padding: 12px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                                        <div style="max-width: 80%;">
                                            <strong style="color: #fff; font-size: 14px;"><?php echo sanitize($res['title']); ?></strong><br>
                                            <a href="<?php echo sanitize($res['link']); ?>" target="_blank" style="font-size: 12px; font-family: var(--font-mono); word-break: break-all;"><?php echo sanitize($res['link']); ?></a>
                                        </div>
                                        <a href="admin.php?action=delete_resource&id=<?php echo $res['id']; ?>" class="btn btn-danger btn-sm confirm-delete" style="padding: 4px 8px; font-size: 10px;">Delete</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
