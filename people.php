<?php
// people.php - Displays club administration and member directory
require_once 'config.php';

$view = isset($_GET['view']) ? sanitize($_GET['view']) : 'administration';

try {
    if ($view === 'administration') {
        // Query approved members with a designation
        $stmt = $pdo->prepare("SELECT * FROM users WHERE status = 'approved' AND designation IS NOT NULL AND designation != '' ORDER BY 
            CASE 
                WHEN designation = 'President' THEN 1
                WHEN designation = 'General Secretary' THEN 2
                WHEN designation = 'GS' THEN 2
                WHEN designation = 'General Secretary (GS)' THEN 2
                WHEN designation = 'Treasurer' THEN 3
                WHEN designation = 'Trainer' THEN 4
                WHEN designation = 'Web Master' THEN 5
                ELSE 6
            END, name ASC");
        $stmt->execute();
        $people = $stmt->fetchAll();
    } else {
        // Query all approved members
        $stmt = $pdo->prepare("SELECT * FROM users WHERE status = 'approved' AND role = 'member' ORDER BY name ASC");
        $stmt->execute();
        $people = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $view === 'members' ? 'Members Directory' : 'Club Administration'; ?> - SGIPC</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">SGIPC<span>{ }</span></a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                
                <!-- Dropdown Menu -->
                <li class="dropdown">
                    <a href="#" class="dropdown-trigger active">People ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="people.php?view=administration" class="<?php echo $view === 'administration' ? 'active' : ''; ?>">Administration</a></li>
                        <li><a href="people.php?view=members" class="<?php echo $view === 'members' ? 'active' : ''; ?>">Members</a></li>
                    </ul>
                </li>
                
                <li><a href="standings.php">Standings</a></li>
                <li><a href="register.php">Register</a></li>
                <li>
                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo is_admin() ? 'admin.php' : 'member.php'; ?>" class="btn btn-primary btn-sm" style="color:#070a13">Dashboard</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-secondary btn-sm">Login</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Header Section -->
    <header class="section" style="padding-bottom: 20px;">
        <div class="container">
            <div class="section-header" style="margin-bottom: 30px;">
                <h2><?php echo $view === 'members' ? 'SGIPC Member Directory' : 'Club Administration'; ?></h2>
                <p>
                    <?php echo $view === 'members' ? 'Meet the proud members of the programming contest community.' : 'Meet the core leaders driving the visions and goals of SGIPC.'; ?>
                </p>
            </div>
            
            <!-- Tab Navigation Toggle -->
            <div style="display: flex; justify-content: center; gap: 12px; margin-bottom: 40px;">
                <a href="people.php?view=administration" class="btn <?php echo $view === 'administration' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm" style="<?php echo $view === 'administration' ? 'color:#070a13;' : ''; ?>">
                    Administration Panel
                </a>
                <a href="people.php?view=members" class="btn <?php echo $view === 'members' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm" style="<?php echo $view === 'members' ? 'color:#070a13;' : ''; ?>">
                    General Members Directory
                </a>
            </div>

            <!-- Search input ONLY for General Members view -->
            <?php if ($view === 'members'): ?>
                <div style="max-width: 500px; margin: 0 auto 40px auto; text-align: center;">
                    <input type="text" id="search-members" class="form-control" placeholder="Search members by name, department, batch, student ID..." style="text-align: center;">
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="container" style="padding-bottom: 100px;">
        <?php if (empty($people)): ?>
            <div class="glass-card" style="text-align: center; max-width: 600px; margin: 0 auto; padding: 45px;">
                <p style="color: var(--text-muted); font-size: 15px;">
                    <?php echo $view === 'members' ? 'No registered members found. Be the first to register!' : 'No administrative designation assigned yet. Standard general members can be promoted by the Administrator.'; ?>
                </p>
                <?php if ($view === 'members'): ?>
                    <a href="register.php" class="btn btn-primary btn-sm" style="margin-top: 20px; color:#070a13">Register Now</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            
            <!-- Cards Grid Layout -->
            <div class="admin-grid">
                <?php foreach ($people as $row): ?>
                    <!-- Member Card -->
                    <div class="glass-card admin-card member-card" style="display: flex; flex-direction: column;">
                        <div class="admin-avatar-wrapper">
                            <img src="https://api.dicebear.com/7.x/bottts/svg?seed=<?php echo urlencode($row['name']); ?>" alt="Avatar" class="admin-avatar">
                            
                            <!-- Display badge designation or member flag -->
                            <?php if (!empty($row['designation'])): ?>
                                <div class="admin-tag"><?php echo sanitize($row['designation']); ?></div>
                            <?php else: ?>
                                <div class="admin-tag" style="background: rgba(255,255,255,0.08); color: var(--text-muted); border: 1px solid var(--border-color);">Member</div>
                            <?php endif; ?>
                        </div>
                        
                        <h3><?php echo sanitize($row['name']); ?></h3>
                        <div class="admin-dept"><?php echo sanitize($row['department']); ?> | Batch <?php echo sanitize($row['batch']); ?></div>
                        
                        <!-- Extra details (e.g. Student ID) -->
                        <div class="code-font" style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">
                            ID: <?php echo sanitize($row['student_id']); ?>
                        </div>

                        <!-- Codeforces Handle Link -->
                        <?php if (!empty($row['codeforces_handle'])): ?>
                            <a href="https://codeforces.com/profile/<?php echo urlencode($row['codeforces_handle']); ?>" target="_blank" class="admin-cf-handle">
                                <?php echo sanitize($row['codeforces_handle']); ?>
                            </a>
                        <?php endif; ?>

                        <!-- Member ID tracker -->
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: auto; padding-top: 15px;">
                            Member No: SGIPC-<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-logo">SGIPC<span>{ }</span></div>
            <p>Special Group Interested in Programming Contest</p>
            <p style="margin-top: 10px; font-size: 12px; color: var(--text-muted);">
                &copy; <?php echo date('Y'); ?> SGIPC. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- JS search filter scripting -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('search-members');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const query = e.target.value.toLowerCase();
                    const cards = document.querySelectorAll('.member-card');
                    
                    cards.forEach(card => {
                        const name = card.querySelector('h3').textContent.toLowerCase();
                        const dept = card.querySelector('.admin-dept').textContent.toLowerCase();
                        const studentId = card.querySelector('.code-font').textContent.toLowerCase();
                        
                        if (name.includes(query) || dept.includes(query) || studentId.includes(query)) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
