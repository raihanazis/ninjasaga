<?php

class ReportService
{
    /**
     * Receive log dump from client and store in server
     *
     * @param string $sessionKey
     * @param int $characterId
     * @param string $logDump
     * @param string $flashVersion
     * @param string $playerType
     * @param string $os
     * @param string $buildNo
     * @return array
     */
    public function reportLogDump($sessionKey, $characterId, $logDump, $flashVersion, $playerType, $os, $buildNo)
    {
        // Buat folder log jika belum ada
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Generate nama file log
        $filename = $logDir . '/logdump_' . date('Ymd_His') . '_char' . intval($characterId) . '.log';

        // Format isi log
        $logContent = "=== Report Log Dump ===\n";
        $logContent .= "Timestamp     : " . date('Y-m-d H:i:s') . "\n";
        $logContent .= "Session Key   : " . $sessionKey . "\n";
        $logContent .= "Character ID  : " . $characterId . "\n";
        $logContent .= "Flash Version : " . $flashVersion . "\n";
        $logContent .= "Player Type   : " . $playerType . "\n";
        $logContent .= "OS            : " . $os . "\n";
        $logContent .= "Build No      : " . $buildNo . "\n";
        $logContent .= "-------------------------\n";
        $logContent .= $logDump . "\n";

        // Tulis ke file
        $success = file_put_contents($filename, $logContent);

        // Response ke client Flash
        return [
            'status' => '1',
            'result' => 'Log submitted'
        ];
    }
}
