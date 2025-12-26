<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
check_auth_api();

header('Content-Type: application/json');

// 실제 환경에서는 공공데이터포털 API를 사용하거나 DB에서 가져오지만,
// 여기서는 예시로 주요 공휴일을 JSON으로 반환합니다.
require_once __DIR__ . '/../../src/HolidayService.php';

$holidays = HolidayService::getHolidays();
echo json_encode($holidays);
