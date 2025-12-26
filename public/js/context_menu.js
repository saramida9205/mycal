
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

// Prevent default browser context menu on calendar
calendarEl.addEventListener('contextmenu', function (e) {
    e.preventDefault();

    // Hide previous menu
    contextMenu.style.display = 'none';

    // Check what was clicked (Event or Day)
    const eventEl = e.target.closest('.fc-event');
    const dayEl = e.target.closest('.fc-daygrid-day') || e.target.closest('.fc-timegrid-slot');

    ctxTargetEvent = null;
    ctxTargetDate = null;

    let x = e.clientX;
    let y = e.clientY;

    if (eventEl && calendar) {
        // Clicked on an Event
        const fcEvent = calendar.getEventById(eventEl.getAttribute('data-event-id') ||  // For some views
            calendar.getEvents().find(ev => ev.el === eventEl)?.id); // Fallback lookup

        // Better way: use FullCalendar's internal data binding if possible, or just API lookup
        // Actually, 'eventEl' might not have ID directly on it in v5 DOM.
        // Let's rely on click handling logic or try to find the event object.
        // FullCalendar v5 doesn't easily expose 'getEventByElement'.
        // Workaround: We will rely on 'eventClick' for left click, but for right click?
        // We can't easily get the event object from DOM element in v5 without internal API.
        // ALTERNATIVE: Use FullCalendar's `eventDidMount` to attach context menu listener to each element?
        // YES, that's better. See below.
        return; // Logic handled in eventDidMount or general listener below
    } else if (dayEl) {
        // Clicked on empty date cell
        const dateStr = dayEl.getAttribute('data-date');
        if (dateStr) {
            ctxTargetDate = dateStr;

            // Show "Add Event" only
            document.getElementById('ctxAdd').style.display = 'flex';
            document.getElementById('ctxEdit').style.display = 'none';
            document.getElementById('ctxDeleteThis').style.display = 'none';
            document.getElementById('ctxDeleteAll').style.display = 'none';

            showMenu(x, y);
        }
    }
});

// Helper to show menu
function showMenu(x, y) {
    contextMenu.style.left = x + 'px';
    contextMenu.style.top = y + 'px';
    contextMenu.style.display = 'block';
}

// Close menu on click elsewhere
document.addEventListener('click', function () {
    contextMenu.style.display = 'none';
});

// Menu Actions
document.getElementById('ctxAdd').addEventListener('click', function () {
    if (ctxTargetDate) {
        openEventModal(null, ctxTargetDate);
    }
});

document.getElementById('ctxEdit').addEventListener('click', function () {
    if (ctxTargetEvent) {
        openEventModal(ctxTargetEvent);
    }
});

document.getElementById('ctxDeleteThis').addEventListener('click', function () {
    if (ctxTargetEvent) {
        deleteEvent(ctxTargetEvent.id, 'this', ctxTargetEvent.startStr.split('T')[0]);
    }
});

document.getElementById('ctxDeleteAll').addEventListener('click', function () {
    if (ctxTargetEvent) {
        deleteEvent(ctxTargetEvent.id, 'all');
    }
});

// Function to delete event
function deleteEvent(id, mode = 'all', date = null) {
    if (!confirm(mode === 'this' ? '이 일정만 삭제하시겠습니까?' : '이 일정을 포함한 모든 반복 일정이 삭제됩니다.')) return;

    fetch('/api/delete_event.php', {
        method: 'POST',
        header: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, mode: mode, date: date })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || '삭제되었습니다.');
                calendar.refetchEvents();
                updateDdayList();
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('삭제 중 오류가 발생했습니다.');
        });
}

// Attach Context Menu to Events via eventClassNames or similar hook is hard for Right Click.
// Instead, global delegated listener is better?
// Let's refine the global listener.
// Finding the FullCalendar Event object from DOM:
// We can iterate calendar.getEvents() and find one matching styling? No.
// We can attach a specialized listener in `eventDidMount`.
