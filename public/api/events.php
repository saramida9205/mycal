<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$view_start_str = $_GET['start'] ?? 'first day of this month';
$view_end_str = $_GET['end'] ?? 'last day of this month';

try {
    $view_start = new DateTime($view_start_str);
    $view_end = new DateTime($view_end_str);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid start or end date format.']);
    exit;
}

$userId = 1; // TODO: Replace with session user ID

try {
    // Fetch all events for the user. Filtering by date is complex with recurrence,
    // so we fetch all and process in PHP. For large datasets, this should be optimized.
    $stmt = $pdo->prepare(
        'SELECT id, title, start, end, allDay, completed, color, recurrence_rule, description FROM events WHERE user_id = ?'
    );
    $stmt->execute([$userId]);
    $all_user_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = [];
    foreach ($all_user_events as $row) {
        $isAllDay = (bool)$row['allDay'];
        $isCompleted = (bool)$row['completed'];

        if (empty($row['recurrence_rule'])) {
            // Non-recurring event
            $event_start_dt = new DateTime($row['start']);
            $event_end_dt = new DateTime($row['end']);

            // Check if the event falls within the calendar's view
            if ($event_start_dt < $view_end && $event_end_dt >= $view_start) {
                $end_date_for_fc = $row['end'];
                if ($isAllDay) {
                    // For all-day events, FullCalendar expects the end date to be exclusive.
                    // The DB stores an inclusive end date (e.g., event on 10th is start:10, end:10).
                    // We need to provide the end as the morning of the next day.
                    $end_dt = new DateTime($row['end']);
                    $end_dt->modify('+1 day');
                    $end_date_for_fc = $end_dt->format('Y-m-d');
                }

                $events[] = [
                    'id'    => $row['id'],
                    'title' => $row['title'],
                    'start' => $row['start'],
                    'end'   => $end_date_for_fc,
                    'color' => $row['color'],
                    'allDay' => $isAllDay,
                    'extendedProps' => [
                        'recurrence_rule' => null,
                        'description' => $row['description'],
                        'completed' => $isCompleted
                    ]
                ];
            }
        } else {
            // Recurring event
            $start_dt = new DateTime($row['start']);
            $end_dt = new DateTime($row['end']);
            $duration = $start_dt->diff($end_dt);
            
            $current_date = clone $start_dt;
            $rule = $row['recurrence_rule'];

            // Loop through occurrences
            while ($current_date < $view_end) {
                if ($current_date >= $view_start) {
                    $event_end = clone $current_date;
                    $event_end->add($duration);
                    
                    $display_end = clone $event_end;
                    if ($isAllDay) {
                        $display_end->modify('+1 day');
                        $display_end_str = $display_end->format('Y-m-d');
                    } else {
                        $display_end_str = $display_end->format('Y-m-d\TH:i:s');
                    }

                    $events[] = [
                        'id'      => $row['id'] . '_' . $current_date->format('Ymd'),
                        'groupId' => $row['id'],
                        'title'   => $row['title'],
                        'start'   => $isAllDay ? $current_date->format('Y-m-d') : $current_date->format('Y-m-d\TH:i:s'),
                        'end'     => $display_end_str,
                        'color'   => $row['color'],
                        'allDay'  => $isAllDay,
                        'editable' => false,
                        'extendedProps' => [
                            'recurrence_rule' => $rule,
                            'description' => $row['description'],
                            'completed' => $isCompleted
                        ]
                    ];
                }

                // Move to the next occurrence
                switch ($rule) {
                    case 'daily':   $current_date->modify('+1 day'); break;
                    case 'weekly':  $current_date->modify('+1 week'); break;
                    case 'monthly': $current_date->modify('+1 month'); break;
                    case 'yearly':  $current_date->modify('+1 year'); break;
                    default: break 2; // Exit both switch and while
                }
            }
        }
    }

    echo json_encode($events);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'General error: ' . $e->getMessage()]);
}