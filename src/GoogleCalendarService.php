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

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        if (isset($data['access_token'])) {
            $newAccessToken = $data['access_token'];
            $expiresIn = time() + $data['expires_in'];

            $stmt = $this->pdo->prepare("UPDATE user_tokens SET google_access_token = ?, google_token_expires = ? WHERE user_id = ?");
            $stmt->execute([$newAccessToken, $expiresIn, $this->userId]);

            return $newAccessToken;
        }

        return null;
    }

    // 구글 캘린더에서 이벤트 가져오기 (단순화 버전)
    public function fetchEvents()
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $url = "https://www.googleapis.com/calendar/v3/calendars/primary/events?" . http_build_query([
            'timeMin' => date('c', strtotime('-1 month')),
            'singleEvents' => 'true',
            'maxResults' => 100
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        return $data['items'] ?? [];
    }
}
