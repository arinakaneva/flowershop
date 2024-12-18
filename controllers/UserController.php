<?php

namespace app\controllers;

use app\models\User;
use yii\rest\ActiveController;
use yii\web\Response;
use Yii;

class UserController extends RestController
{
    public $modelClass = 'app\models\User';
    public function actions()
    {
        return ['create'];
    }
    public function actionCreate()
    {

        $data = Yii::$app->request->post();
        $user = new User();
        $user->scenario = User::SCENARIO_REGISTER;
        $user->load($data, '');
        if ($this->ValidationError($user)) return $this->ValidationError($user);
        $user->password = Yii::$app->getSecurity()->generatePasswordHash($user->password);
        $user->save();
        return $this->Response(201, [
            'id_user' => $user->id_user,
            'message' => 'Пользователь зарегистрирован'
        ]);
    }
    public function actionLogin()
    {
        $data = Yii::$app->request->post();
        $user = new User();
        $user->scenario = User::SCENARIO_LOGIN;
        $user->load($data, '');

        if ($this->ValidationError($user)) return $this->ValidationError($user);
        $user = null;
        $user = User::findOne(['email' => $data['email']]);
        if ($user) {
            if ($user->validatePassword($data['password'])) {
                $user->token = Yii::$app->getSecurity()->generateRandomString();
                $user->save();
                return $this->Response(200, [
                    'token' => $user->token
                ]);
            }
        }
        return $this->Response(401, [
            'message' => 'Неправильный email или пароль'
        ]);
    
    }
    public function actionUser()
    {
        
            $user = User::getByToken();
            if ($user && $user->isAuthorized()) {
                return $this->Response(200, ['data' => $user]);
            }
            return $this->Response(401, ['error' => ['message' => 'Вы не авторизованы']]);
    
    }
}
