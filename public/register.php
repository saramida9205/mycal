<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '아이디와 비밀번호를 입력해주세요.';
    } elseif ($password !== $password_confirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        // 중복 아이디 확인
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = '이미 존재하는 아이디입니다.';
        } else {
            // 사용자 생성
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $hashed_password]);
                $success = '회원가입이 완료되었습니다. <a href="login.php">로그인하기</a>';
            } catch (PDOException $e) {
                $error = '시스템 오류: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입 - My일정관리</title>
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .register-container {
            background-color: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            text-align: center;
            color: #1a1a1a;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4b5563;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #34A853;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #2d9249;
        }

        .error-message {
            color: #dc2626;
            background-color: #fee2e2;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            text-align: center;
        }

        .success-message {
            color: #059669;
            background-color: #d1fae5;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .login-link a {
            color: #2563eb;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <h1>회원가입</h1>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">아이디</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="password_confirm">비밀번호 확인</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
                <button type="submit">가입하기</button>
            </form>
        <?php endif; ?>
        <div class="login-link">
            이미 계정이 있으신가요? <a href="login.php">로그인</a>
        </div>
    </div>
</body>

</html>