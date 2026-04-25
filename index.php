<?php
// index.php - SGIPC Public Portfolio website
require_once 'config.php';

// Fetch approved administration members
try {
    $admin_stmt = $pdo->prepare("SELECT * FROM users WHERE status = 'approved' AND designation IS NOT NULL AND designation != '' ORDER BY 
        CASE 
            WHEN designation = 'President' THEN 1
            WHEN designation = 'General Secretary' THEN 2
            WHEN designation = 'GS' THEN 2
            WHEN designation = 'General Secretary (GS)' THEN 2
            WHEN designation = 'Treasurer' THEN 3
            ELSE 4
        END, name ASC");
    $admin_stmt->execute();
    $admin_members = $admin_stmt->fetchAll();
    
    // Fetch count of approved members for stats
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'approved' AND role = 'member'");
    $approved_count = $count_stmt->fetchColumn();

    // Fetch gallery photos
    $gallery_stmt = $pdo->query("SELECT * FROM gallery ORDER BY id DESC");
    $gallery_photos = $gallery_stmt->fetchAll();
} catch (PDOException $e) {
    $admin_members = [];
    $approved_count = 0;
    $gallery_photos = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIPC - Special Group Interested in Programming Contest</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">SGIPC<span>{ }</span></a>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                
                <!-- Dropdown -->
                <li class="dropdown">
                    <a href="#" class="dropdown-trigger">People ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="people.php?view=administration">Administration</a></li>
                        <li><a href="people.php?view=members">Members</a></li>
                    </ul>
                </li>
                
                <li><a href="standings.php">Standings</a></li>
                <li><a href="#gallery">Gallery</a></li>
                <li><a href="#events">Events</a></li>
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

    <!-- Hero Section -->
    <header class="hero">
        <div class="container">
            <div class="hero-tagline">Competitive Programming Club</div>
            <h1>Solve. Optimize. <span>Conquer.</span></h1>
            <p class="hero-desc">
                Welcome to SGIPC (Special Group Interested in Programming Contest). We are a community of passionate programmers, algorithm enthusiasts, and problem solvers pushing the boundaries of competitive programming.
            </p>
            <div class="hero-actions">
                <a href="register.php" class="btn btn-primary">Join Club</a>
                <a href="#about" class="btn btn-secondary">Learn More</a>
            </div>

            <!-- Terminal Mockup -->
            <div class="code-mockup">
                <div class="mockup-header">
                    <div class="dot red"></div>
                    <div class="dot yellow"></div>
                    <div class="dot green"></div>
                    <div class="mockup-title">sgipc.cpp</div>
                </div>
                <div class="mockup-body">
                    <div class="mockup-line"><span class="mockup-prompt">sgipc@pc:~$</span> ./run_bootcamp</div>
                    <div class="mockup-line" style="color: var(--success);">[+] Bootstrapping competitive programming minds...</div>
                    <div class="mockup-line">#include &lt;iostream&gt;</div>
                    <div class="mockup-line">using namespace std;</div>
                    <div class="mockup-line" style="color: var(--text-muted);">// Currently teaching:</div>
                    <div class="mockup-line">string current_topic = "<span id="typing-effect" style="color: var(--primary); font-weight: bold;">Dynamic Programming</span><span style="animation: blink 1s step-end infinite;">|</span>";</div>
                    <div class="mockup-line">int main() {</div>
                    <div class="mockup-line">&nbsp;&nbsp;&nbsp;&nbsp;cout &lt;&lt; "Keep Coding, Keep Competing!" &lt;&lt; endl;</div>
                    <div class="mockup-line">&nbsp;&nbsp;&nbsp;&nbsp;return 0;</div>
                    <div class="mockup-line">}</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Stats Section -->
    <section class="container">
        <div class="stats-grid">
            <div class="glass-card stat-card">
                <div class="stat-number"><?php echo max(15, (int)$approved_count); ?>+</div>
                <div class="stat-label">Active Contestants</div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-number">45+</div>
                <div class="stat-label">Contests Hosted</div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-number">18,500+</div>
                <div class="stat-label">Problems Solved</div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-number">5+</div>
                <div class="stat-label">IOI & ICPC Mentors</div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section">
        <div class="container">
            <div class="section-header">
                <h2>About SGIPC</h2>
                <p>Developing algorithmic thinking and nurturing contest performance.</p>
            </div>
            <div class="glass-card" style="padding: 40px; max-width: 900px; margin: 0 auto;">
                <p style="margin-bottom: 20px; font-size: 16px;">
                    <strong>SGIPC</strong> (Special Group Interested in Programming Contest) is the premium competitive programming body in our institution. We focus entirely on preparing students for prestigious national and international programming contests, including the **ACM-ICPC (International Collegiate Programming Contest)**, **NCPC**, and **IUPC** contests.
                </p>
                <p style="margin-bottom: 20px; font-size: 16px;">
                    Our core activities involve hosting weekly mock contests on platforms like Codeforces and Vjudge, taking class-by-class training bootcamps on advanced data structures and algorithms, and fostering a collaborative peer network for solving tough problems.
                </p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 30px;">
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 10px; font-size: 18px;">💡 Weekly Activities</h4>
                        <ul style="list-style-position: inside; color: var(--text-muted); font-size: 14px;">
                            <li style="margin-bottom: 8px;">Individual & Team Programming Contests</li>
                            <li style="margin-bottom: 8px;">Topic-wise lectures and code reviews</li>
                            <li style="margin-bottom: 8px;">Algorithm design training workshops</li>
                        </ul>
                    </div>
                    <div>
                        <h4 style="color: var(--success); margin-bottom: 10px; font-size: 18px;">🏆 Our Vision</h4>
                        <ul style="list-style-position: inside; color: var(--text-muted); font-size: 14px;">
                            <li style="margin-bottom: 8px;">Represent the university at World Finals</li>
                            <li style="margin-bottom: 8px;">Build solid logical backgrounds for tech roles</li>
                            <li style="margin-bottom: 8px;">Cultivate a high-performance contest culture</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="section">
        <div class="container">
            <div class="section-header">
                <h2>Club Gallery</h2>
                <p>Memorable moments from our programming bootcamps and contests.</p>
            </div>
            
            <?php if (empty($gallery_photos)): ?>
                <div class="glass-card" style="text-align: center; max-width: 600px; margin: 0 auto; padding: 40px;">
                    <p style="color: var(--text-muted); font-size: 14px;">No gallery images shared yet. Check back later!</p>
                </div>
            <?php else: ?>
                <div class="gallery-grid">
                    <?php foreach ($gallery_photos as $photo): ?>
                        <div class="gallery-card">
                           <img src="<?php echo sanitize($photo['image_path']); ?>" alt="Gallery Image">
                           <?php if (!empty($photo['caption'])): ?>
                               <div class="gallery-caption">
                                   <?php echo sanitize($photo['caption']); ?>
                               </div>
                           <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Events Section -->
    <section id="events" class="section" style="background: rgba(10, 14, 26, 0.3);">
        <div class="container">
            <div class="section-header">
                <h2>Upcoming & Past Events</h2>
                <p>Join us at our next contest or bootcamp.</p>
            </div>
            <div class="events-grid">
                <!-- Event 1 -->
                <div class="glass-card event-card">
                    <img src="https://images.unsplash.com/photo-1515879218367-8466d910aaa4?w=600&auto=format&fit=crop&q=60" alt="Code Rush" class="event-image">
                    <div class="event-content">
                        <div class="event-date">JULY 10, 2026</div>
                        <h3>SGIPC Code Rush 2026</h3>
                        <p>Our annual flagship programming contest featuring individual competitors. Registration will open on the member panel soon.</p>
                        <a href="register.php" class="btn btn-secondary btn-sm" style="margin-top: auto; align-self: flex-start;">Register to Join</a>
                    </div>
                </div>

                <!-- Event 2 -->
                <div class="glass-card event-card">
                    <img src="https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&auto=format&fit=crop&q=60" alt="Bootcamp" class="event-image">
                    <div class="event-content">
                        <div class="event-date">EVERY FRIDAY AT 3:00 PM</div>
                        <h3>Weekly Practice Contests</h3>
                        <p>We host weekly contests on Vjudge. Approved members will receive the Vjudge links and passcodes in their member dashboards.</p>
                        <a href="login.php" class="btn btn-secondary btn-sm" style="margin-top: auto; align-self: flex-start;">Login to Dashboard</a>
                    </div>
                </div>

                <!-- Event 3 -->
                <div class="glass-card event-card">
                    <img src="https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=600&auto=format&fit=crop&q=60" alt="Workshop" class="event-image">
                    <div class="event-content">
                        <div class="event-date">PAST EVENT (MAY 2026)</div>
                        <h3>Advanced Graph Algorithms</h3>
                        <p>A workshop detailing Lowest Common Ancestor (LCA), Centroid Decomposition, and heavy programming implementations in C++.</p>
                        <span style="color: var(--text-muted); font-size: 13px; font-weight: bold; margin-top: auto;">Completed</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-logo">SGIPC<span>{ }</span></div>
            <p>Special Group Interested in Programming Contest</p>
            <p style="margin-top: 10px; font-size: 12px; color: var(--text-muted);">
                &copy; <?php echo date('Y'); ?> SGIPC. All rights reserved. Built with PHP, HTML, CSS & JS.
            </p>
        </div>
    </footer>

    <!-- Custom Blink Animation in CSS -->
    <style>
        @keyframes blink {
            50% { opacity: 0; }
        }
    </style>

    <script src="assets/js/main.js"></script>
</body>
</html>
