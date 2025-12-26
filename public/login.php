<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $input_password = $_POST['password'] ?? '';

    if (empty($username) || empty($input_password)) {
        $error = '아이디와 비밀번호를 입력해주세요.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($input_password, $user['password'])) {
                $_SESSION['authenticated'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                header('Location: index.php');
                exit;
            } else {
                $error = '아이디 또는 비밀번호가 일치하지 않습니다.';
            }
        } catch (PDOException $e) {
            $error = '시스템 오류 발생';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - My일정관리</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --text-color: #f8fafc;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --input-bg: rgba(255, 255, 255, 0.15);
            --error-bg: rgba(239, 68, 68, 0.2);
            --error-text: #fca5a5;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            /* Slate 900 */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: var(--text-color);
            overflow: hidden;
            position: relative;
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 10;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--glass-border);
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: white;
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .subtitle {
            color: #94a3b8;
            margin-bottom: 2.5rem;
            font-size: 0.95rem;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #cbd5e1;
            font-size: 0.85rem;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            box-sizing: border-box;
            font-size: 1rem;
            background-color: var(--input-bg);
            color: white;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input::placeholder {
            color: #64748b;
        }

        input:focus {
            border-color: var(--primary-color);
            background-color: rgba(255, 255, 255, 0.2);
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }

        button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-top: 1rem;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .error-message {
            color: var(--error-text);
            background-color: var(--error-bg);
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .login-link {
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
            transition: color 0.2s;
        }

        .login-link a:hover {
            color: #818cf8;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <canvas id="particleCanvas"></canvas>
    <div class="login-container">
        <h1>My일정관리</h1>
        <p class="subtitle">Future-ready Productivity.</p>

        <?php if ($error): ?>
            <div class="error-message">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">아이디</label>
                <input type="text" id="username" name="username" placeholder="user_id" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <button type="submit">로그인</button>
            <div class="login-link">
                계정이 없으신가요? <a href="register.php">회원가입</a>
            </div>
        </form>
    </div>

    <script>
        const canvas = document.getElementById('particleCanvas');
        const ctx = canvas.getContext('2d');

        let width, height;
        let particles = [];

        // Configuration
        const particleCount = 100;
        const connectionDistance = 150;
        const mouseDistance = 200;

        let mouse = {
            x: null,
            y: null
        };

        window.addEventListener('mousemove', (e) => {
            mouse.x = e.x;
            mouse.y = e.y;
        });

        window.addEventListener('resize', init);

        function init() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
            particles = [];

            for (let i = 0; i < particleCount; i++) {
                particles.push(new Particle());
            }
        }

        class Particle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.vx = (Math.random() - 0.5) * 1.5;
                this.vy = (Math.random() - 0.5) * 1.5;
                this.size = Math.random() * 2 + 1;
                this.color = `rgba(99, 102, 241, ${Math.random() * 0.5 + 0.2})`; // Indigo color
            }

            update() {
                this.x += this.vx;
                this.y += this.vy;

                // Bounce off edges
                if (this.x < 0 || this.x > width) this.vx *= -1;
                if (this.y < 0 || this.y > height) this.vy *= -1;

                // Mouse interaction
                if (mouse.x != null) {
                    let dx = mouse.x - this.x;
                    let dy = mouse.y - this.y;
                    let distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < mouseDistance) {
                        const forceDirectionX = dx / distance;
                        const forceDirectionY = dy / distance;
                        const force = (mouseDistance - distance) / mouseDistance;
                        const directionX = forceDirectionX * force * this.size;
                        const directionY = forceDirectionY * force * this.size;

                        this.x -= directionX;
                        this.y -= directionY;
                    }
                }
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();
            }
        }

        function animate() {
            requestAnimationFrame(animate);
            ctx.clearRect(0, 0, width, height);

            // Draw connections first
            for (let a = 0; a < particles.length; a++) {
                for (let b = a; b < particles.length; b++) {
                    let dx = particles[a].x - particles[b].x;
                    let dy = particles[a].y - particles[b].y;
                    let distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < connectionDistance) {
                        let opacity = 1 - (distance / connectionDistance);
                        ctx.strokeStyle = `rgba(99, 102, 241, ${opacity * 0.2})`;
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(particles[a].x, particles[a].y);
                        ctx.lineTo(particles[b].x, particles[b].y);
                        ctx.stroke();
                    }
                }
            }

            // Draw particles
            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });
        }

        init();
        animate();
    </script>
</body>

</html>