<?php
// app/Models/SystemConfig.php
require_once '../core/Model.php';

class SystemConfig extends Model {
    public $table = 'system_configs';
    protected $primaryKey = 'config_key'; // Khóa chính dạng chuỗi
}
