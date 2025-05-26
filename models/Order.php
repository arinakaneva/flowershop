<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "order".
 *
 * @property int $id_order
 * @property int $id_user
 * @property string $status
 * @property string $created
 * @property string $name
 * @property string $phone
 * @property string $address
 * @property string $pay
 * @property string $comment
 * @property int $id_cart
 *
 * @property Cart $cart
 * @property User $user
 */
class Order extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_user', 'status', 'name', 'phone', 'address', 'pay', 'comment', 'id_cart'], 'required'],
            [['id_user', 'id_cart'], 'integer'],
            [['status', 'pay'], 'string'],
            [['created'], 'safe'],
            [['name', 'address', 'comment'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 40],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['id_user' => 'id_user']],
            [['id_cart'], 'exist', 'skipOnError' => true, 'targetClass' => Cart::class, 'targetAttribute' => ['id_cart' => 'id_cart']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_order' => 'Id Order',
            'id_user' => 'Id User',
            'status' => 'Status',
            'created' => 'Created',
            'name' => 'Name',
            'phone' => 'Phone',
            'address' => 'Address',
            'pay' => 'Pay',
            'comment' => 'Comment',
            'id_cart' => 'Id cart',
        ];
    }

    /**
     * Gets query for [[cart]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getcart()
    {
        return $this->hasOne(Cart::class, ['id_cart' => 'id_cart']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id_user' => 'id_user']);
    }
}
