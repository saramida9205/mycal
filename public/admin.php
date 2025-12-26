<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 체크
if (!isset($_SESSION['authenticated']) || $_SESSION['username'] !== 'admin') {
    echo "<script>alert('관리자만 접근할 수 있습니다.'); location.href='index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 페이지 - My일정관리</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --bg-color: #f0f2f5;
        }

        body {
            font-family: 'Noto Sans KR', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        h1 {
            margin: 0;
            font-size: 1.5rem;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-back {
            background-color: #95a5a6;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .action-group {
            display: flex;
            gap: 5px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            width: 300px;
            border-radius: 8px;
        }

        .modal input {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>🔒 관리자 대시보드</h1>
            <button class="btn btn-back" onclick="location.href='index.php'">메인으로 돌아가기</button>
        </header>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>사용자명</th>
                    <th>가입일</th>
                    <th>등록 일정 수</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <!-- Data will be loaded here -->
            </tbody>
        </table>
    </div>

    <!-- Password Change Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <h3>비밀번호 변경</h3>
            <p>사용자: <span id="modalUsername"></span></p>
            <input type="password" id="newPassword" placeholder="새 비밀번호">
            <input type="hidden" id="modalUserId">
            <div style="text-align: right; margin-top: 10px;">
                <button class="btn btn-primary" onclick="submitPasswordChange()">변경</button>
                <button class="btn" onclick="closeModal()">취소</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadUsers);

        function loadUsers() {
            const formData = new FormData();
            formData.append('action', 'list_users');

            fetch('/api/admin_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderTable(data.users);
                    } else {
                        alert('데이터 로드 실패: ' + data.message);
                    }
                })
                .catch(err => console.error(err));
        }

        function renderTable(users) {
            const tbody = document.getElementById('userTableBody');
            tbody.innerHTML = '';

            users.forEach(user => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.username} ${user.username === 'admin' ? '<span style="color:red;font-size:0.8em">(관리자)</span>' : ''}</td>
                    <td>${user.created_at}</td>
                    <td>${user.event_count}</td>
                    <td>
                        <div class="action-group">
                            <button class="btn btn-primary" style="font-size:0.8em" onclick="openPasswordModal(${user.id}, '${user.username}')">비번변경</button>
                            <button class="btn btn-danger" style="font-size:0.8em; background-color:#e67e22" onclick="clearEvents(${user.id})">일정초기화</button>
                            ${user.username !== 'admin' ? `<button class="btn btn-danger" style="font-size:0.8em" onclick="deleteUser(${user.id})">삭제</button>` : ''}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Functions for actions
        function openPasswordModal(id, username) {
            document.getElementById('modalUserId').value = id;
            document.getElementById('modalUsername').textContent = username;
            document.getElementById('newPassword').value = '';
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }

        function submitPasswordChange() {
            const id = document.getElementById('modalUserId').value;
            const pw = document.getElementById('newPassword').value;
            if (!pw) return alert('비밀번호를 입력하세요');

            callApi('change_password', {
                user_id: id,
                new_password: pw
            });
            closeModal();
        }

        function clearEvents(id) {
            if (confirm('해당 사용자의 모든 일정을 삭제하시겠습니까? (복구 불가)')) {
                callApi('clear_events', {
                    user_id: id
                });
            }
        }

        function deleteUser(id) {
            if (confirm('정말로 이 사용자를 삭제하시겠습니까?\n모든 데이터가 영구 삭제됩니다.')) {
                callApi('delete_user', {
                    user_id: id
                });
            }
        }

        function callApi(action, data) {
            const formData = new FormData();
            formData.append('action', action);
            for (let key in data) {
                formData.append(key, data[key]);
            }

            fetch('/api/admin_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(res => {
                    alert(res.message);
                    if (res.success) loadUsers();
                })
                .catch(err => console.error(err));
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target == document.getElementById('passwordModal')) {
                closeModal();
            }
        }
    </script>
</body>

</html>