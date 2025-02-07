<?php
class SystemService {
    private $salt = "Vmn34aAciYK00Hen26nT01";
    private $db;

    public function __construct() {
        $this->db = new PDO("mysql:host=localhost;dbname=ninjasaga", "root", "");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function login($username, $password, $buildNo, $buildReview, $nonce) {
        try {
            // Handle username
            $userBytes = $username instanceof Amfphp_Core_Amf_Types_ByteArray ? $username->data : $username;
            $decodedUsername = $this->unmask($userBytes, $nonce);
            $this->debug("Decoded username: $decodedUsername");

            // Get user data
            $stmt = $this->db->prepare("SELECT id, password_hash, account_type, balance FROM users WHERE username = ?");
            $stmt->execute([$decodedUsername]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return array("status" => "0", "error" => "Invalid credentials");
            }

            // Handle password
            $passBytes = $password instanceof Amfphp_Core_Amf_Types_ByteArray ? $password->data : $password;
            $decodedPassword = bin2hex($this->unmask($passBytes, $nonce));

            $this->debug("Unmasked password: $decodedPassword");
            $this->debug("Stored hash: " . $user['password_hash']);

            if (!hash_equals($decodedPassword, $user['password_hash'])) {
                return array("status" => "0", "error" => "Invalid credentials");
            }

            // Create session
            $sessionKey = bin2hex(random_bytes(16));
            $stmt = $this->db->prepare("UPDATE users SET session_key = ? WHERE id = ?");
            $stmt->execute([$sessionKey, $user['id']]);

            // Prepare result array
            $result = array(
                intval($user['id']),
                intval($user['account_type']),
                intval($user['balance']),
                $sessionKey
            );

            // Generate signature using first numeric character for offset
            $data = implode("|", array($user['id'], $user['account_type'], $user['balance']));
            $matches = array();
            preg_match('/[0-9]/', $data, $matches, PREG_OFFSET_CAPTURE, 1);
            $offset = $matches[0][0];

            $hashInput = $data . $this->salt;
            $fullHash = sha1($hashInput);
            $signature = substr($fullHash, intval($offset), 12);

            return array(
                "status" => "1",
                "error" => "0",
                "result" => $result,
                "signature" => $signature,
                "swf_versions" => "2016.12.25"
            );

        } catch (Exception $e) {
            $this->debug("Error: " . $e->getMessage());
            return array("status" => "0", "error" => "System error");
        }
    }

    private function unmask($bytes, $nonce) {
        $result = '';
        $nonceLen = strlen($nonce);
        for ($i = 0; $i < strlen($bytes); $i++) {
            $result .= chr(ord($bytes[$i]) ^ (~ord($nonce[$i % $nonceLen]) & 0xFF));
        }
        return $result;
    }

    private function debug($message) {
        error_log($message);
    }
}