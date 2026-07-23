<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

// Public click tracking
if ($action === 'track_click') {
    $linkId = filter_input(INPUT_POST, 'link_id', FILTER_VALIDATE_INT);
    if ($linkId) {
        $db = get_db();
        $stmt = $db->prepare('UPDATE links SET clicks = clicks + 1 WHERE id = ?');
        $stmt->execute([$linkId]);
        json_response(['success' => true]);
    }
    json_response(['error' => 'Invalid link ID.'], 400);
}

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized access.'], 401);
}

$user = current_user();
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf($token)) {
    json_response(['error' => 'Invalid CSRF token.'], 403);
}

$db = get_db();

function sanitize_hex_color(string $value, string $fallback = '#6366f1'): string {
    $value = trim($value);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
        return strtoupper($value);
    }
    return $fallback;
}

function normalize_theme_variant(string $value): string {
    $allowed = ['midnight', 'cyber', 'emerald', 'sunset', 'rose', 'amber', 'ocean', 'mono', 'violet'];
    $value = strtolower(trim($value));
    return in_array($value, $allowed, true) ? $value : 'midnight';
}

function normalize_template_key(string $value): string {
    $allowed = ['glass', 'mono', 'solar', 'neon', 'velvet', 'ocean', 'amber', 'rose', 'violet', 'paper'];
    $value = strtolower(trim($value));
    return in_array($value, $allowed, true) ? $value : 'glass';
}

function normalize_badge_label_list(string $value): string {
    $parts = preg_split('/[,\n|]+/', $value);
    $parts = array_filter(array_map('trim', $parts), static fn ($item) => $item !== '');
    $parts = array_values(array_unique($parts));
    return implode(', ', $parts);
}

switch ($action) {

    case 'update_profile':
        $displayName = trim($_POST['display_name'] ?? '');
        $status      = trim($_POST['status'] ?? '');
        $bio         = trim($_POST['bio'] ?? '');
        $location    = trim($_POST['location'] ?? '');
        $theme       = normalize_theme_variant((string)($_POST['theme'] ?? 'midnight'));
        $accentColor = sanitize_hex_color((string)($_POST['accent_color'] ?? '#6366f1'));
        $templateKey = normalize_template_key((string)($_POST['template_key'] ?? 'glass'));

        if (empty($displayName)) {
            json_response(['error' => 'Display name required.'], 400);
        }

        $avatarName   = !empty($_FILES['avatar_file']['name']) ? handle_file_upload($_FILES['avatar_file'], 'avatar_') : null;
        $bgName       = !empty($_FILES['background_file']['name']) ? handle_file_upload($_FILES['background_file'], 'bg_') : null;
        $bgVideoName  = !empty($_FILES['bg_video_file']['name']) ? handle_file_upload($_FILES['bg_video_file'], 'vid_') : null;
        $bgAudioName  = !empty($_FILES['bg_audio_file']['name']) ? handle_file_upload($_FILES['bg_audio_file'], 'aud_') : null;

        $sql = 'UPDATE profiles SET display_name = ?, status = ?, bio = ?, location = ?, theme = ?, accent_color = ?';
        $params = [$displayName, $status, $bio, $location, $theme, $accentColor];

        if ($avatarName) { $sql .= ', avatar = ?'; $params[] = $avatarName; }
        if ($bgName) { $sql .= ', background = ?'; $params[] = $bgName; }
        if ($bgVideoName) { $sql .= ', bg_video = ?'; $params[] = $bgVideoName; }
        if ($bgAudioName) { $sql .= ', bg_audio = ?'; $params[] = $bgAudioName; }

        $sql .= ' WHERE user_id = ?';
        $params[] = $user['id'];

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        ensure_profile_settings($db, (int)$user['id']);
        $stmt = $db->prepare('UPDATE profile_settings SET template_key = ?, theme_variant = ?, template_accent = ? WHERE user_id = ?');
        $stmt->execute([$templateKey, $theme, $accentColor, $user['id']]);
        
        json_response(['success' => true, 'message' => 'Profile updated successfully!']);
        break;

    case 'save_link':
        $linkId = filter_input(INPUT_POST, 'link_id', FILTER_VALIDATE_INT);
        $title  = trim($_POST['title'] ?? '');
        $url    = trim($_POST['url'] ?? '');

        if (empty($title) || empty($url)) {
            json_response(['error' => 'Title and URL are required.'], 400);
        }

        if ($linkId) {
            $stmt = $db->prepare('UPDATE links SET title = ?, url = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$title, $url, $linkId, $user['id']]);
        } else {
            $stmtPos = $db->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM links WHERE user_id = ?');
            $stmtPos->execute([$user['id']]);
            $nextPos = (int)$stmtPos->fetchColumn();

            $stmt = $db->prepare('INSERT INTO links (user_id, title, url, position) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user['id'], $title, $url, $nextPos]);
        }

        json_response(['success' => true, 'message' => 'Link saved successfully.']);
        break;

    case 'delete_link':
        $linkId = filter_input(INPUT_POST, 'link_id', FILTER_VALIDATE_INT);
        if ($linkId) {
            $stmt = $db->prepare('DELETE FROM links WHERE id = ? AND user_id = ?');
            $stmt->execute([$linkId, $user['id']]);
            json_response(['success' => true, 'message' => 'Link deleted.']);
        }
        json_response(['error' => 'Invalid link ID.'], 400);
        break;

    case 'reorder_links':
        $order = $_POST['order'] ?? [];
        if (is_array($order)) {
            $stmt = $db->prepare('UPDATE links SET position = ? WHERE id = ? AND user_id = ?');
            foreach ($order as $position => $linkId) {
                $stmt->execute([(int)$position, (int)$linkId, $user['id']]);
            }
            json_response(['success' => true]);
        }
        json_response(['error' => 'Invalid order data.'], 400);
        break;

    case 'save_socials':
        $platform = trim($_POST['platform'] ?? '');
        $url      = trim($_POST['url'] ?? '');

        if (empty($platform) || empty($url)) {
            json_response(['error' => 'Platform and URL required.'], 400);
        }

        $stmtCheck = $db->prepare('SELECT id FROM social_links WHERE user_id = ? AND platform = ?');
        $stmtCheck->execute([$user['id'], $platform]);
        $existing = $stmtCheck->fetchColumn();

        if ($existing) {
            $stmt = $db->prepare('UPDATE social_links SET url = ? WHERE id = ?');
            $stmt->execute([$url, $existing]);
        } else {
            $stmt = $db->prepare('INSERT INTO social_links (user_id, platform, url) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $platform, $url]);
        }

        json_response(['success' => true, 'message' => 'Social link updated.']);
        break;

    case 'delete_social':
        $socialId = filter_input(INPUT_POST, 'social_id', FILTER_VALIDATE_INT);
        if ($socialId) {
            $stmt = $db->prepare('DELETE FROM social_links WHERE id = ? AND user_id = ?');
            $stmt->execute([$socialId, $user['id']]);
            json_response(['success' => true]);
        }
        json_response(['error' => 'Invalid ID.'], 400);
        break;

    case 'admin_update_profile':
        if (!is_admin()) json_response(['error' => 'Access denied.'], 403);
        
        $targetId    = (int)$_POST['target_id'];
        $displayName = trim($_POST['display_name'] ?? '');
        $badgeLabel  = normalize_badge_label_list((string)($_POST['badge_label'] ?? ''));
        $status      = trim($_POST['status'] ?? '');

        $stmt = $db->prepare('UPDATE profiles SET display_name = ?, badge_label = ?, status = ? WHERE user_id = ?');
        $stmt->execute([$displayName, $badgeLabel, $status, $targetId]);
        
        json_response(['success' => true, 'message' => 'Profile updated successfully by Admin.']);
        break;

    case 'update_visibility':
        ensure_profile_settings($db, (int)$user['id']);
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $gateEnabled = isset($_POST['gate_enabled']) ? 1 : 0;
        $stmt = $db->prepare('UPDATE profile_settings SET is_public = ?, gate_enabled = ? WHERE user_id = ?');
        $stmt->execute([$isPublic, $gateEnabled, $user['id']]);
        json_response(['success' => true, 'message' => 'Visibility settings saved.']);
        break;

    case 'update_gate_copy':
        ensure_profile_settings($db, (int)$user['id']);
        $gateTitle = trim($_POST['gate_title'] ?? 'Enter the profile');
        $gateDesc = trim($_POST['gate_description'] ?? 'Tap continue to unlock the public profile.');
        $footerNote = trim($_POST['footer_note'] ?? 'Powered by ApexBio');
        $stmt = $db->prepare('UPDATE profile_settings SET gate_title = ?, gate_description = ?, footer_note = ? WHERE user_id = ?');
        $stmt->execute([$gateTitle, $gateDesc, $footerNote, $user['id']]);
        json_response(['success' => true, 'message' => 'Gate copy updated.']);
        break;

    case 'apply_template':
        ensure_profile_settings($db, (int)$user['id']);
        $templateKey = normalize_template_key((string)($_POST['template_key'] ?? 'glass'));
        $theme = normalize_theme_variant((string)($_POST['theme'] ?? 'midnight'));
        $accentColor = sanitize_hex_color((string)($_POST['accent_color'] ?? '#6366f1'));
        $stmt = $db->prepare('UPDATE profiles SET theme = ?, accent_color = ? WHERE user_id = ?');
        $stmt->execute([$theme, $accentColor, $user['id']]);
        $stmt = $db->prepare('UPDATE profile_settings SET template_key = ?, theme_variant = ?, template_accent = ? WHERE user_id = ?');
        $stmt->execute([$templateKey, $theme, $accentColor, $user['id']]);
        json_response(['success' => true, 'message' => 'Template applied.']);
        break;

    case 'track_view_gate':
        $profileId = filter_input(INPUT_POST, 'profile_id', FILTER_VALIDATE_INT);
        if ($profileId) {
            $stmt = $db->prepare('INSERT INTO profile_views (profile_id, ip_hash) VALUES (?, ?)');
            $stmt->execute([$profileId, hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''))]);
            json_response(['success' => true]);
        }
        json_response(['error' => 'Invalid profile ID.'], 400);
        break;

    case 'admin_reset_media':
        if (!is_admin()) json_response(['error' => 'Access denied.'], 403);
        
        $targetId = (int)$_POST['target_id'];
        $type     = $_POST['media_type'];
        
        $validColumns = ['avatar', 'background', 'bg_video', 'bg_audio'];
        if (!in_array($type, $validColumns)) {
            json_response(['error' => 'Invalid media column.'], 400);
        }

        $stmt = $db->prepare("SELECT $type FROM profiles WHERE user_id = ?");
        $stmt->execute([$targetId]);
        $filename = $stmt->fetchColumn();
        if ($filename && file_exists(UPLOAD_DIR . $filename)) {
            @unlink(UPLOAD_DIR . $filename);
        }

        $stmt = $db->prepare("UPDATE profiles SET $type = NULL WHERE user_id = ?");
        $stmt->execute([$targetId]);
        
        json_response(['success' => true, 'message' => ucfirst($type) . ' has been permanently reset.']);
        break;

    case 'admin_delete_link':
        if (!is_admin()) json_response(['error' => 'Access denied.'], 403);
        
        $linkId = (int)$_POST['link_id'];
        $stmt = $db->prepare('DELETE FROM links WHERE id = ?');
        $stmt->execute([$linkId]);
        
        json_response(['success' => true, 'message' => 'User link removed.']);
        break;

    case 'admin_ban':
        if (!is_admin()) json_response(['error' => 'Access denied.'], 403);
        
        $targetId  = (int)($_POST['target_id'] ?? 0);
        $banStatus = (int)($_POST['ban_status'] ?? 1);

        $check = $db->prepare('SELECT role FROM users WHERE id = ?');
        $check->execute([$targetId]);
        if ($check->fetchColumn() === 'admin') {
            json_response(['error' => 'Administrators cannot be banned.'], 400);
        }

        $stmt = $db->prepare('UPDATE users SET is_banned = ? WHERE id = ?');
        $stmt->execute([$banStatus, $targetId]);
        
        json_response(['success' => true, 'message' => $banStatus ? 'User account suspended.' : 'User account restored.']);
        break;

    case 'admin_delete_user':
        if (!is_admin()) json_response(['error' => 'Access denied.'], 403);
        
        $targetId = (int)($_POST['target_id'] ?? 0);
        
        $check = $db->prepare('SELECT role FROM users WHERE id = ?');
        $check->execute([$targetId]);
        if ($check->fetchColumn() === 'admin') {
            json_response(['error' => 'Administrators cannot be deleted.'], 400);
        }

        $db->prepare('DELETE FROM social_links WHERE user_id = ?')->execute([$targetId]);
        $db->prepare('DELETE FROM links WHERE user_id = ?')->execute([$targetId]);
        $db->prepare('DELETE FROM profile_views WHERE profile_id IN (SELECT id FROM profiles WHERE user_id = ?)')->execute([$targetId]);
        $db->prepare('DELETE FROM profiles WHERE user_id = ?')->execute([$targetId]);
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
        
        json_response(['success' => true, 'message' => 'User and all associated data completely deleted.']);
        break;

    default:
        json_response(['error' => 'Endpoint action not recognized.'], 400);
}
