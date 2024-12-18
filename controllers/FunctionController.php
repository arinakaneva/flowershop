<?php
namespace app\controllers;
use yii\rest\Controller;
use yii\widgets\ActiveForm;

class FunctionController extends Controller{

 public function send($code, $data){
    $response=$this→response;
    $response->format = yii\web\Response::FORMAT_JSON;
    $response->data=$data;
    $response->statusCode=$code;
    return $response;
    }

public function validation($model){
    $error=['error'=> ['code'=>422, 'message'=>'Validation error', 'errors'=>ActiveForm::validate($model)]];
    return $this->send(422, $error);
    }


} 
