<?php
// standings.php - Global Club Leaderboard Standings
require_once 'config.php';

try {
    // 1. Fetch Global Standings
    $standings_query = "
        SELECT u.id, u.name, u.student_id, u.department, u.batch, u.codeforces_handle,
               COALESCE(SUM(s.points), 0) AS total_points,
               COUNT(s.id) AS contests_played
        FROM users u
        LEFT JOIN score_logs s ON u.id = s.user_id
        WHERE u.status = 'approved' AND u.role = 'member'
        GROUP BY u.id
        ORDER BY total_points DESC, name ASC
    ";
    $standings_stmt = $pdo->prepare($standings_query);
    $standings_stmt->execute();
    $standings = $standings_stmt->fetchAll();
    
    // 2. Fetch Recent Score Additions Log
    $log_query = "
        SELECT s.*, u.name, u.student_id
        FROM score_logs s
        JOIN users u ON s.user_id = u.id
        ORDER BY s.id DESC
        LIMIT 10
    ";
    $log_stmt = $pdo->prepare($log_query);
    $log_stmt->execute();
    $score_logs = $log_stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Standings - SGIPC Leaderboard</title>
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
                
                <li><a href="standings.php" class="active">Standings</a></li>
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

    <header class="section" style="padding-bottom: 20px;">
        <div class="container">
            <div class="section-header">
                <h2>SGIPC Global Standings</h2>
                <p>The leaderboard ranking of competitive programmers based on local and mock contest scores.</p>
            </div>
        </div>
    </header>

    <main class="container" style="padding-bottom: 100px;">
        
        <div style="display: grid; grid-template-columns: 1.8fr 1fr; gap: 30px; align-items: start;">
            
            <!-- Left Side: Leaderboard Table -->
            <div class="glass-card" style="padding: 24px;">
                <h3 style="margin-bottom: 20px; font-size: 20px; color: var(--primary); border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    🏆 Leaderboard Standings
                </h3>

                <?php if (empty($standings)): ?>
                    <p style="color: var(--text-muted); font-size: 14px; text-align: center; padding: 30px 0;">No approved members in standings yet.</p>
                <?php else: ?>
                    <div class="table-responsive" style="margin-top: 10px;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Programmer</th>
                                    <th>Department</th>
                                    <th style="text-align: center;">Contests</th>
                                    <th style="text-align: right;">Cumulative Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $iter = 0;
                                $display_rank = 0;
                                $last_score = -1;
                                foreach ($standings as $row): 
                                    $iter++;
                                    if ($row['total_points'] != $last_score) {
                                        $display_rank = $iter;
                                        $last_score = $row['total_points'];
                                    }
                                    
                                    // Rank styling class selection
                                    $rank_class = 'rank-other';
                                    if ($display_rank === 1) $rank_class = 'rank-1';
                                    elseif ($display_rank === 2) $rank_class = 'rank-2';
                                    elseif ($display_rank === 3) $rank_class = 'rank-3';
                                ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge <?php echo $rank_class; ?>">
                                                <?php echo $display_rank; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong style="color: #fff;"><?php echo sanitize($row['name']); ?></strong>
                                            <?php if (!empty($row['codeforces_handle'])): ?>
                                                <br>
                                                <a href="https://codeforces.com/profile/<?php echo urlencode($row['codeforces_handle']); ?>" target="_blank" style="color: #ff5b5b; font-family: var(--font-mono); font-size: 11px;">
                                                    cf: <?php echo sanitize($row['codeforces_handle']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo sanitize($row['department']); ?><br>
                                            <small style="color: var(--text-muted); font-size: 11px;">ID: <?php echo sanitize($row['student_id']); ?></small>
                                        </td>
                                        <td style="text-align: center;" class="code-font"><?php echo $row['contests_played']; ?></td>
                                        <td style="text-align: right; color: var(--success); font-weight: bold;" class="code-font">
                                            <?php echo number_format($row['total_points']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Side: Recent Contests History Logs -->
            <div class="glass-card" style="padding: 24px;">
                <h3 style="margin-bottom: 20px; font-size: 20px; color: var(--success); border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    ⚡ Recent Score Additions
                </h3>

                <?php if (empty($score_logs)): ?>
                    <p style="color: var(--text-muted); font-size: 14px; text-align: center; padding: 30px 0;">No score history logged yet.</p>
                <?php else: ?>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <ul style="list-style: none;">
                            <?php foreach ($score_logs as $log): ?>
                                <li style="padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                                    <div>
                                        <strong style="color: #fff;"><?php echo sanitize($log['name']); ?></strong><br>
                                        <span style="color: var(--text-muted); font-size: 11px;"><?php echo sanitize($log['contest_name']); ?></span>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="code-font" style="color: <?php echo $log['points'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; font-weight: bold;">
                                            <?php echo ($log['points'] >= 0 ? '+' : '') . $log['points']; ?> pts
                                        </span><br>
                                        <small style="color: var(--text-muted); font-size: 10px;">
                                            <?php echo date('M d, Y', strtotime($log['added_at'])); ?>
                                        </small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

        </div>
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

    <script src="assets/js/main.js"></script>
</body>
</html>
