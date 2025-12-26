<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
check_auth_api();

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

$userId = $_SESSION['user_id'] ?? 1;

try {
    // Fetch all events for the user
    $stmt = $pdo->prepare(
        'SELECT id, title, start, end, allDay, completed, color, category, recurrence_rule, exdates, description FROM events WHERE user_id = ?'
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

            // Check if within view
            if ($event_start_dt < $view_end && $event_end_dt >= $view_start) {
                $end_date_for_fc = $row['end'];
                if ($isAllDay) {
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
                        'category' => $row['category'],
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

            $exdates = [];
            if (!empty($row['exdates'])) {
                $exdates = explode(',', $row['exdates']);
            }

            // Parse Rule
            $ruleParts = explode(';', $rule);
            $baseRule = $ruleParts[0];
            $holidayShift = null; // BEFORE, AFTER

            foreach ($ruleParts as $part) {
                if (strpos($part, 'HOLIDAY=') === 0) {
                    $holidayShift = substr($part, 8);
                }
            }

            // Advanced Rules Setup
            $nthParams = [];
            $isAdvancedMonthly = false;

            if (strpos($baseRule, 'monthly_') === 0 && $baseRule !== 'monthly') {
                $isAdvancedMonthly = true;
                if ($baseRule === 'monthly_nth_weekday') {
                    $parts = explode(':', $baseRule);
                    if (count($parts) >= 3) {
                        $nthParams['n'] = $parts[1];
                        $nthParams['day'] = $parts[2];
                    }
                }
            }

            // Loop through occurrences
            while ($current_date < $view_end) {
                $target_date = clone $current_date;

                // 1. Calculate Target Date for Advanced Rules
                if ($isAdvancedMonthly) {
                    if ($baseRule === 'monthly_first_day') {
                        $target_date->modify('first day of this month');
                    } elseif ($baseRule === 'monthly_last_day') {
                        $target_date->modify('last day of this month');
                    } elseif ($baseRule === 'monthly_nth_weekday') {
                        $ordinal = ['1' => 'first', '2' => 'second', '3' => 'third', '4' => 'fourth', '5' => 'last'][$nthParams['n']] ?? 'first';
                        $dayName = $nthParams['day'];
                        $target_date->modify("{$ordinal} {$dayName} of this month");
                    }
                }

                // 2. Apply Holiday Shifting
                if ($holidayShift) {
                    require_once __DIR__ . '/../../src/HolidayService.php';
                    $max_shifts = 5;
                    $shifts = 0;

                    while ($shifts < $max_shifts) {
                        $isDateHoliday = HolidayService::isHoliday($target_date->format('Y-m-d'));
                        $isWeekend = (int)$target_date->format('N') >= 6; // 6=Sat, 7=Sun

                        if (!$isDateHoliday && !$isWeekend) {
                            break;
                        }

                        if ($holidayShift === 'BEFORE') {
                            $target_date->modify('-1 day');
                        } elseif ($holidayShift === 'AFTER') {
                            $target_date->modify('+1 day');
                        }
                        $shifts++;
                    }
                }

                // 3. Render Event
                $target_date_str = $target_date->format('Y-m-d');
                if ($target_date >= $view_start && $target_date < $view_end && !in_array($target_date_str, $exdates)) {
                    $event_end = clone $target_date;
                    $event_end->add($duration);

                    $display_end = clone $event_end;
                    if ($isAllDay) {
                        $display_end->modify('+1 day');
                        $display_end_str = $display_end->format('Y-m-d');
                    } else {
                        $display_end_str = $display_end->format('Y-m-d\TH:i:s');
                    }

                    $events[] = [
                        'id'      => $row['id'] . '_' . $target_date->format('Ymd'),
                        'groupId' => $row['id'],
                        'title'   => $row['title'],
                        'start'   => $isAllDay ? $target_date->format('Y-m-d') : $target_date->format('Y-m-d\TH:i:s'),
                        'end'     => $display_end_str,
                        'color'   => $row['color'],
                        'allDay'  => $isAllDay,
                        'editable' => false,
                        'extendedProps' => [
                            'category' => $row['category'],
                            'recurrence_rule' => $rule,
                            'description' => $row['description'],
                            'completed' => $isCompleted
                        ]
                    ];
                }

                // 4. Increment for NEXT iteration
                if ($isAdvancedMonthly) {
                    $current_date->modify('first day of next month');
                } else {
                    switch (substr($baseRule, 0, 7)) {
                        case 'daily':
                            $current_date->modify('+1 day');
                            break;
                        case 'weekly':
                            $current_date->modify('+1 week');
                            break;
                        case 'monthly':
                            $current_date->modify('+1 month');
                            break;
                        case 'yearly':
                            $current_date->modify('+1 year');
                            break;
                        default:
                            break 2;
                    }
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
