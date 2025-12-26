
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
    const customRecurrenceOptions = document.getElementById('customRecurrenceOptions');
    const advRecureType = document.getElementById('advRecureType');
    const advRecurNthOptions = document.getElementById('advRecurNthOptions');
    const advRecurNth = document.getElementById('advRecurNth');
    const advRecurDay = document.getElementById('advRecurDay');
    const advRecurHoliday = document.getElementById('advRecurHoliday');
    const eventColorInput = document.getElementById('eventColor');
    const colorPalette = document.getElementById('colorPalette');

    // Advanced Recurrence UI Logic
    recurrenceRuleInput.addEventListener('change', function () {
        if (this.value === 'custom') {
            customRecurrenceOptions.style.display = 'block';
        } else {
            customRecurrenceOptions.style.display = 'none';
        }
    });

    advRecureType.addEventListener('change', function () {
        if (this.value === 'monthly_nth_weekday') {
            advRecurNthOptions.style.display = 'block';
        } else {
            advRecurNthOptions.style.display = 'none';
        }
    });

    // Auto Backup Trigger
    setTimeout(() => {
        fetch('/api/auto_backup.php')
            .then(res => res.json())
            .then(data => console.log('Auto Backup:', data.message))
            .catch(err => console.error('Auto Backup Error:', err));
    }, 2000); // 2초 후 실행 (페이지 로드 부하 분산)

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
            content_style: theme === 'dark'
                ? 'body { background-color: #1e293b; color: #f1f5f9; font-family: "Inter", sans-serif; }'
                : 'body { font-family: "Inter", sans-serif; }'
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

    // Modern Palette
    const colors = ['#ef4444', '#f97316', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#64748b'];

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
        },
        dayCellContent: function (e) {
            return e.dayNumberText.replace('일', '');
        }
    });
    miniCalendar.render();

    // --- Context Menu Logic ---
    const contextMenu = document.createElement('div');
    contextMenu.className = 'context-menu';
    contextMenu.innerHTML = `
        <div class="context-menu-item" id="ctxAdd"><span class="icon">➕</span> 일정 추가</div>
        <div class="context-divider"></div>
        <div class="context-menu-item" id="ctxEdit"><span class="icon">✏️</span> 수정</div>
        <div class="context-menu-item" id="ctxDeleteThis"><span class="icon">🗑️</span> 이번 일정만 삭제</div>
        <div class="context-menu-item danger" id="ctxDeleteAll"><span class="icon">⚠️</span> 전체 일정 삭제</div>
    `;
    document.body.appendChild(contextMenu);

    let ctxTargetEvent = null;
    let ctxTargetDate = null;

    // Menu Actions
    document.getElementById('ctxAdd').addEventListener('click', function () {
        if (ctxTargetDate) {
            openEventModal(null, ctxTargetDate);
            contextMenu.style.display = 'none';
        }
    });

    document.getElementById('ctxEdit').addEventListener('click', function () {
        if (ctxTargetEvent) {
            openEventModal(ctxTargetEvent);
            contextMenu.style.display = 'none';
        }
    });

    document.getElementById('ctxDeleteThis').addEventListener('click', function () {
        if (ctxTargetEvent) {
            // startStr might be ISO string
            let dateStr = ctxTargetEvent.startStr;
            if (dateStr.includes('T')) dateStr = dateStr.split('T')[0];
            deleteEventApi(ctxTargetEvent.id, 'this', dateStr);
            contextMenu.style.display = 'none';
        }
    });

    document.getElementById('ctxDeleteAll').addEventListener('click', function () {
        if (ctxTargetEvent) {
            deleteEventApi(ctxTargetEvent.id, 'all');
            contextMenu.style.display = 'none';
        }
    });

    function deleteEventApi(id, mode, date = null) {
        if (!confirm(mode === 'this' ? '이 일정만 삭제하시겠습니까?' : '반복되는 모든 일정이 삭제됩니다.\n계속하시겠습니까?')) return;

        fetch('/api/delete_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, mode: mode, date: date })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || '삭제되었습니다.');
                    calendar.refetchEvents();
                    updateDDayList();
                } else {
                    alert(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('삭제 중 오류가 발생했습니다.');
            });
    }

    // Global Right Click Handler
    document.addEventListener('contextmenu', function (e) {
        // Prevent default browser menu if inside calendar
        if (calendarEl.contains(e.target) || (miniCalendarEl && miniCalendarEl.contains(e.target))) {
            // Let specific listeners handle it, but block default here just in case
            // However, blocking here prevents 'eventDidMount' listener from working?
            // No, event bubbling goes up. We should preventDefault() in the specific handler if handled.
            // But for empty cells, we handle here.
        }

        const dayEl = e.target.closest('.fc-daygrid-day') || e.target.closest('.fc-timegrid-slot-lane');

        // If clicking on empty space in calendar (not event)
        if (dayEl && calendarEl.contains(dayEl) && !e.target.closest('.fc-event')) {
            e.preventDefault();
            const dateStr = dayEl.getAttribute('data-date');
            if (dateStr) {
                ctxTargetDate = dateStr;
                // Show Add Only
                document.getElementById('ctxAdd').style.display = 'flex';
                document.getElementById('ctxEdit').style.display = 'none';
                document.getElementById('ctxDeleteThis').style.display = 'none';
                document.getElementById('ctxDeleteAll').style.display = 'none';

                showMenu(e.pageX, e.pageY);
            }
        }
    });

    document.addEventListener('click', () => {
        contextMenu.style.display = 'none';
    });

    function showMenu(x, y) {
        // Boundary check
        const menuWidth = 150;
        const menuHeight = 150;
        if (x + menuWidth > window.innerWidth) x -= menuWidth;
        if (y + menuHeight > window.innerHeight) y -= menuHeight;

        contextMenu.style.left = x + 'px';
        contextMenu.style.top = y + 'px';
        contextMenu.style.display = 'block';
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: window.innerWidth <= 768 ? 'listWeek' : 'dayGridMonth',
        locale: 'ko',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth,listWeek'
        },
        height: '100%',
        dayCellContent: function (e) {
            // Render basic day number
            let html = `<div class="fc-daygrid-day-number">${e.dayNumberText.replace('일', '')}</div>`;

            // Calculate Lunar Date (Only on Saturdays)
            const date = new Date(e.date);
            if (date.getDay() === 6) { // 6 = Saturday
                const y = date.getFullYear();
                const m = date.getMonth() + 1;
                const d = date.getDate();

                if (typeof LunarCal !== 'undefined') {
                    const lunar = LunarCal.solarToLunar(y, m, d);
                    if (lunar) {
                        const lunarStr = `${lunar.month}.${lunar.day}`;
                        html += `<div class="lunar-date">${lunarStr}</div>`;
                    }
                }
            }

            return { html: html };
        },
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
            // Apply category color if not custom or default blue
            const categoryColors = {
                'work': '#3b82f6', // blue-500
                'personal': '#10b981', // emerald-500
                'important': '#ef4444', // red-500
                'general': '#6366f1' // indigo-500
            };
            if (!eventData.color || eventData.color === '#6366f1' || eventData.color === '#3498db') {
                eventData.color = categoryColors[eventData.category] || '#6366f1';
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
        eventClassNames: function (arg) {
            if (arg.event.extendedProps.completed) {
                return ['completed'];
            }
            return [];
        },

        // Attach Context Menu to Events
        eventDidMount: function (info) {
            info.el.addEventListener('contextmenu', function (e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent bubbling to day cell

                ctxTargetEvent = info.event;

                document.getElementById('ctxAdd').style.display = 'none';
                document.getElementById('ctxEdit').style.display = 'flex';

                // Recurring Event Logic
                if (info.event.extendedProps.recurrence_rule) {
                    document.getElementById('ctxDeleteThis').style.display = 'flex';
                    document.getElementById('ctxDeleteAll').style.display = 'flex';
                    document.getElementById('ctxDeleteAll').querySelector('span').innerText = '🔄';
                    document.getElementById('ctxDeleteAll').childNodes[1].nodeValue = ' 전체 반복 삭제';
                } else {
                    document.getElementById('ctxDeleteThis').style.display = 'none';
                    document.getElementById('ctxDeleteAll').style.display = 'flex';
                    document.getElementById('ctxDeleteAll').querySelector('span').innerText = '🗑️';
                    document.getElementById('ctxDeleteAll').childNodes[1].nodeValue = ' 삭제하기';
                }

                showMenu(e.pageX, e.pageY);
            });
        },

        dateClick: function (info) {
            eventForm.reset();
            eventIdInput.value = '';
            modalTitle.textContent = '새 일정 추가';
            deleteButton.style.display = 'none';

            document.getElementById('eventStart').value = info.dateStr + 'T10:00';
            document.getElementById('eventEnd').value = info.dateStr + 'T11:00';
            eventCategoryInput.value = 'general';
            eventColorInput.value = '#6366f1';
            document.querySelectorAll('.color-option').forEach(opt => {
                opt.classList.remove('selected');
                if (opt.dataset.color === '#6366f1') {
                    opt.classList.add('selected');
                }
            });
            tinymce.get('eventDescription').setContent('');
            document.getElementById('eventCompleted').checked = false;
            eventModal.style.display = 'block';
        },

        eventClick: function (info) {
            info.jsEvent.preventDefault();
            openEventModal(info.event);
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

    const closeModalButton = document.getElementById('closeModalButton');
    if (closeModalButton) {
        closeModalButton.onclick = function () {
            eventModal.style.display = 'none';
        }
    }

    const calendarSelectionModal = document.getElementById('calendarSelectionModal');
    const closeCalendarModal = document.getElementById('closeCalendarModal');
    const calendarListContainer = document.getElementById('calendarListContainer');
    const confirmSyncButton = document.getElementById('confirmSyncButton');

    if (closeCalendarModal) {
        closeCalendarModal.onclick = function () {
            calendarSelectionModal.style.display = 'none';
        };
    }

    // Modal outside click
    window.onclick = function (event) {
        if (event.target == eventModal) {
            eventModal.style.display = 'none';
        }
        if (event.target == calendarSelectionModal) {
            calendarSelectionModal.style.display = 'none';
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

        // Handle Advanced Recurrence Rule Construction
        let rrule = recurrenceRuleInput.value;
        if (rrule === 'custom') {
            const type = advRecureType.value;
            rrule = type;

            if (type === 'monthly_nth_weekday') {
                rrule += `:${advRecurNth.value}:${advRecurDay.value}`;
            }

            if (advRecurHoliday.value) {
                rrule += `;HOLIDAY=${advRecurHoliday.value}`;
            }
        }
        formData.append('recurrence_rule', rrule);

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
                    showToast('성공적으로 저장되었습니다.');
                } else {
                    alert('오류: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
    });

    if (deleteButton) {
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
    }

    const removeDuplicatesButton = document.getElementById('removeDuplicatesButton');
    if (removeDuplicatesButton) {
        removeDuplicatesButton.addEventListener('click', function () {
            if (confirm('중복된 일정(제목, 시간 동일)을 정리하시겠습니까?\n이 작업은 되돌릴 수 없습니다.')) {
                showToast('중복 일정을 정리 중입니다...');
                fetch('/api/remove_duplicates.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            if (data.count > 0) {
                                calendar.refetchEvents();
                                updateDDayList();
                            }
                        } else {
                            alert('오류: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('오류가 발생했습니다.');
                    });
            }
        });
    }

    if (googleLoginButton) {
        googleLoginButton.addEventListener('click', function () {
            if (this.classList.contains('success')) {
                if (!confirm('이미 연동되어 있습니다. 다시 연동(권한 갱신)하시겠습니까?')) {
                    return;
                }
            }
            window.location.href = '/api/google_login.php';
        });
    }

    if (googleSyncButton) {
        googleSyncButton.addEventListener('click', function () {
            // 1. 캘린더 목록 가져오기
            showToast('캘린더 목록을 불러오는 중...');
            fetch('/api/get_google_calendars.php')
                .then(async response => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('서버 응답 오류: ' + text.substring(0, 100));
                    }
                })
                .then(data => {
                    if (data.success) {
                        renderCalendarList(data.calendars);
                        calendarSelectionModal.style.display = 'block';
                    } else {
                        showToast('캘린더 목록 불러오기 실패: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast(error.message);
                });
        });
    }

    function renderCalendarList(calendars) {
        if (!calendars || calendars.length === 0) {
            calendarListContainer.innerHTML = '<p>동기화 가능한 캘린더가 없습니다.</p>';
            return;
        }

        calendarListContainer.innerHTML = calendars.map(cal => `
            <div class="calendar-item" style="display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee;">
                <input type="checkbox" id="cal_${cal.id}" value="${cal.id}" ${cal.primary ? 'checked' : ''} class="calendar-checkbox">
                <label for="cal_${cal.id}" style="margin-left: 10px; flex: 1; cursor: pointer; display: flex; align-items: center;">
                    <span style="width: 12px; height: 12px; border-radius: 50%; background-color: ${cal.backgroundColor}; display: inline-block; margin-right: 8px;"></span>
                    ${cal.summary}
                    ${cal.primary ? '<span style="font-size: 0.8em; color: gray; margin-left: 5px;">(기본)</span>' : ''}
                </label>
            </div>
        `).join('');
    }

    if (confirmSyncButton) {
        confirmSyncButton.addEventListener('click', function () {
            const checkboxes = document.querySelectorAll('.calendar-checkbox:checked');
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);

            if (selectedIds.length === 0) {
                alert('최소 하나의 캘린더를 선택해주세요.');
                return;
            }

            calendarSelectionModal.style.display = 'none';
            showToast('선택한 캘린더 동기화 중...'); // 오타 수정: 동기화 -> 동기화 중

            fetch('/api/google_sync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ calendar_ids: selectedIds })
            })
                .then(async response => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('서버 응답 오류: ' + text.substring(0, 100));
                    }
                })
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                        calendar.refetchEvents();
                        updateDDayList();
                    } else {
                        showToast('동기화 실패: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast(error.message);
                });
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

    function openEventModal(event, dateStr = null) {
        if (event) {
            // Edit Mode
            modalTitle.textContent = '일정 수정';
            deleteButton.style.display = 'inline-block';

            let endDateForModal = event.end;
            if (event.allDay && event.end) {
                let inclusiveEndDate = new Date(event.end);
                inclusiveEndDate.setDate(inclusiveEndDate.getDate() - 1);
                endDateForModal = inclusiveEndDate;
            }

            eventIdInput.value = event.groupId || event.id;
            document.getElementById('eventTitle').value = event.title;
            document.getElementById('allDay').checked = event.allDay;
            document.getElementById('eventStart').value = toLocalISOString(event.start);
            document.getElementById('eventEnd').value = toLocalISOString(endDateForModal || event.start);
            eventCategoryInput.value = event.extendedProps.category || 'general';

            // Recurrence Parsing
            const storedRule = event.extendedProps.recurrence_rule || '';
            if (storedRule && !['daily', 'weekly', 'monthly', 'yearly'].includes(storedRule)) {
                // It's a custom rule
                recurrenceRuleInput.value = 'custom';
                customRecurrenceOptions.style.display = 'block';

                const parts = storedRule.split(';');
                const mainRuleParts = parts[0].split(':');
                const baseType = mainRuleParts[0]; // monthly_last_day or monthly_nth_weekday

                advRecureType.value = baseType;
                if (baseType === 'monthly_nth_weekday') {
                    advRecurNthOptions.style.display = 'block';
                    if (mainRuleParts.length >= 3) {
                        advRecurNth.value = mainRuleParts[1];
                        advRecurDay.value = mainRuleParts[2];
                    }
                } else {
                    advRecurNthOptions.style.display = 'none';
                }

                // Holiday Shift
                advRecurHoliday.value = '';
                parts.forEach(p => {
                    if (p.startsWith('HOLIDAY=')) {
                        advRecurHoliday.value = p.split('=')[1];
                    }
                });
            } else {
                recurrenceRuleInput.value = storedRule;
                customRecurrenceOptions.style.display = 'none';
            }

            tinymce.get('eventDescription').setContent(event.extendedProps.description || '');
            eventColorInput.value = event.backgroundColor || '#3498db';
            document.getElementById('eventCompleted').checked = event.extendedProps.completed || false;

            document.querySelectorAll('.color-option').forEach(opt => {
                opt.classList.remove('selected');
                if (opt.dataset.color === eventColorInput.value) {
                    opt.classList.add('selected');
                }
            });
        } else {
            // Create Mode
            eventForm.reset();
            eventIdInput.value = '';
            modalTitle.textContent = '새 일정 추가';
            deleteButton.style.display = 'none';

            if (dateStr) {
                document.getElementById('eventStart').value = dateStr + 'T10:00';
                document.getElementById('eventEnd').value = dateStr + 'T11:00';
            } else {
                const now = new Date();
                const nowStr = toLocalISOString(now).slice(0, 16);
                document.getElementById('eventStart').value = nowStr;
                document.getElementById('eventEnd').value = nowStr;
            }

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
        }
        eventModal.style.display = 'block';
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

    function toLocalISOString(date) {
        if (!date) return '';
        const offset = date.getTimezoneOffset() * 60000; // offset in milliseconds
        const localISOTime = (new Date(date - offset)).toISOString().slice(0, 16); // YYYY-MM-DDTHH:mm
        return localISOTime;
    }

    // --- Notification System ---
    if ('Notification' in window) {
        if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            Notification.requestPermission();
        }
    }

    const notifiedEvents = new Set();

    function checkUpcomingEvents() {
        if (Notification.permission !== 'granted') return;

        const now = new Date();
        const events = calendar.getEvents();

        events.forEach(event => {
            if (!event.start) return;

            const timeDiff = event.start - now;
            const minutesDiff = Math.floor(timeDiff / 1000 / 60);

            // Notify if event starts in exactly 30 minutes (check window: 29-31 min to avoid missing polls)
            // And avoid duplicate notifications
            if (minutesDiff >= 29 && minutesDiff <= 31 && !notifiedEvents.has(event.id)) {
                new Notification('일정 알림', {
                    body: `30분 후에 [${event.title}] 일정이 시작됩니다.`,
                    icon: '/favicon.ico' // Optional
                });

                // Also show toast
                showToast(`🔔 30분 후: ${event.title}`);

                notifiedEvents.add(event.id);
            }
        });
    }

    // Check every minute
    setInterval(checkUpcomingEvents, 60000);
});
