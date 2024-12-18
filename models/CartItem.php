<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cart_item".
 *
 * @property int $id_cart_item
 * @property int $id_cart
 * @property int $id_product
 * @property int $price
 * @property string $created
 *
 * @property Cart $cart
 * @property Product $product
 */
class CartItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cart_item';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_cart', 'id_product', 'price'], 'required'],
            [['id_cart', 'id_product', 'price'], 'integer'],
            [['created'], 'safe'],
            [['id_cart'], 'exist', 'skipOnError' => true, 'targetClass' => Cart::class, 'targetAttribute' => ['id_cart' => 'id_cart']],
            [['id_product'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['id_product' => 'id_product']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_cart_item' => 'Id Cart Item',
            'id_cart' => 'Id Cart',
            'id_product' => 'Id Product',
            'price' => 'Price',
            'created' => 'Created',
        ];
    }

    /**
     * Gets query for [[Cart]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCart()
    {
        return $this->hasOne(Cart::class, ['id_cart' => 'id_cart']);
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id_product' => 'id_product']);
    }
}
