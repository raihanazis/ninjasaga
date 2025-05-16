<?php

class HashUtil {
    private static $s = "Vmn34aAciYK00Hen26nT01";

    public static function generateHash(string $param1, string $param2): string {
        // Dapatkan index berdasarkan karakter ke-1 dari session_key
        $indexHexChar = substr($param1, 1, 1);
        $startIndex = hexdec($indexHexChar);

        // SHA1 hash dari param2
        $hash = sha1($param2);

        // Ambil substring sepanjang 12 karakter dari posisi $startIndex
        return substr($hash, $startIndex, 12);
    }

    public static function getHash(string $param1, string $sessionKey): string {
        $toHash = $param1 . self::$s . $sessionKey;
        return self::generateHash($sessionKey, $toHash);
    }

    public static function getArrayHash(array $param1, string $sessionKey): string {
        return self::getHash(implode(',', $param1), $sessionKey);
    }
}
