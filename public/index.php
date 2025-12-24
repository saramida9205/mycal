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

    <h1>My일정관리</h1>

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
                    <button type="submit" id="saveButton">저장</button>
                    <button type="button" id="deleteButton">삭제</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 캘린더가 렌더링될 DOM 요소 -->
    <div id="calendar"></div>

    <!-- FullCalendar Core JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <!-- FullCalendar 한국어 언어팩 -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/ko.js'></script>
    <!-- Your application script -->
    <script src="js/script.js"></script>

</body>
</html>