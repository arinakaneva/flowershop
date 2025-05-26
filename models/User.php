<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id_user
 * @property string $name
 * @property string $surname
 * @property string $email
 * @property string $phone
 * @property string $password
 * @property int|null $admin
 * @property string $token
 * @property int $id_role
 * @property Role $id_role0
 */
class User extends \yii\db\ActiveRecord
{
  const SCENARIO_LOGIN = 'login';
    const SCENARIO_REGISTER = 'register';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_LOGIN] = ['email', 'password'];
        $scenarios[self::SCENARIO_REGISTER] = ['name', 'surname', 'email', 'phone', 'password'];

        return $scenarios;
    }
    public $password_repetition;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'surname', 'email', 'phone', 'password', 'password_repetition'], 'required', 'on' => self::SCENARIO_REGISTER],
            [['email', 'password'], 'required', 'on' => self::SCENARIO_LOGIN],
            [['name', 'surname', 'email', 'password'], 'string', 'max' => 255],
            ['name', 'match', 'pattern' => '/^[а-яё\s\-]+$/iu', 'message'=>'Только кириллица, пробелы и дефисы'],
            ['surname', 'match', 'pattern' => '/^[а-яё\s\-]+$/iu', 'message'=>'Только кириллица, пробелы и дефисы'],
            ['email', 'email'],
            [['email'], 'unique','on' => self::SCENARIO_REGISTER, 'message' => 'Этот email уже используется'],
            [['phone'], 'match', 'pattern' => '/^\+?[0-9\-\s()]+$/', 'message' => 'Неверный формат номера телефона. Допустимы цифры, пробелы, "+", "-", "()"'],
            ['phone', 'unique', 'message' => 'Этот телефон уже используется'],
            ['password', 'string', 'min' => 8, 'message'=>'Минимум 8 символов'],
            ['password_repetition', 'compare', 'compareAttribute' => 'password', 'message'=>'Пароли должны совпадать'],
            [['id_role'], 'integer'],

            [['id_role'], 'exist', 'skipOnError' => true, 'targetClass' => Role::class, 'targetAttribute' => ['id_role' => 'id_role']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_user' => 'Id User',
            'name' => 'Имя',
            'surname' => 'Фамилия',
            'email' => 'Почта',
            'phone' => 'Телефон',
            'password' => 'Пароль',
            'id_role' => 'Id Role',
            'token' => 'Token',
        ];
    }
    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->password);
        }

        public static function getByToken() {
            return self::findOne(['token' => str_replace('Bearer ', '', Yii::$app->request->headers->get('Authorization'))]);
        }
    
        public function isAdmin() {
            $admin_role = Role::findOne(['name' => 'admin']);
            return $this->id_role === $admin_role['id_role'];
        }
    
        public function isAuthorized() {
            $token = str_replace('Bearer ', '', Yii::$app->request->headers->get('Authorization'));
            if (!$token || $token != $this->token) {
                return false;
            }
            return true;
        }
    
        public function getRole()
        {
            return $this->hasOne(Role::class, ['id_role' => 'id_role']);
        }
}
