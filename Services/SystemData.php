<?php

class SystemData {
    private $db;

    public function __construct() {
        $this->db = new mysqli("localhost", "root", "", "ninjasaga");
        if ($this->db->connect_error) {
            throw new Exception("Database connection failed: " . $this->db->connect_error);
        }
    }

    /**
     * Retrieve system data after character creation.
     * @param string $session_key
     * @param string $version
     * @return array
     */
    public function getCreateCharacter($session_key, $version) {
        // Validate session
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE account_session_key = ?");
        $stmt->bind_param("s", $session_key);
        $stmt->execute();
        $result = $stmt->get_result();
        $account = $result->fetch_assoc();

        if (!$account) {
            return [
                'status' => 'error',
                'error' => 'Invalid session key'
            ];
        }

        // You can customize the system data below
        $system_data = [
            'daily_quests' => [],
            'missions' => [],
            'initial_items' => ['weapon_01', 'armor_01'],
            'version' => $version,
        ];

        return [
            'status' => '1',
            'result' => $system_data
        ];
    }
}
