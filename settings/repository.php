<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class SettingsRepository extends BaseRepository
{
    protected string $table = 'system_settings';

    public function getAll(): array
    {
        $result = $this->connection()->query("SELECT setting_key, setting_value FROM system_settings");

        $settings = [];

        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    public function updateBatch(array $data): void
    {
        $stmt = $this->prepare("
            INSERT INTO system_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($data as $key => $value) {
            $stmt->bind_param("ss", $key, $value);
            $this->execute($stmt);
        }
    }
}