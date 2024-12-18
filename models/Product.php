<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property int $id_product
 * @property string $name
 * @property int $id_category
 * @property string $description
 * @property string $photo
 * @property float $price
 *
 * @property Category $category
 */
class Product extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }
    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';
    
    public function scenarios()
    {
    $scenarios = parent::scenarios();
    $scenarios[self::SCENARIO_CREATE] = ['name', 'id_category', 'description',  'photo', 'price'];
    $scenarios[self::SCENARIO_UPDATE] = ['name' ,'id_category', 'description',  'photo', 'price'];
    return $scenarios;
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'id_category', 'description', 'price'], 'required','on' => self::SCENARIO_CREATE],
            [['name'], 'string'],
            [['id_category'], 'integer'],
            [['price'], 'number'],
            [['description'], 'string', 'max' => 255],
            [['id_category'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['id_category' => 'id_category']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_product' => 'Id Product',
            'name' => 'Name',
            'id_category' => 'Id Category',
            'description' => 'Description',
            'photo' => 'Photo',
            'price' => 'Price',
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id_category' => 'id_category']);
    }
}
