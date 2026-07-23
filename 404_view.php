<?php
require_once __DIR__ . '/backend/config.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Not Found - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card" style="text-align: center;">
            <h2>404 - Profile Not Found</h2>
            <p style="color: var(--text-muted); margin: 15px 0;">The user profile you requested does not exist or has been suspended.</p>
            <a href="index.php" class="btn btn-primary">Return Home</a>
        </div>
    </div>
</body>
</html>
