<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';

// Load branding from the first villa (or settings for backward compatibility)
$branding_query = @mysqli_query($conn, "SELECT * FROM villas LIMIT 1");
if (!$branding_query || mysqli_num_rows($branding_query) === 0) {
    $branding_query = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
}
$branding = $branding_query ? mysqli_fetch_assoc($branding_query) : [];

$global_company_name = $branding['company_name'] ?? 'VillaRS';
$global_logo = $branding['logo_path'] ?? '';
$global_favicon = $branding['favicon_path'] ?? '';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT u.id, u.username, u.password, r.role_name 
              FROM users u 
              JOIN user_roles r ON u.role_id = r.id 
              WHERE u.username = '$username' AND u.status = 1";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role_name'];

            // Set default villa
            $uid = (int)$row['id'];
            $vq = mysqli_query($conn, "SELECT villa_id FROM user_villas WHERE user_id = $uid AND is_default = 1 LIMIT 1");
            if ($vr = mysqli_fetch_assoc($vq)) {
                $_SESSION['villa_id'] = (int)$vr['villa_id'];
            } else {
                $vq2 = mysqli_query($conn, "SELECT villa_id FROM user_villas WHERE user_id = $uid LIMIT 1");
                if ($vr2 = mysqli_fetch_assoc($vq2)) {
                    $_SESSION['villa_id'] = (int)$vr2['villa_id'];
                }
            }

            header('Location: dashboard.php');
            exit();
        }
    }
    $error = 'Invalid username or password!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($global_company_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if ($global_favicon): ?>
    <link rel="icon" href="uploads/<?= htmlspecialchars($global_favicon) ?>">
    <?php else: ?>
    <link rel="icon" href="assets/images/favicon.png">
    <?php endif; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0c111d 0%, #1a2332 50%, #0c111d 100%);
            overflow: hidden;
            position: relative;
        }

        .animation-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            animation: float 25s infinite ease-in-out;
        }

        .circle:nth-child(1) {
            width: 400px;
            height: 400px;
            background: #6366f1;
            top: -150px;
            left: -100px;
            animation-delay: 0s;
        }

        .circle:nth-child(2) {
            width: 300px;
            height: 300px;
            background: #8b5cf6;
            bottom: -100px;
            right: -100px;
            animation-delay: -5s;
        }

        .circle:nth-child(3) {
            width: 200px;
            height: 200px;
            background: #06b6d4;
            top: 40%;
            left: 5%;
            animation-delay: -10s;
        }

        .circle:nth-child(4) {
            width: 250px;
            height: 250px;
            background: #6366f1;
            bottom: 15%;
            right: 10%;
            animation-delay: -15s;
        }

        .circle:nth-child(5) {
            width: 150px;
            height: 150px;
            background: #8b5cf6;
            top: 15%;
            right: 20%;
            animation-delay: -8s;
        }

        .circle:nth-child(6) {
            width: 180px;
            height: 180px;
            background: #06b6d4;
            bottom: 35%;
            left: 8%;
            animation-delay: -12s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            25% {
                transform: translate(80px, 120px) scale(1.1);
            }
            50% {
                transform: translate(120px, 60px) scale(0.95);
            }
            75% {
                transform: translate(40px, 180px) scale(1.05);
            }
        }

        .login-card {
            position: relative;
            z-index: 1;
            background: rgba(12, 17, 29, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 100px rgba(99, 102, 241, 0.1);
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card h3 {
            color: #fff;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            letter-spacing: 0.5px;
        }

        .login-card .form-label {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .login-card .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 14px 16px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .login-card .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #6366f1;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.25);
            color: #fff;
            outline: none;
        }

        .login-card .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .login-card .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            margin-top: 10px;
            justify-content: center;
        }

        .login-card .btn-primary:hover {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }

        .login-card .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }

        .decoration {
            position: absolute;
            width: 60px;
            height: 60px;
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 50%;
            top: -30px;
            right: -30px;
        }

        .decoration-2 {
            position: absolute;
            width: 40px;
            height: 40px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 50%;
            bottom: -20px;
            left: -20px;
        }

        .brand-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
        }

        .icon-decoration {
            margin-bottom: 15px;
        }

        .icon-decoration svg {
            width: 50px;
            height: 50px;
            fill: #6366f1;
            animation: pulse 3s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.8;
            }
            50% {
                transform: scale(1.05);
                opacity: 1;
            }
        }

        .brand-container h3 {
            margin-bottom: 0;
        }

        @media (max-width: 480px) {
            .login-card {
                margin: 20px;
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="animation-container">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>

    <div class="login-card">
        <div class="decoration"></div>
        <div class="decoration-2"></div>
        
        <div class="brand-container">
            <div class="icon-decoration">
                <?php if (!empty($global_logo)): ?>
                    <img src="uploads/<?= htmlspecialchars($global_logo) ?>" alt="Logo" style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px; filter: brightness(1.1);">
                <?php else: ?>
                    <img src="assets/images/logo.png" alt="Logo" style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px; filter: brightness(1.1);">
                <?php endif; ?>
            </div>
            <h3><?= htmlspecialchars($global_company_name) ?></h3>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 ">LOGIN</button>
        </form>
    </div>
</body>
</html>