
document.addEventListener('DOMContentLoaded', function() {
    const eventModal = document.getElementById('eventModal');
    const modalTitle = document.getElementById('modalTitle');
    const closeModal = document.querySelector('.close-button');
    const eventForm = document.getElementById('eventForm');
    const eventIdInput = document.getElementById('eventId');
    const deleteButton = document.getElementById('deleteButton');
    const recurrenceRuleInput = document.getElementById('recurrenceRule');
    const eventColorInput = document.getElementById('eventColor');
    const calendarEl = document.getElementById('calendar');
    const colorPalette = document.getElementById('colorPalette');

    tinymce.init({
        selector: '#eventDescription',
        plugins: 'advlist autolink lists link image charmap print preview anchor',
        toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image',
        language: 'ko_KR'
    });

    const colors = ['#3498db', '#2ecc71', '#f1c40f', '#e74c3c', '#9b59b6', '#1abc9c', '#f39c12', '#d35400', '#2c3e50', '#7f8c8d'];

    colors.forEach(color => {
        const colorOption = document.createElement('div');
        colorOption.classList.add('color-option');
        colorOption.style.backgroundColor = color;
        colorOption.dataset.color = color;
        colorPalette.appendChild(colorOption);

        colorOption.addEventListener('click', function() {
            eventColorInput.value = this.dataset.color;
            document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    function toLocalISOString(date) {
        if (!date) return '';
        const d = new Date(date);
        const tzoffset = d.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(d - tzoffset)).toISOString().slice(0, 16);
        return localISOTime;
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'ko',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: true,
        events: '/api/events.php',
        eventContent: function(arg) {
            if (arg.event.extendedProps.completed) {
                arg.el.classList.add('completed');
            }
        },

        dateClick: function(info) {
            eventForm.reset();
            eventIdInput.value = '';
            modalTitle.textContent = '새 일정 추가';
            deleteButton.style.display = 'none';

            document.getElementById('eventStart').value = info.dateStr + 'T10:00';
            document.getElementById('eventEnd').value = info.dateStr + 'T11:00';
            eventColorInput.value = '#3498db';
            document.querySelectorAll('.color-option').forEach(opt => {
                opt.classList.remove('selected');
                if (opt.dataset.color === '#3498db') {
                    opt.classList.add('selected');
                }
            });
            tinymce.get('eventDescription').setContent('');
            document.getElementById('eventCompleted').checked = false;
            eventModal.style.display = 'block';
        },

        eventClick: function(info) {
            info.jsEvent.preventDefault();
            
            modalTitle.textContent = '일정 수정';
            deleteButton.style.display = 'inline-block';

            let endDateForModal = info.event.end;
            if (info.event.allDay && info.event.end) {
                let inclusiveEndDate = new Date(info.event.end);
                inclusiveEndDate.setDate(inclusiveEndDate.getDate() - 1);
                endDateForModal = inclusiveEndDate;
            }

            eventIdInput.value = info.event.groupId || info.event.id;
            document.getElementById('eventTitle').value = info.event.title;
            document.getElementById('allDay').checked = info.event.allDay;
            document.getElementById('eventStart').value = toLocalISOString(info.event.start);
            document.getElementById('eventEnd').value = toLocalISOString(endDateForModal || info.event.start);
            recurrenceRuleInput.value = info.event.extendedProps.recurrence_rule || '';
            tinymce.get('eventDescription').setContent(info.event.extendedProps.description || '');
            eventColorInput.value = info.event.backgroundColor || '#3498db';
            document.getElementById('eventCompleted').checked = info.event.extendedProps.completed || false;

            document.querySelectorAll('.color-option').forEach(opt => {
                opt.classList.remove('selected');
                if (opt.dataset.color === eventColorInput.value) {
                    opt.classList.add('selected');
                }
            });

            eventModal.style.display = 'block';
        },

        eventDrop: function(info) {
            if (info.event.extendedProps.recurrence_rule) {
                alert('반복 일정은 날짜를 직접 변경할 수 없습니다. 일정을 클릭하여 수정해주세요.');
                info.revert();
                return;
            }
            updateEvent(info.event, info);
        },

        eventResize: function(info) {
            if (info.event.extendedProps.recurrence_rule) {
                alert('반복 일정은 기간을 직접 변경할 수 없습니다. 일정을 클릭하여 수정해주세요.');
                info.revert();
                return;
            }
            updateEvent(info.event, info);
        }
    });

    calendar.render();

    function updateEvent(event, infoForRevert) {
        let endDate = event.end;
        if (event.allDay && event.end) {
            let inclusiveEndDate = new Date(event.end);
            inclusiveEndDate.setDate(inclusiveEndDate.getDate() - 1);
            endDate = inclusiveEndDate;
        }

        const eventData = {
            id: event.id,
            title: event.title,
            start: toLocalISOString(event.start),
            end: toLocalISOString(endDate || event.start),
            allDay: event.allDay,
            color: event.backgroundColor,
            recurrence_rule: (event.extendedProps && event.extendedProps.recurrence_rule) || null,
            description: (event.extendedProps && event.extendedProps.description) || null,
            completed: (event.extendedProps && event.extendedProps.completed) || false
        };

        fetch('/api/update_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(eventData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Server responded with an error.');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                alert('일정 업데이트에 실패했습니다.');
                infoForRevert.revert();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('일정 업데이트 중 오류가 발생했습니다.');
            infoForRevert.revert();
        });
    }

    closeModal.onclick = function() {
        eventModal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == eventModal) {
            eventModal.style.display = 'none';
        }
    }

    eventForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const eventId = eventIdInput.value;
        const url = eventId ? '/api/update_event.php' : '/api/create_event.php';

        const formData = new FormData();
        formData.append('id', eventIdInput.value);
        formData.append('title', document.getElementById('eventTitle').value);
        formData.append('start', document.getElementById('eventStart').value);
        formData.append('end', document.getElementById('eventEnd').value);
        formData.append('allDay', document.getElementById('allDay').checked);
        formData.append('completed', document.getElementById('eventCompleted').checked);
        formData.append('color', eventColorInput.value);
        formData.append('recurrence_rule', recurrenceRuleInput.value);
        formData.append('description', tinymce.get('eventDescription').getContent());

        const files = document.getElementById('eventAttachments').files;
        for (let i = 0; i < files.length; i++) {
            formData.append('attachments[]', files[i]);
        }

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                eventModal.style.display = 'none';
                calendar.refetchEvents();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    deleteButton.addEventListener('click', function() {
        const eventId = eventIdInput.value;
        if (!eventId) return;

        if (confirm('이 일정을 정말 삭제하시겠습니까?')) {
            fetch('/api/delete_event.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: eventId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    eventModal.style.display = 'none';
                    calendar.refetchEvents();
                } else {
                    alert('삭제 실패: ' + (data.message || '알 수 없는 오류'));
                }
            }).catch(error => console.error('Error:', error));
        }
    });
});
