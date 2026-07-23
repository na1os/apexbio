<?php
require_once __DIR__ . '/backend/config.php';

$requestedUsername = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_SPECIAL_CHARS);

function getSocialIconSvg($platform) {
    switch (strtolower($platform)) {
        case 'instagram':
            return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>';
        case 'twitter':
        case 'x':
            return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';
        case 'youtube':
            return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>';
        case 'github':
            return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>';
        case 'discord':
            return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>';
        case 'tiktok':
            return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.82.56-1.36 1.48-1.4 2.47-.04.86.32 1.73.95 2.32.78.74 1.91 .98 2.94.67 1.02-.31 1.83-1.18 2.05-2.22.08-.47.08-.95.08-1.42V.02z"/></svg>';
        default:
            return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>';
    }
}

function getRoleBadgeAsset(string $label): array {
    $map = [
        'Developer' => ['icon' => '💻', 'color' => '#60a5fa', 'label' => 'Developer'],
        'Creator' => ['icon' => '🎥', 'color' => '#f472b6', 'label' => 'Creator'],
        'Designer' => ['icon' => '🎨', 'color' => '#fb7185', 'label' => 'Designer'],
        'Musician' => ['icon' => '🎵', 'color' => '#a78bfa', 'label' => 'Musician'],
        'Gamer' => ['icon' => '🎮', 'color' => '#22d3ee', 'label' => 'Gamer'],
        'Entrepreneur' => ['icon' => '💼', 'color' => '#f59e0b', 'label' => 'Entrepreneur'],
        'Verified' => ['icon' => '✅', 'color' => '#34d399', 'label' => 'Verified'],
        'Founder' => ['icon' => '⚡', 'color' => '#f97316', 'label' => 'Founder'],
    ];
    return $map[$label] ?? ['icon' => '✨', 'color' => '#94a3b8', 'label' => $label ?: 'Role'];
}

function parse_badges(?string $raw): array {
    $parts = preg_split('/[,\n|]+/', (string)$raw);
    $parts = array_filter(array_map('trim', $parts));
    return array_values(array_unique($parts));
}

function template_theme_class(string $templateKey): string {
    $key = preg_replace('/[^a-z0-9_-]/i', '', strtolower($templateKey));
    return 'template-' . ($key ?: 'glass');
}

if ($requestedUsername) {
    $db = get_db();
    $stmt = $db->prepare('SELECT p.*, u.username, u.is_banned FROM profiles p JOIN users u ON p.user_id = u.id WHERE u.username = ?');
    $stmt->execute([$requestedUsername]);
    $profile = $stmt->fetch();
    if (!$profile || $profile['is_banned']) {
        header('HTTP/1.0 404 Not Found');
        echo '<!DOCTYPE html><html><head><title>User Not Found</title><link rel="stylesheet" href="css/style.css"></head><body class="error-body"><div class="error-box"><h1>404</h1><p>User profile not found or account has been suspended.</p><a href="index.php" class="btn btn-primary">Go Home</a></div></body></html>';
        exit;
    }

    $settings = get_profile_settings($db, (int)$profile['user_id']);
    $badges = parse_badges($profile['badge_label'] ?? '');
    $templateClass = template_theme_class((string)($settings['template_key'] ?? 'glass'));
    $accentColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($profile['accent_color'] ?? '')) ? strtoupper((string)$profile['accent_color']) : '#6366f1';
    $themeValue = strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string)($profile['theme'] ?? 'midnight')));
    $themeClass = 'theme-' . ($themeValue ?: 'midnight');
    $viewer = current_user();
    $isOwnerViewer = $viewer && (int)$viewer['id'] === (int)$profile['user_id'];
    $gateMode = !empty($settings['gate_enabled']) && empty($_GET['enter']) && !$isOwnerViewer;

    $viewCookie = 'apexbio_view_' . (int)$profile['id'];
    $shouldCountView = !$isOwnerViewer && empty($_COOKIE[$viewCookie]);
    if ($shouldCountView) {
        $ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $stmtView = $db->prepare('INSERT INTO profile_views (profile_id, ip_hash) VALUES (?, ?)');
        $stmtView->execute([$profile['id'], $ipHash]);
        setcookie($viewCookie, '1', [
            'expires' => time() + (86400 * 365),
            'path' => '/',
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => false,
        ]);
    }

    $stmtLinks = $db->prepare('SELECT * FROM links WHERE user_id = ? ORDER BY position ASC');
    $stmtLinks->execute([$profile['user_id']]);
    $links = $stmtLinks->fetchAll();

    $stmtSocials = $db->prepare('SELECT * FROM social_links WHERE user_id = ? ORDER BY position ASC');
    $stmtSocials->execute([$profile['user_id']]);
    $socials = $stmtSocials->fetchAll();

    $stmtViews = $db->prepare('SELECT COUNT(*) FROM profile_views WHERE profile_id = ?');
    $stmtViews->execute([$profile['id']]);
    $totalViews = (int)$stmtViews->fetchColumn();

    $stmtViews7 = $db->prepare('SELECT COUNT(*) FROM profile_views WHERE profile_id = ? AND viewed_at >= NOW() - INTERVAL 7 DAY');
    $stmtViews7->execute([$profile['id']]);
    $views7d = (int)$stmtViews7->fetchColumn();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($profile['display_name']) ?> - <?= SITE_NAME ?></title>
        <link rel="stylesheet" href="css/style.css">
        <style>:root { --accent-color: <?= $accentColor ?>; }</style>
    </head>
    <body class="profile-page <?= $themeClass ?> <?= e($templateClass) ?>">
        <?php if (!empty($profile['bg_video'])): ?>
            <video class="bg-video-player" autoplay loop muted playsinline><source src="uploads/<?= e($profile['bg_video']) ?>" type="video/mp4"></video>
            <div class="bg-overlay"></div>
        <?php elseif (!empty($profile['background'])): ?>
            <div class="bg-image-layer" style="background-image:url('uploads/<?= e($profile['background']) ?>')"></div>
            <div class="bg-overlay"></div>
        <?php endif; ?>

        <?php if (!empty($profile['bg_audio'])): ?>
            <div class="audio-player-widget">
                <audio id="bg-audio" loop src="uploads/<?= e($profile['bg_audio']) ?>"></audio>
                <button id="audio-toggle-btn" class="audio-btn"><span id="audio-icon">🎵</span></button>
            </div>
        <?php endif; ?>

        <div class="profile-wrapper">
            <div class="profile-card <?= $gateMode ? 'profile-card-gated' : '' ?>">
                <?php if ($gateMode): ?>
                    <div class="gate-screen">
                        <div class="gate-pill">Gate enabled</div>
                        <h1><?= e($settings['gate_title']) ?></h1>
                        <p><?= nl2br(e($settings['gate_description'])) ?></p>
                        <div class="gate-stats">
                            <div><strong><?= number_format($totalViews) ?></strong><span>Total views</span></div>
                            <div><strong><?= number_format($views7d) ?></strong><span>Last 7 days</span></div>
                        </div>
                        <a href="?username=<?= e($profile['username']) ?>&enter=1" class="btn btn-primary btn-lg gate-button">Enter profile</a>
                    </div>
                <?php else: ?>
                    <div class="profile-header">
                        <div class="avatar-container">
                            <img src="<?= !empty($profile['avatar']) ? 'uploads/' . e($profile['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($profile['display_name']) ?>" alt="Avatar" class="profile-avatar">
                        </div>
                        <h1 class="profile-name"><?= e($profile['display_name']) ?></h1>
                        <p class="profile-handle">@<?= e($profile['username']) ?></p>
                        <?php if (!empty($badges)): ?>
                            <div class="badge-stack">
                                <?php foreach ($badges as $badge): $asset = getRoleBadgeAsset($badge); ?>
                                    <span class="profile-badge-box" title="<?= e($asset['label']) ?>">
                                        <img class="role-badge-img" src="<?= e(role_badge_img_src($badge)) ?>" alt="<?= e($asset['label']) ?>">
                                        <span class="badge-text"><?= e($badge) ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($profile['status'])): ?>
                            <div class="profile-status"><span class="status-dot"></span><span><?= e($profile['status']) ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($profile['bio'])): ?>
                            <p class="profile-bio"><?= nl2br(e($profile['bio'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($profile['location'])): ?>
                            <div class="profile-location">📍 <?= e($profile['location']) ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($socials)): ?>
                        <div class="socials-container">
                            <?php foreach ($socials as $s): ?>
                                <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener noreferrer" class="social-icon-btn" title="<?= e($s['platform']) ?>"><?= getSocialIconSvg($s['platform']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="profile-links">
                        <?php foreach ($links as $link): ?>
                            <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer" class="profile-link-btn" data-link-id="<?= $link['id'] ?>">
                                <span><?= e($link['title']) ?></span><span class="link-arrow">↗</span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="profile-footer">
                        <span class="branding-link">Powered by <strong><?= SITE_NAME ?></strong></span>
                        <small><?= e($settings['footer_note'] ?: 'A premium bio page experience.') ?></small>
                    </div>
                <?php endif; ?>

                <div class="profile-views-badge" title="Views"><span class="views-eye">👁</span><span><?= number_format($totalViews) ?></span></div>
            </div>
        </div>

        <script src="js/script.js"></script>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Premium no-code bio pages</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="landing-body landing-app">
    <nav class="landing-nav landing-nav-pro">
        <a href="index.php" class="brand-logo">Apex<span>Bio</span></a>
        <div class="nav-actions">
            <a href="#features" class="btn btn-outline">Features</a>
            <a href="#templates" class="btn btn-outline">Templates</a>
            <?php if (is_logged_in()): ?>
                <a href="dashboard.php" class="btn btn-primary">Open Dashboard</a>
            <?php else: ?>
                <a href="auth.php?action=login" class="btn btn-outline">Log In</a>
                <a href="auth.php?action=register" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="landing-shell">
        <section class="landing-hero">
            <div class="landing-copy">
                <span class="hero-badge">No-code profile builder</span>
                <h1>Build a page that feels like a product, not a link dump.</h1>
                <p class="hero-subtitle">Templates, role icons, gated access, analytics, and a clean editor anyone can use in minutes.</p>
                <div class="hero-actions">
                    <?php if (is_logged_in()): ?>
                        <a href="dashboard.php" class="btn btn-lg btn-primary">Open Dashboard</a>
                    <?php else: ?>
                        <a href="auth.php?action=register" class="btn btn-lg btn-primary">Create Your Profile</a>
                        <a href="auth.php?action=login" class="btn btn-lg btn-secondary">Log In</a>
                    <?php endif; ?>
                </div>
                <div class="landing-metrics">
                    <div><strong>10+</strong><span>templates</span></div>
                    <div><strong>Instant</strong><span>publish</span></div>
                    <div><strong>No code</strong><span>required</span></div>
                </div>
            </div>
            <div class="landing-hero-panel">
                <div class="mock-window"><span></span><span></span><span></span></div>
                <div class="mock-profile">
                    <div class="mock-avatar"></div>
                    <h3>Nanos</h3>
                    <p>@mihai</p>
                    <div class="mock-tags"><span>Verified</span><span>Founder</span></div>
                    <div class="mock-link"></div>
                    <div class="mock-link"></div>
                    <div class="mock-link"></div>
                </div>
            </div>
        </section>

        <section id="features" class="landing-section">
            <div class="section-header">
                <p class="eyebrow">Why it feels premium</p>
                <h2 class="text-gradient-sub">Everything is tuned for a clean, modern bio page.</h2>
            </div>
            <div class="modern-grid">
                <div class="glass-card"><span class="gradient-icon">✦</span><h3>No-code editor</h3><p>Edit text, badges, templates, and visibility from one place.</p></div>
                <div class="glass-card"><span class="gradient-icon">◉</span><h3>Instant publish</h3><p>Save once and the public page updates immediately.</p></div>
                <div class="glass-card"><span class="gradient-icon">⬢</span><h3>Gate mode</h3><p>Lock the page behind a click-to-enter screen whenever you want.</p></div>
                <div class="glass-card"><span class="gradient-icon">⌁</span><h3>Role badges</h3><p>Show small icon badges with hover tooltips instead of bulky labels.</p></div>
            </div>
        </section>

        <section id="templates" class="landing-section">
            <div class="section-header">
                <p class="eyebrow">Templates</p>
                <h2 class="text-gradient-sub">Pick a visual language that matches the person.</h2>
            </div>
            <div class="template-showcase">
                <div class="template-showcase-card template-a">Glass Luxe</div>
                <div class="template-showcase-card template-b">Studio Mono</div>
                <div class="template-showcase-card template-c">Neon Signal</div>
                <div class="template-showcase-card template-d">Solar Warm</div>
                <div class="template-showcase-card template-e">Ocean Deep</div>
                <div class="template-showcase-card template-f">Violet Pulse</div>
                <div class="template-showcase-card template-g">Velvet Night</div>
                <div class="template-showcase-card template-h">Amber Grid</div>
                <div class="template-showcase-card template-i">Rose Frame</div>
                <div class="template-showcase-card template-j">Minimal Paper</div>
            </div>
        </section>

        <section class="landing-section callout-band">
            <div>
                <p class="eyebrow">Built for creators</p>
                <h2>One profile. Many looks. Zero code.</h2>
            </div>
            <a href="<?= is_logged_in() ? 'dashboard.php' : 'auth.php?action=register' ?>" class="btn btn-primary btn-lg">Start building</a>
        </section>
    </main>
</body>
</html>
