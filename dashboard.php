<?php
require_once __DIR__ . '/backend/config.php';

if (!is_logged_in()) {
    header('Location: auth.php?action=login');
    exit;
}

$db = get_db();
$user = current_user();

$stmt = $db->prepare('SELECT * FROM profiles WHERE user_id = ?');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(500);
    echo 'Profile record missing.';
    exit;
}

ensure_profile_settings($db, (int)$user['id']);
$settings = get_profile_settings($db, (int)$user['id']);

$stmt = $db->prepare('SELECT * FROM links WHERE user_id = ? ORDER BY position ASC');
$stmt->execute([$user['id']]);
$links = $stmt->fetchAll();

$stmt = $db->prepare('SELECT COUNT(*) FROM profile_views WHERE profile_id = ?');
$stmt->execute([$profile['id']]);
$totalViews = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM profile_views WHERE profile_id = ? AND viewed_at >= NOW() - INTERVAL 24 HOUR');
$stmt->execute([$profile['id']]);
$views24h = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM profile_views WHERE profile_id = ? AND viewed_at >= NOW() - INTERVAL 7 DAY');
$stmt->execute([$profile['id']]);
$views7d = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT SUM(clicks) FROM links WHERE user_id = ?');
$stmt->execute([$user['id']]);
$totalClicks = (int)($stmt->fetchColumn() ?: 0);

function parse_badges(?string $raw): array {
    $parts = preg_split('/[,\n|]+/', (string)$raw);
    $parts = array_filter(array_map('trim', $parts));
    return array_values(array_unique($parts));
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

function role_badge_img_src(string $label): string {
    $asset = getRoleBadgeAsset($label);
    $svg = sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%%" stop-color="%s"/><stop offset="100%%" stop-color="#0f172a"/></linearGradient></defs><circle cx="32" cy="32" r="30" fill="url(#g)"/><text x="32" y="40" text-anchor="middle" font-size="26" font-family="Arial, sans-serif">%s</text></svg>',
        htmlspecialchars($asset['color'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        htmlspecialchars($asset['icon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
    );
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

$badgeList = parse_badges($profile['badge_label'] ?? '');
$profileUrl = 'index.php?username=' . urlencode($user['username']);
$templateCards = [
    ['key' => 'glass', 'label' => 'Glass', 'theme' => 'midnight', 'accent' => '#7c3aed', 'desc' => 'Soft blur with premium depth.'],
    ['key' => 'mono', 'label' => 'Mono', 'theme' => 'mono', 'accent' => '#e5e7eb', 'desc' => 'Clean black-and-white with sharp contrast.'],
    ['key' => 'solar', 'label' => 'Solar', 'theme' => 'sunset', 'accent' => '#f97316', 'desc' => 'Warm gradients for creators and artists.'],
    ['key' => 'neon', 'label' => 'Neon', 'theme' => 'cyber', 'accent' => '#06b6d4', 'desc' => 'Electric glow for edgy profiles.'],
    ['key' => 'velvet', 'label' => 'Velvet', 'theme' => 'midnight', 'accent' => '#ec4899', 'desc' => 'Dark luxe style with pink accent.'],
    ['key' => 'ocean', 'label' => 'Ocean', 'theme' => 'ocean', 'accent' => '#38bdf8', 'desc' => 'Cool deep blue with sleek contrast.'],
    ['key' => 'amber', 'label' => 'Amber', 'theme' => 'amber', 'accent' => '#f59e0b', 'desc' => 'Golden tone for warm personal brands.'],
    ['key' => 'rose', 'label' => 'Rose', 'theme' => 'rose', 'accent' => '#fb7185', 'desc' => 'Soft rosy palette with bold edges.'],
    ['key' => 'violet', 'label' => 'Violet', 'theme' => 'violet', 'accent' => '#a78bfa', 'desc' => 'Purple glow with studio vibes.'],
    ['key' => 'paper', 'label' => 'Paper', 'theme' => 'mono', 'accent' => '#e5e7eb', 'desc' => 'Minimal clean black-on-paper style.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="brand-logo">Apex<span>Bio</span></a>
            </div>
            <nav class="sidebar-menu">
                <button class="nav-item active" data-tab="profile"><span>Profile & Design</span></button>
                <button class="nav-item" data-tab="visibility"><span>Visibility & Gate</span></button>
                <button class="nav-item" data-tab="links"><span>Links & Buttons</span></button>
                <button class="nav-item" data-tab="analytics"><span>Analytics</span></button>
                <button class="nav-item" data-tab="account"><span>Account Settings</span></button>
                <?php if (is_admin()): ?>
                    <a href="admin.php" class="nav-item admin-link" style="color:#ef4444;"><span>Admin Console</span></a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <img src="<?= !empty($profile['avatar']) ? 'uploads/' . e($profile['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($profile['display_name']) ?>" alt="Avatar" class="mini-avatar">
                    <div>
                        <div class="user-name"><?= e($profile['display_name']) ?></div>
                        <a href="<?= e($profileUrl) ?>" target="_blank" class="view-profile-link">Open Page</a>
                    </div>
                </div>
                <a href="auth.php?action=logout" class="btn btn-outline btn-sm btn-block">Logout</a>
            </div>
        </aside>

        <main class="dashboard-main editor-shell">
            <div class="editor-topbar dash-card">
                <div>
                    <p class="eyebrow">No-code page builder</p>
                    <h2>Build your profile visually</h2>
                    <p>Change text, visibility, templates, colors, and order without touching code.</p>
                </div>
                <div class="editor-actions">
                    <a href="<?= e($profileUrl) ?>" target="_blank" class="btn btn-secondary">Open public page</a>
                </div>
            </div>

            <section id="tab-profile" class="tab-content active">
                <div class="dash-header">
                    <h2>Profile Customization</h2>
                    <p>Edit your public identity, template, and colors.</p>
                </div>
                <form id="form-profile-general" class="dash-card ajax-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="template_key" value="<?= e($settings['template_key'] ?? 'glass') ?>">

                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label>Display Name</label>
                            <input type="text" name="display_name" class="form-input" value="<?= e($profile['display_name']) ?>" required>
                        </div>
                        <div class="form-group flex-1">
                            <label>Status Message</label>
                            <input type="text" name="status" class="form-input" value="<?= e($profile['status']) ?>" placeholder="e.g. Building web platforms">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Bio / Description</label>
                        <textarea name="bio" class="form-input" rows="3" placeholder="Tell the world about yourself..."><?= e($profile['bio']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label>Location</label>
                            <input type="text" name="location" class="form-input" value="<?= e($profile['location']) ?>" placeholder="City, Country">
                        </div>
                        <div class="form-group flex-1">
                            <label>Badges</label>
                            <div class="badge-readonly">
                                <?php if (!empty($badgeList)): ?>
                                    <div class="badge-stack">
                                        <?php foreach ($badgeList as $badge): ?>
                                            <?php $asset = getRoleBadgeAsset($badge); ?>
                                            <span class="profile-badge-box" title="<?= e($asset['label']) ?>">
                                                <img class="role-badge-img" src="<?= e(role_badge_img_src($badge)) ?>" alt="<?= e($asset['label']) ?>">
                                                <span class="badge-text"><?= e($badge) ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="helper-text">Badges are assigned by an admin.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label>Theme Preset</label>
                            <select name="theme" class="form-input">
                                <option value="midnight" <?= $profile['theme'] === 'midnight' ? 'selected' : '' ?>>Midnight Obsidian</option>
                                <option value="cyber" <?= $profile['theme'] === 'cyber' ? 'selected' : '' ?>>Cyberpunk Neon</option>
                                <option value="emerald" <?= $profile['theme'] === 'emerald' ? 'selected' : '' ?>>Emerald Minimal</option>
                                <option value="sunset" <?= $profile['theme'] === 'sunset' ? 'selected' : '' ?>>Sunset Boulevard</option>
                                <option value="rose" <?= $profile['theme'] === 'rose' ? 'selected' : '' ?>>Rose Atelier</option>
                                <option value="amber" <?= $profile['theme'] === 'amber' ? 'selected' : '' ?>>Amber Field</option>
                                <option value="ocean" <?= $profile['theme'] === 'ocean' ? 'selected' : '' ?>>Ocean Depths</option>
                                <option value="mono" <?= $profile['theme'] === 'mono' ? 'selected' : '' ?>>Mono Paper</option>
                                <option value="violet" <?= $profile['theme'] === 'violet' ? 'selected' : '' ?>>Violet Pulse</option>
                            </select>
                        </div>
                        <div class="form-group flex-1">
                            <label>Accent Color</label>
                            <input type="color" name="accent_color" class="form-input color-picker" value="<?= e($profile['accent_color'] ?: '#6366f1') ?>">
                        </div>
                    </div>

                    <div class="template-strip">
                        <?php foreach ($templateCards as $template): ?>
                            <button type="button" class="template-card" data-template-key="<?= e($template['key']) ?>" data-theme="<?= e($template['theme']) ?>" data-accent="<?= e($template['accent']) ?>">
                                <span class="template-swatch" style="--swatch: <?= e($template['accent']) ?>;"></span>
                                <strong><?= e($template['label']) ?></strong>
                                <small><?= e($template['desc']) ?></small>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <hr class="divider">
                    <h3 style="margin-bottom:15px;font-size:1.1rem;">Media & Assets</h3>
                    <div class="form-row">
                        <div class="form-group flex-1 file-upload-wrapper"><label>Avatar Image</label><input type="file" name="avatar_file" class="form-input" accept="image/*"></div>
                        <div class="form-group flex-1 file-upload-wrapper"><label>Static Background</label><input type="file" name="background_file" class="form-input" accept="image/*"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1 file-upload-wrapper"><label>Video Background</label><input type="file" name="bg_video_file" class="form-input" accept="video/mp4"></div>
                        <div class="form-group flex-1 file-upload-wrapper"><label>Background Music</label><input type="file" name="bg_audio_file" class="form-input" accept="audio/mpeg, audio/mp3"></div>
                    </div>

                    <div class="profile-quick-cards">
                        <div class="quick-stat"><span>Profile Views</span><strong><?= number_format($totalViews) ?></strong></div>
                        <div class="quick-stat"><span>Clicks</span><strong><?= number_format($totalClicks) ?></strong></div>
                        <div class="quick-stat"><span>Badges</span><strong><?= count($badgeList) ?></strong></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </section>

            <section id="tab-visibility" class="tab-content">
                <div class="dash-header">
                    <h2>Visibility & Gate</h2>
                    <p>Control whether your page is public and whether visitors must click to enter.</p>
                </div>
                <form id="form-visibility" class="dash-card ajax-form">
                    <input type="hidden" name="action" value="update_visibility">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <label class="toggle-row"><input type="checkbox" name="is_public" <?= !empty($settings['is_public']) ? 'checked' : '' ?>> Public profile visible on search and direct link</label>
                    <label class="toggle-row"><input type="checkbox" name="gate_enabled" <?= !empty($settings['gate_enabled']) ? 'checked' : '' ?>> Require a click before showing the page</label>
                    <p class="helper-text">Gate is bypassed for your own account so you can always view your page directly.</p>
                    <div class="visibility-status">
                        <strong>Current:</strong> <?= !empty($settings['gate_enabled']) ? 'Gate active' : 'Gate off' ?> · <?= !empty($settings['is_public']) ? 'Public page on' : 'Public page hidden' ?>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:14px;">Save Visibility</button>
                </form>

                <form id="form-gate-copy" class="dash-card ajax-form" style="margin-top:20px;">
                    <input type="hidden" name="action" value="update_gate_copy">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <label>Gate title</label>
                    <input type="text" name="gate_title" class="form-input" value="<?= e($settings['gate_title']) ?>">
                    <label style="margin-top:12px;">Gate description</label>
                    <textarea name="gate_description" class="form-input" rows="3"><?= e($settings['gate_description']) ?></textarea>
                    <label style="margin-top:12px;">Footer note</label>
                    <input type="text" name="footer_note" class="form-input" value="<?= e($settings['footer_note']) ?>">
                    <button type="submit" class="btn btn-primary" style="margin-top:15px;">Save Gate Copy</button>
                </form>
            </section>

            <section id="tab-links" class="tab-content">
                <div class="dash-header">
                    <h2>Custom Links</h2>
                    <p>Add, style, and prioritize external links displayed on your page.</p>
                </div>
                <div class="dash-card">
                    <form id="form-add-link" class="ajax-form">
                        <input type="hidden" name="action" value="save_link">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <div class="form-row">
                            <div class="form-group flex-2"><input type="text" name="title" class="form-input" placeholder="Link Title" required></div>
                            <div class="form-group flex-3"><input type="url" name="url" class="form-input" placeholder="https://example.com" required></div>
                            <button type="submit" class="btn btn-primary">Add Link</button>
                        </div>
                    </form>
                </div>
                <div class="links-manager-list" id="links-list">
                    <?php foreach ($links as $link): ?>
                        <div class="link-item-row" data-id="<?= $link['id'] ?>">
                            <div class="link-drag-handle">Drag</div>
                            <div class="link-details">
                                <strong><?= e($link['title']) ?></strong>
                                <small><?= e($link['url']) ?> (<?= $link['clicks'] ?> clicks)</small>
                            </div>
                            <div class="link-actions">
                                <button class="btn btn-sm btn-danger delete-link-btn" data-id="<?= $link['id'] ?>">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section id="tab-analytics" class="tab-content">
                <div class="dash-header">
                    <h2>Analytics</h2>
                    <p>Your profile performance at a glance.</p>
                </div>
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-value"><?= number_format($totalViews) ?></div><div class="stat-label">Total views</div></div>
                    <div class="stat-card"><div class="stat-value"><?= number_format($views24h) ?></div><div class="stat-label">Last 24h</div></div>
                    <div class="stat-card"><div class="stat-value"><?= number_format($views7d) ?></div><div class="stat-label">Last 7d</div></div>
                    <div class="stat-card"><div class="stat-value"><?= number_format($totalClicks) ?></div><div class="stat-label">Link clicks</div></div>
                </div>
            </section>

            <section id="tab-account" class="tab-content">
                <div class="dash-header">
                    <h2>Account Settings</h2>
                    <p>Small but useful defaults for your public page.</p>
                </div>
                <div class="dash-card">
                    <p><strong>Profile URL:</strong> <a href="<?= e($profileUrl) ?>" target="_blank"><?= e($profileUrl) ?></a></p>
                    <p style="margin-top:10px;"><strong>Visibility:</strong> <?= !empty($settings['is_public']) ? 'Public' : 'Hidden' ?></p>
                    <p style="margin-top:10px;"><strong>Gate:</strong> <?= !empty($settings['gate_enabled']) ? 'Enabled' : 'Disabled' ?></p>
                    <p style="margin-top:10px;"><strong>Template:</strong> <?= e($settings['template_key'] ?? 'glass') ?></p>
                </div>
            </section>
        </main>
    </div>

    <div id="toast-container"></div>
    <script src="js/script.js"></script>
</body>
</html>
