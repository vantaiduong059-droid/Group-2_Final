<?php
// app/Models/Course.php
require_once '../core/Model.php';

class Course extends Model {
    public $table = 'courses';
    
    // Model không chứa logic truy vấn phức tạp, đã đẩy qua Repository
}
