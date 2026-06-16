<?php
// app/Repositories/SystemConfigRepository.php
require_once 'BaseRepository.php';

class SystemConfigRepository extends BaseRepository {

    public function getAllConfigs() {
        $stmt = $this->model->db->prepare("SELECT * FROM {$this->model->table}");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $configs = [];
        foreach ($rows as $row) {
            $configs[$row['config_key']] = [
                'value' => $row['config_value'],
                'description' => $row['description']
            ];
        }
        return $configs;
    }

    public function getByKey($key, $default = null) {
        $stmt = $this->model->db->prepare("SELECT config_value FROM {$this->model->table} WHERE config_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public function updateConfig($key, $value) {
        $stmt = $this->model->db->prepare("
            UPDATE {$this->model->table} 
            SET config_value = :value 
            WHERE config_key = :key
        ");
        return $stmt->execute([
            'value' => $value,
            'key' => $key
        ]);
    }

    public function getConfigsArray() {
        $stmt = $this->model->db->prepare("SELECT config_key, config_value FROM {$this->model->table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
