<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth.php';

// 로그인 체크
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $userId = $_SESSION['user_id'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = '모든 필드를 입력해주세요.';
    } elseif ($new_password !== $confirm_password) {
        $error = '새 비밀번호가 일치하지 않습니다.';
    } else {
        try {
            // 현재 비밀번호 확인
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($current_password, $user['password'])) {
                // 새 비밀번호 업데이트
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashed_password, $userId]);

                $success = '비밀번호가 성공적으로 변경되었습니다.';
            } else {
                $error = '현재 비밀번호가 일치하지 않습니다.';
            }
        } catch (PDOException $e) {
            $error = '시스템 오류가 발생했습니다: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>비밀번호 변경 - My일정관리</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-hover: #2980b9;
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            --text-color: #2c3e50;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
        }

        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: var(--bg-gradient);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: var(--text-color);
        }

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        h1 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 2rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-size: 0.9rem;
            font-weight: 500;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        input[type="password"]:focus {
            border-color: var(--primary-color);
            background-color: #fff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        button {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2);
            margin-bottom: 10px;
        }

        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.3);
        }

        .back-button {
            background-color: #95a5a6;
        }

        .back-button:hover {
            background-color: #7f8c8d;
        }

        .message {
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .error-message {
            color: var(--error-color);
            background-color: #fadbd8;
            border-left: 4px solid var(--error-color);
        }

        .success-message {
            color: #155724;
            background-color: #d4edda;
            border-left: 4px solid var(--success-color);
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>비밀번호 변경</h1>

        <?php if ($error): ?>
            <div class="message error-message">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success-message">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="current_password">현재 비밀번호</label>
                <input type="password" id="current_password" name="current_password" required autofocus>
            </div>
            <div class="form-group">
                <label for="new_password">새 비밀번호</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">새 비밀번호 확인</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit">변경하기</button>
            <button type="button" class="back-button" onclick="location.href='index.php'">돌아가기</button>
        </form>
    </div>
</body>

</html>