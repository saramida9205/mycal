<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../config/database.php';
check_auth();

$userId = $_SESSION['user_id'] ?? 1;
$stmt = $pdo->prepare("SELECT 1 FROM user_tokens WHERE user_id = ?");
$stmt->execute([$userId]);
$isGoogleConnected = (bool)$stmt->fetch();
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
                <div>
                    <h2>My일정관리</h2>
                    <?php if (isset($_SESSION['username'])): ?>
                        <div style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 4px;">
                            👋 안녕하세요, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>님
                        </div>
                    <?php endif; ?>
                </div>
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
                <?php if ($isGoogleConnected): ?>
                    <button id="googleLoginButton" class="action-button full-width success">구글 캘린더 연동완료</button>
                <?php else: ?>
                    <button id="googleLoginButton" class="action-button full-width" style="background-color: #4285F4;">구글 캘린더 연동</button>
                <?php endif; ?>
                <button id="googleSyncButton" class="action-button full-width secondary">구글 일정 동기화</button>
                <button id="backupDbButton" class="action-button full-width secondary">DB 백업</button>
                <button id="removeDuplicatesButton" class="action-button full-width secondary">중복 일정 정리</button>
                <button id="exportIcsButton" class="action-button full-width secondary">ICS 내보내기</button>
                <button id="importIcsButton" class="action-button full-width">ICS 파일 가져오기</button>
                <button id="clearEventsButton" class="action-button full-width danger">일정 초기화</button>
                <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
                    <a href="/admin.php" class="action-button full-width danger" style="background-color: #2c3e50;">관리자 페이지</a>
                <?php endif; ?>
                <a href="/change_password.php" class="action-button full-width outline">비밀번호 변경</a>
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
                            <option value="custom">직접 설정...</option>
                        </select>
                    </div>
                    <!-- Advanced Recurrence Options -->
                    <div id="customRecurrenceOptions" class="advanced-options">
                        <div class="form-row" style="margin-bottom: 10px;">
                            <label style="margin-right: 10px;">상세설정:</label>
                            <select id="advRecureType" style="flex: 1;">
                                <option value="monthly_first_day">매월 1일 (초)</option>
                                <option value="monthly_last_day">매월 말일</option>
                                <option value="monthly_nth_weekday">매월 N번째 요일</option>
                            </select>
                        </div>

                        <div id="advRecurNthOptions" style="display:none; margin-bottom: 10px;">
                            <select id="advRecurNth" style="width: 45%;">
                                <option value="1">첫째</option>
                                <option value="2">둘째</option>
                                <option value="3">셋째</option>
                                <option value="4">넷째</option>
                                <option value="5">마지막</option>
                            </select>
                            <select id="advRecurDay" style="width: 45%;">
                                <option value="Mon">월요일</option>
                                <option value="Tue">화요일</option>
                                <option value="Wed">수요일</option>
                                <option value="Thu">목요일</option>
                                <option value="Fri">금요일</option>
                                <option value="Sat">토요일</option>
                                <option value="Sun">일요일</option>
                            </select>
                        </div>

                        <div class="form-row" style="align-items: center;">
                            <label style="margin-right: 10px;">공휴일인 경우:</label>
                            <select id="advRecurHoliday" style="flex: 1;">
                                <option value="">그대로 진행</option>
                                <option value="BEFORE">전일(평일)로 이동</option>
                                <option value="AFTER">익일(평일)로 이동</option>
                            </select>
                        </div>
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
                    <button type="button" id="closeModalButton" class="action-button secondary">닫기</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 캘린더 선택 모달 -->
    <div id="calendarSelectionModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeCalendarModal">&times;</span>
            <h2>동기화할 구글 캘린더 선택</h2>
            <div id="calendarListContainer" style="margin: 20px 0; max-height: 300px; overflow-y: auto;">
                <p>불러오는 중...</p>
            </div>
            <div class="form-actions">
                <button id="confirmSyncButton" class="action-button">동기화 시작</button>
            </div>
        </div>
    </div>

    <!-- FullCalendar Core JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <!-- FullCalendar 한국어 언어팩 -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/ko.js'></script>
    <!-- Lunar Calendar Logic -->
    <script src="js/lunar_cal.js"></script>
    <!-- Background Particles -->
    <script src="js/particles.js"></script>
    <!-- Your application script -->
    <script src="js/script.js"></script>

</body>

</html>