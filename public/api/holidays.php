<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
check_auth_api();

header('Content-Type: application/json');

// 실제 환경에서는 공공데이터포털 API를 사용하거나 DB에서 가져오지만,
// 여기서는 예시로 주요 공휴일을 JSON으로 반환합니다.
$holidays = [
    ['title' => '신정', 'start' => '2024-01-01', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '설날', 'start' => '2024-02-09', 'end' => '2024-02-12', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '삼일절', 'start' => '2024-03-01', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '어린이날', 'start' => '2024-05-05', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '부처님오신날', 'start' => '2024-05-15', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '현충일', 'start' => '2024-06-06', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '광복절', 'start' => '2024-08-15', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '추석', 'start' => '2024-09-16', 'end' => '2024-09-19', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '개천절', 'start' => '2024-10-03', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '한글날', 'start' => '2024-10-09', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '크리스마스', 'start' => '2024-12-25', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    // 2025
    ['title' => '신정', 'start' => '2025-01-01', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
    ['title' => '설날', 'start' => '2025-01-28', 'end' => '2025-01-31', 'allDay' => true, 'display' => 'background', 'color' => '#ffcccc'],
];

echo json_encode($holidays);
