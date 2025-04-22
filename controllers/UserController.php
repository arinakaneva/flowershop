<?php

namespace app\controllers;

use app\models\User;
use yii\rest\ActiveController;
use yii\web\Response;
use Yii;
use yii\filters\Cors;

class UserController extends RestController
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Удаляем стандартный authenticator
        unset($behaviors['authenticator']);
        
        // Добавляем CORS фильтр
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['http://localhost:5174'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ],
        ];
        
        return $behaviors;
    }

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

    public function actionLogout()
    {
        $user = User::getByToken();
        if ($user) {
            $user->token = null;
            $user->save();
            return $this->Response(200, [
                'message' => 'Вы успешно вышли из системы'
            ]);
        }
        return $this->Response(401, [
            'message' => 'Пользователь не авторизован'
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
