<?php

class CharacterDAO {
    private $db;

    public function __construct() {
        $this->db = new mysqli("localhost", "root", "", "ninjasaga");
        if ($this->db->connect_error) {
            throw new Exception("Database connection failed: " . $this->db->connect_error);
        }
    }

    public function getCharactersList($session_key) {
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

        $account_id = $account['account_id'];
        $query = "SELECT * FROM characters WHERE account_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $characters = [];
        while ($row = $result->fetch_assoc()) {
            $characters[] = $row;
        }

        return [
            'status' => '1',
            'result' => $characters
        ];
    }

    public function createCharacter($session_key, $name, $gender, $hairColorIndex, $skinColorIndex, $hair, $face) {
        // Get account ID based on session key
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

        $account_id = $account['account_id'];
        $level = 1;
        $gold = 300;
        $hp = $cp = $max_hp = $max_cp = 100;
        $character_hair_color = json_encode([$hairColorIndex]);
        $character_skin_color = json_encode([$skinColorIndex]);
        $character_face = "face_$face" . "_" . $gender;
        $character_hair = "hair_$hair" . "_" . $gender;

        $query = "INSERT INTO characters (
            account_id, character_name, character_gender, character_gold, character_hp, 
            character_max_hp, character_cp, character_max_cp, character_hair_color, 
            character_skin_color, character_face, character_hair, character_level
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return [
                'status' => 'error',
                'error' => 'Prepare failed: ' . $this->db->error
            ];
        }

        $stmt->bind_param(
            "issiiiiissssi",
            $account_id,
            $name,
            $gender,
            $gold,
            $hp,
            $max_hp,
            $cp,
            $max_cp,
            $character_hair_color,
            $character_skin_color,
            $character_face,
            $character_hair,
            $level
        );

        if (!$stmt->execute()) {
            return [
                'status' => 'error',
                'error' => 'Failed to execute query: ' . $stmt->error
            ];
        }

        return [
            'status' => '1',
            'result' => 'Character created successfully'
        ];
    }

    public function getExtraData($session_key, $hash, $access_token) {
        // Validate session
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE account_session_key = ? LIMIT 1");
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

        $account_id = $account['account_id'];

        // Get character (assuming 1 character per account for simplicity)
        $char_query = $this->db->prepare("SELECT * FROM characters WHERE account_id = ? LIMIT 1");
        $char_query->bind_param("i", $account_id);
        $char_query->execute();
        $char_result = $char_query->get_result();
        $character = $char_result->fetch_assoc();

        if (!$character) {
            return [
                'status' => 'error',
                'error' => 'Character not found'
            ];
        }

        // Build extra data response
        $extraData = [
            'character_id' => $character['character_id'],
            'character_name' => $character['character_name'],
            'character_level' => (int)$character['character_level'],
            'character_xp' => (int)$character['character_xp'],
            'char_login_per_day' => 1,
            'player_pet' => [], // Placeholder, populate with pet data if exists
            'inventory' => [], // Placeholder
        ];

        return [
            'status' => '1',
            'result' => $extraData
        ];
    }
}
