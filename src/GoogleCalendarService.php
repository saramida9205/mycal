<?php
require_once __DIR__ . '/google_config.php';

class GoogleCalendarService
{
    private $pdo;
    private $userId;

    public function __construct($pdo, $userId)
    {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    // 액세스 토큰 가져오기 및 필요시 갱신
    public function getAccessToken()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_tokens WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$token) return null;

        // 토큰 만료 5분 전이면 갱신
        if ($token['google_token_expires'] <= (time() + 300)) {
            return $this->refreshAccessToken($token['google_refresh_token']);
        }

        return $token['google_access_token'];
    }

    private function refreshAccessToken($refreshToken)
    {
        if (!$refreshToken) return null;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ]));
        // Synology/Local dev SSL fix
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            $newAccessToken = $data['access_token'];
            $expiresIn = time() + $data['expires_in'];

            $stmt = $this->pdo->prepare("UPDATE user_tokens SET google_access_token = ?, google_token_expires = ? WHERE user_id = ?");
            $stmt->execute([$newAccessToken, $expiresIn, $this->userId]);

            return $newAccessToken;
        }

        return null;
    }

    // 구글 캘린더 목록 가져오기
    public function getCalendarList()
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $ch = curl_init('https://www.googleapis.com/calendar/v3/users/me/calendarList');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        if (isset($data['error'])) {
            throw new Exception("Google API Error: " . ($data['error']['message'] ?? json_encode($data['error'])));
        }

        return $data['items'] ?? [];
    }

    // 구글 캘린더에서 이벤트 가져오기 (특정 캘린더 ID 지원)
    public function fetchEvents($calendarId = 'primary')
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $calendarIdEncoded = urlencode($calendarId);
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calendarIdEncoded}/events?" . http_build_query([
            'timeMin' => date('c', strtotime('-1 year')), // 최근 1년치
            'timeMax' => date('c', strtotime('+1 year')), // 향후 1년치
            'singleEvents' => 'true', // 반복 일정을 개별 인스턴스로 확장하여 가져옴 (정확도 향상)
            'maxResults' => 2000, // 최대 2000개
            'orderBy' => 'startTime' // singleEvents=true일 때 startTime 정렬 가능
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        // Synology/Local dev SSL fix
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch); // Resource release

        if (isset($data['error'])) {
            throw new Exception("Google API Error: " . ($data['error']['message'] ?? json_encode($data['error'])));
        }

        return $data['items'] ?? [];
    }
}
