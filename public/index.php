<?php
require_once __DIR__ . '/../src/auth.php';
check_auth();
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My일정관리</title>
    <!-- FullCalendar Core CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href='css/style.css' rel='stylesheet' />
    <!-- TinyMCE -->
    <!--
    <script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    -->
    <script src="https://cdn.tiny.cloud/1/71xslsw0ngu40psplssdk0i9ksw27wn52iv7dk6bxi4yi0tb/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
</head>

<body>
    <button class="menu-toggle" id="menuToggle">☰</button>

    <div class="app-container">
        <!-- 사이드바 -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>My일정관리</h2>
                <div class="theme-switch" title="테마 변경">
                    <span id="theme-icon">🌙</span>
                </div>
            </div>

            <!-- 검색 섹션 -->
            <div class="search-section">
                <input type="text" id="eventSearch" placeholder="일정 검색...">
            </div>

            <!-- 미니 캘린더 -->
            <div id="miniCalendar"></div>

            <!-- D-Day 섹션 -->
            <div class="dday-section">
                <h3>다가오는 중요 일정</h3>
                <div id="ddayList">
                    <div class="empty-msg">예정된 일정이 없습니다.</div>
                </div>
            </div>

            <div class="sidebar-footer">
                <button id="googleLoginButton" class="action-button full-width" style="background-color: #4285F4;">구글 캘린더 연동</button>
                <button id="googleSyncButton" class="action-button full-width secondary">구글 일정 동기화</button>
                <button id="backupDbButton" class="action-button full-width secondary">DB 백업</button>
                <button id="exportIcsButton" class="action-button full-width secondary">ICS 내보내기</button>
                <button id="importIcsButton" class="action-button full-width">ICS 파일 가져오기</button>
                <button id="clearEventsButton" class="action-button full-width danger">일정 초기화</button>
                <a href="/logout.php" class="action-button full-width outline">로그아웃</a>
                <input type="file" id="icsFileInput" style="display: none;" accept=".ics">
            </div>
        </aside>

        <!-- 메인 콘텐츠 영역 -->
        <main class="main-content">
            <header class="main-header">
                <div id="statusToast" class="toast">상태 메시지</div>
            </header>

            <div id="calendar"></div>
        </main>
    </div>

    <!-- 일정 추가/수정 모달 -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 id="modalTitle">새 일정 추가</h2>
            <div class="form-group checkbox-group top-checkbox">
                <input type="checkbox" id="eventCompleted" name="completed">
                <label for="eventCompleted" class="checkbox-label">일정 완료</label>
            </div>
            <form id="eventForm">
                <input type="hidden" id="eventId" name="id">

                <div class="form-group">
                    <label for="eventTitle">제목:</label>
                    <input type="text" id="eventTitle" name="title" required>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-small">
                        <label for="eventStart">시작일:</label>
                        <input type="datetime-local" id="eventStart" name="start" required>
                    </div>

                    <div class="form-group form-group-small">
                        <label for="eventEnd">종료일:</label>
                        <input type="datetime-local" id="eventEnd" name="end" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-small">
                        <label for="eventCategory">카테고리:</label>
                        <select id="eventCategory" name="category">
                            <option value="general">일반</option>
                            <option value="work">업무</option>
                            <option value="personal">개인</option>
                            <option value="important">중요</option>
                        </select>
                    </div>
                    <div class="form-group form-group-small">
                        <label for="recurrenceRule">반복:</label>
                        <select id="recurrenceRule" name="recurrence_rule">
                            <option value="">안 함</option>
                            <option value="daily">매일</option>
                            <option value="weekly">매주</option>
                            <option value="monthly">매월</option>
                            <option value="yearly">매년</option>
                        </select>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="allDay" name="allDay">
                        <label for="allDay" class="checkbox-label">하루 종일</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>색상:</label>
                    <div id="colorPalette"></div>
                    <input type="hidden" id="eventColor" name="color">
                </div>

                <div class="form-group">
                    <label for="eventDescription">메모:</label>
                    <textarea id="eventDescription" name="description"></textarea>
                </div>

                <div class="form-group">
                    <label for="eventAttachments">파일 첨부:</label>
                    <input type="file" id="eventAttachments" name="attachments[]" multiple>
                    <div id="attachmentsList"></div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="saveButton" class="action-button">저장</button>
                    <button type="button" id="deleteButton" class="action-button danger">삭제</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FullCalendar Core JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <!-- FullCalendar 한국어 언어팩 -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/ko.js'></script>
    <!-- Your application script -->
    <script src="js/script.js"></script>

</body>

</html>