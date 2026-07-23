<?php
require_once __DIR__ . '/backend/config.php';

$error = '';
$success = '';
$action = $_GET['action'] ?? 'login';

// Handle Logout
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: auth.php?action=login');
    exit;
}

// Redirect logged in users
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// Processing Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!check_rate_limit('auth_' . $ip, 10, 300)) {
            $error = 'Too many attempts. Please try again in 5 minutes.';
        } else if ($action === 'register') {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
                $error = 'Username must be 3-20 characters long (letters, numbers, underscores only).';
            } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else if (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } else {
                $db = get_db();
                $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error = 'Username or Email is already registered.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $db->beginTransaction();
                    try {
                        $stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
                        $stmt->execute([$username, $email, $hash]);
                        $userId = (int)$db->lastInsertId();

                        $stmt = $db->prepare('INSERT INTO profiles (user_id, display_name) VALUES (?, ?)');
                        $stmt->execute([$userId, $username]);

                        $db->commit();

                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $userId;
                        header('Location: dashboard.php');
                        exit;
                    } catch (Exception $e) {
                        $db->rollBack();
                        $error = 'An error occurred during registration. Please try again.';
                    }
                }
            }
        } else if ($action === 'login') {
            $loginInput = trim($_POST['login_input'] ?? '');
            $password   = $_POST['password'] ?? '';

            if (empty($loginInput) || empty($password)) {
                $error = 'Please fill in all fields.';
            } else {
                $db = get_db();
                $stmt = $db->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
                $stmt->execute([$loginInput, $loginInput]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ((int)$user['is_banned'] === 1) {
                        $error = 'Account suspended. Reason: ' . e($user['ban_reason'] ?? 'Violation of Terms');
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        header('Location: dashboard.php');
                        exit;
                    }
                } else {
                    $error = 'Invalid credentials provided.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $action === 'register' ? 'Register' : 'Login' ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="index.php" class="auth-brand">Apex<span>Bio</span></a>
                <h2><?= $action === 'register' ? 'Create Your Profile' : 'Welcome Back' ?></h2>
                <p><?= $action === 'register' ? 'Claim your personal handle and build your page.' : 'Log in to manage your link hub.' ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form action="auth.php?action=<?= e($action) ?>" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <?php if ($action === 'register'): ?>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-input" placeholder="e.g. alex" required pattern="[a-zA-Z0-9_]{3,20}">
                        <small class="form-help">Your page will be at <?= SITE_URL ?>/username</small>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="alex@example.com" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="At least 8 characters" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                <?php else: ?>
                    <div class="form-group">
                        <label for="login_input">Username or Email</label>
                        <input type="text" id="login_input" name="login_input" class="form-input" placeholder="Enter username or email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                <?php endif; ?>
            </form>

            <div class="auth-footer">
                <?php if ($action === 'register'): ?>
                    Already have a profile? <a href="auth.php?action=login">Sign In</a>
                <?php else: ?>
                    Don't have a profile yet? <a href="auth.php?action=register">Register Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
