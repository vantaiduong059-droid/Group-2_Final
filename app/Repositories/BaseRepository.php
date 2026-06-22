<?php

require_once '../core/Model.php';

abstract class BaseRepository {
    protected $model;

    public function __construct(Model $model) {
        $this->model = $model;
    }

    public function getAll() {
        return $this->model->findAll();
    }

    public function getById($id) {
        return $this->model->findById($id);
    }

    public function delete($id) {
        return $this->model->delete($id);
    }


}
