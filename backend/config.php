<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', ' ');
define('DB_USER', ' ');
define('DB_PASS', ' ');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'ApexBio');
define('SITE_URL', 'http://localhost');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 15 * 1024 * 1024); // 15 MB limit for media uploads

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = get_db()->prepare('SELECT u.*, p.id AS profile_id, p.display_name, p.avatar, p.theme FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function is_admin(): bool {
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

function check_rate_limit(string $key, int $maxAttempts = 5, int $decaySeconds = 300): bool {
    $sessionKey = 'rate_' . md5($key);
    $now = time();
    if (!isset($_SESSION[$sessionKey]) || $now > $_SESSION[$sessionKey]['reset']) {
        $_SESSION[$sessionKey] = ['attempts' => 1, 'reset' => $now + $decaySeconds];
        return true;
    }
    if ($_SESSION[$sessionKey]['attempts'] >= $maxAttempts) {
        return false;
    }
    $_SESSION[$sessionKey]['attempts']++;
    return true;
}

function log_admin_action(int $adminId, string $action, string $targetType, ?int $targetId = null, ?string $details = null): void {
    $stmt = get_db()->prepare('INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$adminId, $action, $targetType, $targetId, $details]);
}

function handle_file_upload(array $file, string $prefix = 'img_'): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_FILE_SIZE) return null;

    $allowedTypes = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
        'video/mp4' => 'mp4', 'audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3'
    ];
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mimeType, $allowedTypes)) return null;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $ext = $allowedTypes[$mimeType];
    $filename = $prefix . bin2hex(random_bytes(12)) . '.' . $ext;
    $targetPath = UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    return null;
}

function ensure_profile_settings_column(PDO $db, string $column, string $definition): void {
    try {
        $stmt = $db->prepare('
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = \'profile_settings\'
              AND COLUMN_NAME = ?
        ');
        $stmt->execute([$column]);
        $exists = (int)$stmt->fetchColumn() > 0;
        if ($exists) {
            return;
        }

        $db->exec('ALTER TABLE profile_settings ADD COLUMN `' . $column . '` ' . $definition);
    } catch (Throwable $e) {
        // Best effort only. Older schemas may still work with the existing columns.
    }
}

function ensure_profile_settings(PDO $db, int $userId): void {
    try {
        $db->exec('
            CREATE TABLE IF NOT EXISTS profile_settings (
                user_id INT PRIMARY KEY,
                is_public TINYINT(1) NOT NULL DEFAULT 1,
                gate_enabled TINYINT(1) NOT NULL DEFAULT 0,
                gate_title VARCHAR(160) NOT NULL DEFAULT \'Enter the profile\',
                gate_description VARCHAR(255) NOT NULL DEFAULT \'Tap continue to unlock the public profile.\',
                footer_note VARCHAR(255) NOT NULL DEFAULT \'Powered by ApexBio\',
                template_key VARCHAR(64) NOT NULL DEFAULT \'glass\',
                theme_variant VARCHAR(64) NOT NULL DEFAULT \'midnight\',
                template_accent VARCHAR(24) NOT NULL DEFAULT \'#6366f1\',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');
    } catch (Throwable $e) {
        // If schema creation fails, continue and let the read path handle missing data.
    }

    try {
        $stmt = $db->prepare('INSERT IGNORE INTO profile_settings (user_id) VALUES (?)');
        $stmt->execute([$userId]);
    } catch (Throwable $e) {
        // Ignore bootstrap failures so dashboard rendering does not hard crash.
    }

    ensure_profile_settings_column($db, 'template_key', "VARCHAR(64) NOT NULL DEFAULT 'glass'");
    ensure_profile_settings_column($db, 'theme_variant', "VARCHAR(64) NOT NULL DEFAULT 'midnight'");
    ensure_profile_settings_column($db, 'template_accent', "VARCHAR(24) NOT NULL DEFAULT '#6366f1'");
}

function get_profile_settings(PDO $db, int $userId): array {
    ensure_profile_settings($db, $userId);
    $stmt = $db->prepare('SELECT * FROM profile_settings WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: [
        'is_public' => 1,
        'gate_enabled' => 0,
        'gate_title' => 'Enter the profile',
        'gate_description' => 'Tap continue to unlock the public profile.',
        'footer_note' => 'Powered by ApexBio',
        'template_key' => 'glass',
        'theme_variant' => 'midnight',
        'template_accent' => '#6366f1',
    ];
}
