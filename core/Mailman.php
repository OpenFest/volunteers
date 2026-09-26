<?php

class Mailman
{
    private string $apiUrl;

    public function __construct(
        string         $apiUrl,
        private string $username,
        private string $password
    )
    {
        $this->apiUrl = rtrim($apiUrl, '/');
    }

    /**
     * @throws Exception
     */
    public function getUsers(int $count = 50, int $page = 1): array|string
    {
        return $this->request('GET', '/users', [
            'count' => $count,
            'page' => $page
        ]);
    }

    /**
     * @throws Exception
     */
    public function getLists(int $count = 50, int $page = 1): array|string
    {
        return $this->request('GET', '/lists', [
            'count' => $count,
            'page' => $page
        ]);
    }

    /**
     * @throws Exception
     */
    public function getMembers(string $listId, int $count = 50, int $page = 1): array|string
    {
        $endpoint = sprintf('/lists/%s/roster/member', urlencode($listId));

        return $this->request('GET', $endpoint, [
            'count' => $count,
            'page' => $page
        ]);
    }

    /**
     * @throws Exception
     */
    public function isMember(string $listId, string $email): bool
    {
        $endpoint = sprintf('/lists/%s/member/%s', urlencode($listId), urlencode($email));

        try {
            $this->request('GET', $endpoint);
            return true;
        } catch (Exception) {
            return false;
        }
    }


    /**
     * @throws Exception
     */
    public function subscribe(string $listId, string $email, string $displayName = '', bool $bypassAuth = true): array|string
    {
        $data = [
            'list_id' => $listId,
            'subscriber' => $email,
            'display_name' => $displayName,
            'pre_verified' => $bypassAuth,
            'pre_confirmed' => $bypassAuth,
            'pre_approved' => $bypassAuth,
        ];

        return $this->request('POST', '/members', $data);
    }

    /**
     * @throws Exception
     */
    public function unsubscribe(string $listId, string $email): array|string
    {
        $endpoint = sprintf('/lists/%s/member/%s', urlencode($listId), urlencode($email));

        return $this->request('DELETE', $endpoint);
    }


    /**
     * @throws Exception
     */
    private function request(string $method, string $endpoint, array $data = []): array|string
    {
        $url = $this->apiUrl . $endpoint;
        $ch = curl_init();
        $method = strtoupper($method);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (!empty($data)) {
            if ($method === 'GET') {
                $url .= '?' . http_build_query($data);
            } else {
                $jsonPayload = json_encode($data, JSON_THROW_ON_ERROR);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonPayload)
                ]);
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error !== '') {
            throw new Exception("cURL Error: " . $error);
        }

        if ($httpCode >= 400) {
            $errorDetails = is_string($response) ? $response : 'Unknown Error';
            throw new Exception("API Error HTTP {$httpCode}: " . $errorDetails);
        }

        if (empty($response)) {
            return [];
        }

        try {
            return json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return (string)$response;
        }
    }
}