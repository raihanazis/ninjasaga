<?php

require_once 'HashUtil.php';
class SystemService
{
    /**
     * Login service for AMFPHP (AMF0 safe return)
     * @param string $username
     * @param string $password
     * @param string $buildNo
     * @param string $buildReview
     * @return array
     */
    public function login($username, $password, $buildNo, $buildReview)
    {
        // Connect to DB
        $db = new mysqli("localhost", "root", "", "ninjasaga");

        if ($db->connect_error) {
            return [
                'status' => 'error',
                'error' => 'Database connection failed'
            ];
        }

        // Sanitize
        $username = $db->real_escape_string($username);
        $password = $db->real_escape_string($password);

        // Query account
        $query = "SELECT * FROM accounts WHERE username = '$username' LIMIT 1";
        $result = $db->query($query);

        if (!$result || $result->num_rows === 0) {
            return [
                'status' => 'error',
                'error' => 'Account not found'
            ];
        }

        $acc = $result->fetch_assoc();

        if ($acc['password'] !== $password) {
            return [
                'status' => 'error',
                'error' => 'Invalid password'
            ];
        }

        // Create session key
        $session_key = bin2hex(random_bytes(16));

        $update = $db->prepare("UPDATE accounts SET account_session_key = ? WHERE id = ?");
        $update->bind_param("si", $session_key, $acc['id']);
        $update->execute();

        // Build result
        $param1 = [
            (int)$acc['account_id'],       // ID
            (int)$acc['account_type'],     // type
            (int)$acc['account_balance'],  // balance
            $session_key                   // session key
        ];

        $stringToHash = $param1[0] . '|' . $param1[1] . '|' . $param1[2];

        $signature = HashUtil::getArrayHash($param1, $session_key);

        // Buat isi log
        $logMessage = "[" . date("Y-m-d H:i:s") . "] "
            . "SessionKey: $session_key | "
            . "Param1: " . implode(',', $param1) . " | "
            . "Signature: $signature" . PHP_EOL;

// Tulis ke log file
        file_put_contents("C:/laragon/www/amf/log.txt", $logMessage, FILE_APPEND);

        // Final response
        return [
            'status' => '1',
            'result' => $param1,
            'signature' => $signature,
        ];
    }
}
