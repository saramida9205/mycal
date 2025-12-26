<?php

class HolidayService
{
    // Hardcoded holidays for now. In a real app, this could fetch from DB or external API.
    // Format: ['start' => 'YYYY-MM-DD', 'title' => 'Name']
    private static $holidays = [
        ['title' => '신정', 'start' => '2024-01-01'],
        ['title' => '설날', 'start' => '2024-02-09', 'end' => '2024-02-12'],
        ['title' => '삼일절', 'start' => '2024-03-01'],
        ['title' => '어린이날', 'start' => '2024-05-05'],
        ['title' => '부처님오신날', 'start' => '2024-05-15'],
        ['title' => '현충일', 'start' => '2024-06-06'],
        ['title' => '광복절', 'start' => '2024-08-15'],
        ['title' => '추석', 'start' => '2024-09-16', 'end' => '2024-09-19'],
        ['title' => '개천절', 'start' => '2024-10-03'],
        ['title' => '한글날', 'start' => '2024-10-09'],
        ['title' => '크리스마스', 'start' => '2024-12-25'],
        // 2025
        ['title' => '신정', 'start' => '2025-01-01'],
        ['title' => '설날', 'start' => '2025-01-28', 'end' => '2025-01-31'],
    ];

    public static function getHolidays()
    {
        // Add display properties for FullCalendar
        return array_map(function ($h) {
            $h['allDay'] = true;
            $h['display'] = 'background';
            $h['color'] = '#ffcccc';
            return $h;
        }, self::$holidays);
    }

    public static function isHoliday($dateStr)
    {
        foreach (self::$holidays as $h) {
            $start = $h['start'];
            $end = $h['end'] ?? $start; // If no end, it's single day

            // Simple string comparison for single day
            if ($start === $dateStr) return true;

            // Range check
            if ($dateStr >= $start && $dateStr <= $end) return true;
        }
        return false;
    }
}
