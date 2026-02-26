<?php
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    $role = strtolower(trim($_SESSION['role'] ?? ''));
    if ($role === 'admin') {
        session_write_close();
        header("Location: admin/index.php");
        exit();
    } else if ($role === 'driver') {
        session_write_close();
        header("Location: driver/index.php");
        exit();
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config/database.php';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // update status to online
            $upd = $db->prepare("UPDATE users SET status='online' WHERE id=?");
            $upd->execute([$user['id']]);

            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: driver/index.php");
            }
            session_write_close();
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – DeliTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <style>
        :root {
            --primary: #6366f1;
            --primary-d: #4f46e5;
            --accent: #22d3ee;
            --bg: #0f0f1a;
            --surface: #1a1a2e;
            --border: rgba(255, 255, 255, .08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* animated background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 800px 600px at 20% 20%, rgba(99, 102, 241, .15) 0%, transparent 70%),
                radial-gradient(ellipse 600px 800px at 80% 80%, rgba(34, 211, 238, .1) 0%, transparent 70%);
            animation: bgPulse 8s ease-in-out infinite alternate;
        }

        @keyframes bgPulse {
            from {
                opacity: .6;
            }

            to {
                opacity: 1;
            }
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 24px;
        }

        .brand {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 16px;
            box-shadow: 0 0 40px rgba(99, 102, 241, .4);
        }

        .brand h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .brand p {
            color: #94a3b8;
            font-size: .95rem;
            margin-top: 4px;
        }

        .card {
            background: rgba(26, 26, 46, .8);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 36px 32px;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, .5);
        }

        .form-label {
            color: #cbd5e1;
            font-size: .85rem;
            font-weight: 600;
            margin-bottom: 6px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .form-control {
            background: rgba(255, 255, 255, .05);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: #fff;
            padding: 12px 16px;
            font-size: .95rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, .07);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .2);
            color: #fff;
        }

        .form-control::placeholder {
            color: #475569;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            border-radius: 10px;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            padding: 13px;
            margin-top: 8px;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
            letter-spacing: .3px;
        }

        .btn-login:hover {
            opacity: .9;
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert-error {
            background: rgba(239, 68, 68, .12);
            border: 1px solid rgba(239, 68, 68, .3);
            border-radius: 10px;
            color: #fca5a5;
            padding: 12px 16px;
            font-size: .9rem;
            margin-bottom: 20px;
        }

        .divider {
            border-color: var(--border);
            margin: 24px 0;
        }

        .demo-creds {
            background: rgba(34, 211, 238, .06);
            border: 1px solid rgba(34, 211, 238, .15);
            border-radius: 10px;
            padding: 14px 16px;
            font-size: .82rem;
            color: #94a3b8;
        }

        .demo-creds strong {
            color: var(--accent);
        }

        /* floating particles */
        .particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--primary);
            border-radius: 50%;
            animation: float linear infinite;
            opacity: 0;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }

            10% {
                opacity: .6;
            }

            90% {
                opacity: .6;
            }

            100% {
                transform: translateY(-10vh) translateX(60px);
                opacity: 0;
            }
        }
    </style>
</head>

<body>

    <div class="particles" id="particles"></div>

    <div class="login-wrapper">
        <div class="brand">
            <div class="brand-icon">🚚</div>
            <h1>DeliTrack</h1>
            <p>Real-time Delivery Tracking System</p>
        </div>

        <div class="card">
            <?php if ($error): ?>
                <div class="alert-error">⚠️
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com"
                        required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                        required>
                </div>
                <button type="submit" class="btn-login">Sign In →</button>
            </form>

            <hr class="divider">

            <div class="demo-creds">
                <div class="mb-2">🔑 <strong>Admin:</strong> admin@delivery.com / admin123</div>
                <div>🚗 <strong>Driver:</strong> john@delivery.com / driver123</div>
            </div>
        </div>
    </div>

    <script>
        // Generate floating particles
        const container = document.getElementById('particles');
        for (let i = 0; i < 18; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            p.style.left = Math.random() * 100 + '%';
            p.style.animationDuration = (8 + Math.random() * 12) + 's';
            p.style.animationDelay = (Math.random() * 10) + 's';
            p.style.width = (2 + Math.random() * 4) + 'px';
            p.style.height = p.style.width;
            p.style.background = Math.random() > .5 ? '#6366f1' : '#22d3ee';
            container.appendChild(p);
        }
    </script>
</body>

</html>