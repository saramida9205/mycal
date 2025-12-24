
document.addEventListener('DOMContentLoaded', function () {
    const eventModal = document.getElementById('eventModal');
    const modalTitle = document.getElementById('modalTitle');
    const closeModal = document.querySelector('.close-button');
    const eventForm = document.getElementById('eventForm');
    const eventIdInput = document.getElementById('eventId');
    const deleteButton = document.getElementById('deleteButton');
    const eventCategoryInput = document.getElementById('eventCategory');
    const ddayListEl = document.getElementById('ddayList');
    const exportIcsButton = document.getElementById('exportIcsButton');
    const backupDbButton = document.getElementById('backupDbButton');
    const googleLoginButton = document.getElementById('googleLoginButton');
    const googleSyncButton = document.getElementById('googleSyncButton');
    const clearEventsButton = document.getElementById('clearEventsButton');
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    const themeIcon = document.getElementById('theme-icon');
    const themeSwitch = document.querySelector('.theme-switch');
    const calendarEl = document.getElementById('calendar');
    const miniCalendarEl = document.getElementById('miniCalendar');
    const toastEl = document.getElementById('statusToast');
    const searchInput = document.getElementById('eventSearch');
    const recurrenceRuleInput = document.getElementById('recurrenceRule');
    const eventColorInput = document.getElementById('eventColor');
    const colorPalette = document.getElementById('colorPalette');

    // Theme Logic
    const currentTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
    if (themeIcon) themeIcon.textContent = currentTheme === 'dark' ? '☀️' : '🌙';

    if (themeSwitch) {
        themeSwitch.addEventListener('click', () => {
            const theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            if (themeIcon) themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
            if (calendar) calendar.render();
            if (miniCalendar) miniCalendar.render();

            // Re-init TinyMCE for theme
            tinymce.remove('#eventDescription');
            initTinyMCE(theme);
        });
    }

    function initTinyMCE(theme) {
        tinymce.init({
            selector: '#eventDescription',
            plugins: 'advlist autolink lists link image charmap print preview anchor',
            toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image',
            language: 'ko_KR',
            height: 250,
            skin: theme === 'dark' ? 'oxide-dark' : 'oxide',
            content_css: theme === 'dark' ? 'dark' : 'default',
            content_style: theme === 'dark' ? 'body { background-color: #3d3d3d; color: #f0f0f0; }' : ''
        });
    }

    initTinyMCE(currentTheme);

    // Mobile Menu Logic
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== menuToggle) {
            sidebar.classList.remove('active');
        }
    });

    function updateDDayList() {
        const now = new Date();
        const nextWeek = new Date();
        nextWeek.setDate(now.getDate() + 7);

        const importantEvents = calendar.getEvents().filter(event => {
            const start = event.start;
            const category = (event.extendedProps && event.extendedProps.category) || 'general';
            return start >= now && start <= nextWeek && category === 'important';
        });

        importantEvents.sort((a, b) => a.start - b.start);

        if (importantEvents.length === 0) {
            ddayListEl.innerHTML = '<div class="empty-msg">예정된 중요 일정 없음</div>';
            return;
        }

        ddayListEl.innerHTML = importantEvents.map(event => {
            const diff = Math.ceil((event.start - now) / (1000 * 60 * 60 * 24));
            const dayLabel = diff === 0 ? 'D-Day' : `D-${diff}`;
            return `
                <div class="dday-item">
                    <span class="dday-title">${event.title}</span>
                    <span class="dday-tag">${dayLabel}</span>
                </div>
            `;
        }).join('');
    }

    const colors = ['#3498db', '#2ecc71', '#f1c40f', '#e74c3c', '#9b59b6', '#1abc9c', '#f39c12', '#d35400', '#2c3e50', '#7f8c8d'];

    colors.forEach(color => {
        const colorOption = document.createElement('div');
        colorOption.classList.add('color-option');
        colorOption.style.backgroundColor = color;
        colorOption.dataset.color = color;
        colorPalette.appendChild(colorOption);

        colorOption.addEventListener('click', function () {
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

    function showToast(message) {
        toastEl.textContent = message;
        toastEl.classList.add('show');
        setTimeout(() => toastEl.classList.remove('show'), 3000);
    }

    // Mini Calendar Initialization
    const miniCalendar = new FullCalendar.Calendar(miniCalendarEl, {
        initialView: 'dayGridMonth',
        locale: 'ko',
        headerToolbar: {
            left: 'prev,next',
            center: 'title',
            right: ''
        },
        height: 'auto',
        selectable: true,
        dateClick: function (info) {
            calendar.gotoDate(info.date);
        }
    });
    miniCalendar.render();

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: window.innerWidth <= 768 ? 'listWeek' : 'dayGridMonth',
        locale: 'ko',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth,listWeek'
        },
        height: '100%',
        windowResize: function (view) {
            if (window.innerWidth <= 768) {
                calendar.changeView('listWeek');
            } else {
                calendar.changeView('dayGridMonth');
            }
        },
        editable: true,
        events: '/api/events.php',
        eventSources: [
            {
                url: '/api/holidays.php',
                editable: false,
                display: 'background'
            }
        ],
        eventDataTransform: function (eventData) {
            // Apply category color if not custom or default blue
            const categoryColors = {
                'work': '#2c3e50',
                'personal': '#2ecc71',
                'important': '#e74c3c',
                'general': '#3498db'
            };
            if (!eventData.color || eventData.color === '#3498db') {
                eventData.color = categoryColors[eventData.category] || '#3498db';
            }
            eventData.originalTitle = eventData.title;
            return eventData;
        },
        datesSet: function (info) {
            if (miniCalendar) {
                miniCalendar.gotoDate(info.view.currentStart);
            }
        },
        eventsSet: function () {
            updateDDayList();
        },
        eventContent: function (arg) {
            if (arg.event.extendedProps.completed) {
                arg.el.classList.add('completed');
            }
        },

        dateClick: function (info) {
            eventForm.reset();
            eventIdInput.value = '';
            modalTitle.textContent = '새 일정 추가';
            deleteButton.style.display = 'none';

            document.getElementById('eventStart').value = info.dateStr + 'T10:00';
            document.getElementById('eventEnd').value = info.dateStr + 'T11:00';
            eventCategoryInput.value = 'general';
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

        eventClick: function (info) {
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
            eventCategoryInput.value = info.event.extendedProps.category || 'general';
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

        eventDrop: function (info) {
            if (info.event.extendedProps.recurrence_rule) {
                alert('반복 일정은 날짜를 직접 변경할 수 없습니다. 일정을 클릭하여 수정해주세요.');
                info.revert();
                return;
            }
            updateEvent(info.event, info);
        },

        eventResize: function (info) {
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
            category: (event.extendedProps && event.extendedProps.category) || 'general',
            recurrence_rule: (event.extendedProps && event.extendedProps.recurrence_rule) || null,
            description: (event.extendedProps && event.extendedProps.description) || null,
            completed: (event.extendedProps && event.extendedProps.completed) || false
        };

        showToast('일정을 업데이트 중입니다...');

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
                    showToast('일정 업데이트에 실패했습니다.');
                    if (infoForRevert) infoForRevert.revert();
                } else {
                    showToast('일정이 저장되었습니다.');
                    updateDDayList();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('오류가 발생했습니다.');
                if (infoForRevert) infoForRevert.revert();
            });
    }

    closeModal.onclick = function () {
        eventModal.style.display = 'none';
    }

    window.onclick = function (event) {
        if (event.target == eventModal) {
            eventModal.style.display = 'none';
        }
    }

    eventForm.addEventListener('submit', function (e) {
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
        formData.append('category', eventCategoryInput.value);
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

    deleteButton.addEventListener('click', function () {
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

    // ICS 파일 가져오기 관련 로직
    const importIcsButton = document.getElementById('importIcsButton');
    const icsFileInput = document.getElementById('icsFileInput');

    if (importIcsButton && icsFileInput) {
        importIcsButton.addEventListener('click', function () {
            icsFileInput.click();
        });

        icsFileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const formData = new FormData();
                formData.append('ics_file', this.files[0]);

                if (!confirm('ICS 파일을 가져와 일정에 추가하시겠습니까?')) {
                    this.value = '';
                    return;
                }

                importIcsButton.disabled = true;
                importIcsButton.textContent = '가져오는 중...';

                fetch('/api/import_ics.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.count + '개의 일정이 성공적으로 추가되었습니다.');
                            calendar.refetchEvents();
                        } else {
                            alert('가져오기 실패: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('가져오기 중 오류가 발생했습니다.');
                    })
                    .finally(() => {
                        importIcsButton.disabled = false;
                        importIcsButton.textContent = 'ICS 파일 가져오기';
                        this.value = '';
                    });
            }
        });
    }

    // ICS 내보내기 로직
    if (backupDbButton) {
        backupDbButton.addEventListener('click', function () {
            window.location.href = '/api/backup_db.php';
        });
    }

    if (exportIcsButton) {
        exportIcsButton.addEventListener('click', function () {
            window.location.href = '/api/export_ics.php';
        });
    }

    if (clearEventsButton) {
        clearEventsButton.addEventListener('click', function () {
            if (confirm('모든 일정을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
                fetch('/api/clear_events.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            calendar.refetchEvents();
                        } else {
                            alert('오류: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    }

    // Search Logic
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();

            calendar.getEvents().forEach(event => {
                const title = event.title.toLowerCase();
                const description = (event.extendedProps.description || '').toLowerCase();

                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    event.setProp('display', 'auto');
                } else {
                    event.setProp('display', 'none');
                }
            });
        });
    }
});
