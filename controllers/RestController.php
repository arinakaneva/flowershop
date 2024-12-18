<?php
namespace app\controllers;
use yii\rest\ActiveController;

class RestController extends ActiveController
{

    public function Response($status, $data = null){
        $response = $this->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = $data;
        $response->statusCode = $status;
        return $response;
    }

    public function ValidationError($model) {
        if (!$model->validate()) {
            return $this->Response(422, ['message' => 'Ошибка валидации', 'errors' => $model->errors]);
        }
        return false;
    }
}