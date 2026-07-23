<?php
require_once __DIR__ . '/backend/config.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: index.php');
    exit;
}

$db = get_db();
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editUser = null;
$editProfile = null;
$editLinks = [];

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
        'Partner' => ['icon' => '🤝', 'color' => '#38bdf8', 'label' => 'Partner'],
        'Artist' => ['icon' => '🖼️', 'color' => '#f472b6', 'label' => 'Artist'],
        'Streamer' => ['icon' => '📺', 'color' => '#ef4444', 'label' => 'Streamer'],
        'VIP' => ['icon' => '⭐', 'color' => '#eab308', 'label' => 'VIP'],
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

if ($editId) {
    $stmt = $db->prepare('SELECT id, username, email, role, created_at, is_banned FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();

    if ($editUser) {
        $stmt = $db->prepare('SELECT * FROM profiles WHERE user_id = ?');
        $stmt->execute([$editId]);
        $editProfile = $stmt->fetch();

        $stmt = $db->prepare('SELECT * FROM links WHERE user_id = ?');
        $stmt->execute([$editId]);
        $editLinks = $stmt->fetchAll();
    }
} else {
    $stmt = $db->query('
        SELECT u.id, u.username, u.email, u.role, u.is_banned, p.display_name, p.avatar, p.badge_label 
        FROM users u 
        LEFT JOIN profiles p ON u.id = p.user_id 
        ORDER BY u.created_at DESC
    ');
    $users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Console - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-body">
    <div class="admin-topbar">
        <div class="admin-brand">Apex<span>Admin</span></div>
        <div class="admin-nav">
            <a href="admin.php" class="btn btn-outline btn-sm">Users Directory</a>
            <a href="dashboard.php" class="btn btn-primary btn-sm">Back to Dashboard</a>
        </div>
    </div>

    <div class="admin-container">
        
        <?php if ($editUser && $editProfile): ?>
            <!-- ==============================
                 SINGLE USER MANAGEMENT VIEW
            =============================== -->
            <div class="admin-page-header">
                <h2>Editing User: @<?= e($editUser['username']) ?></h2>
                <a href="index.php?username=<?= e($editUser['username']) ?>" target="_blank" class="btn btn-outline btn-sm">View Public Profile</a>
            </div>

            <div class="admin-grid-layout">
                <!-- Left Column: Edit Form -->
                <div class="admin-card">
                    <h3>Profile Information</h3>
                    <form class="ajax-form" style="margin-top:15px;">
                        <input type="hidden" name="action" value="admin_update_profile">
                        <input type="hidden" name="target_id" value="<?= $editUser['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="form-group">
                            <label>Display Name</label>
                            <input type="text" name="display_name" class="form-input" value="<?= e($editProfile['display_name']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Assign Badges / Roles</label>
                            <input
                                type="text"
                                name="badge_label"
                                class="form-input"
                                value="<?= e($editProfile['badge_label']) ?>"
                                placeholder="Verified, Founder, Creator"
                            >
                            <small class="admin-helper">Users cannot change these badges. Separate multiple entries with commas.</small>
                            <div class="admin-badge-suggestions">
                                <span>Verified</span>
                                <span>Founder</span>
                                <span>Creator</span>
                                <span>Developer</span>
                                <span>Designer</span>
                                <span>VIP</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Status Message</label>
                            <input type="text" name="status" class="form-input" value="<?= e($editProfile['status']) ?>">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Save Details</button>
                    </form>
                </div>

                <!-- Right Column: Media, Links & Danger -->
                <div class="admin-side-col">
                    
                    <div class="admin-card">
                        <h3>Media Controls (Reset)</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">Force remove inappropriate media.</p>
                        <div class="media-reset-grid">
                            <button class="btn btn-secondary btn-sm admin-reset-btn" data-id="<?= $editUser['id'] ?>" data-type="avatar">Reset Avatar</button>
                            <button class="btn btn-secondary btn-sm admin-reset-btn" data-id="<?= $editUser['id'] ?>" data-type="background">Reset BG Image</button>
                            <button class="btn btn-secondary btn-sm admin-reset-btn" data-id="<?= $editUser['id'] ?>" data-type="bg_video">Reset Video</button>
                            <button class="btn btn-secondary btn-sm admin-reset-btn" data-id="<?= $editUser['id'] ?>" data-type="bg_audio">Reset Audio</button>
                        </div>
                    </div>

                    <div class="admin-card">
                        <h3>Manage Links</h3>
                        <div class="admin-links-list">
                            <?php if(empty($editLinks)): ?>
                                <p style="color:var(--text-muted); font-size:0.85rem;">No links added by user.</p>
                            <?php else: ?>
                                <?php foreach($editLinks as $l): ?>
                                    <div class="admin-link-item">
                                        <div class="link-text">
                                            <strong><?= e($l['title']) ?></strong>
                                            <span><?= e($l['url']) ?></span>
                                        </div>
                                        <button class="btn btn-danger btn-sm admin-del-link-btn" data-id="<?= $l['id'] ?>">Delete</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="admin-card danger-zone">
                        <h3>Danger Zone</h3>
                        <div class="danger-actions">
                            <button class="btn <?= $editUser['is_banned'] ? 'btn-success' : 'btn-danger' ?> btn-block toggle-ban-btn" data-id="<?= $editUser['id'] ?>" data-status="<?= $editUser['is_banned'] ? '0' : '1' ?>">
                                <?= $editUser['is_banned'] ? 'Unban Account' : 'Suspend Account' ?>
                            </button>
                            
                            <?php if ($editUser['role'] !== 'admin'): ?>
                                <button class="btn btn-outline btn-block delete-user-btn" data-id="<?= $editUser['id'] ?>" style="border-color: var(--danger); color: var(--danger); margin-top:10px;">
                                    Permanently Delete User
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ==============================
                 ALL USERS DIRECTORY
            =============================== -->
            <div class="admin-page-header">
                <h2>Users Directory</h2>
                <p>Manage all accounts on the platform.</p>
            </div>

            <div class="admin-card" style="padding: 0; overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role / Badge</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="admin-td-user">
                                        <img src="<?= !empty($u['avatar']) ? 'uploads/' . e($u['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($u['display_name']) ?>" class="mini-avatar" alt="Avatar">
                                        <div>
                                            <strong><?= e($u['display_name']) ?></strong>
                                            <small>@<?= e($u['username']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($u['email']) ?></td>
                                <td>
                                    <span class="role-badge <?= $u['role'] === 'admin' ? 'admin-role' : '' ?>">
                                        <?= e(strtoupper($u['role'])) ?>
                                    </span>
                                    <?php $badges = parse_badges($u['badge_label'] ?? ''); ?>
                                    <?php if($badges): ?>
                                        <div class="admin-badge-stack">
                                            <?php foreach ($badges as $badge): ?>
                                                <?php $asset = getRoleBadgeAsset($badge); ?>
                                                <span class="profile-badge-box admin-badge-chip" title="<?= e($asset['label']) ?>">
                                                    <img class="role-badge-img" src="<?= e(role_badge_img_src($badge)) ?>" alt="<?= e($asset['label']) ?>">
                                                    <span class="badge-text"><?= e($badge) ?></span>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['is_banned']): ?>
                                        <span class="status-pill banned">Banned</span>
                                    <?php else: ?>
                                        <span class="status-pill active">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <a href="admin.php?edit=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">Manage</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <div id="toast-container"></div>
    <script src="js/script.js"></script>
</body>
</html>
