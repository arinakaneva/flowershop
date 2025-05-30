<?php

namespace app\controllers;

use app\models\Category;
use app\models\Product;
use app\models\User;
use yii\rest\ActiveController;
use yii\web\Response;
use yii\web\UploadedFile;
use Yii;

class ProductController extends RestController
{
    public $modelClass = 'app\models\Product';
    public function actions()
    {
        return ['create'];
    }
    public function actionCreate() {
    $user = User::getByToken();
    if (!($user && $user->isAuthorized() && $user->isAdmin())) {
        return $this->Response(403, ['error' => ['message' => 'Доступ запрещен']]);
    }
    $data = Yii::$app->request->post();
    $product = new Product();
    $product->scenario = Product::SCENARIO_CREATE;
    $product->load($data, '');
    $photoqq = UploadedFile::getInstanceByName('photo');
    
    if (!is_null($photoqq)) {
        $product->photo = $photoqq;
        if ($this->ValidationError($product)) return $this->ValidationError($product);
        $path = Yii::$app->basePath. '/assets/upload/' . hash('sha256', $product->photo->baseName) . '.' . $product->photo->extension;
        $product->photo->saveAs($path);
        $product->photo = $path;
    } else {
        if ($this->ValidationError($product)) return $this->ValidationError($product);
    }
    
    $product->save();
    return $this->Response(201, [
        'id_product' => $product->id_product,
        'message' => 'Товар добавлен'
    ]);
}

public function actionUpgrade($id_product) {
    $user = User::getByToken();
    if (!($user && $user->isAuthorized() && $user->isAdmin())) {
        return $this->Response(403, ['error' => ['message' => 'Доступ запрещен']]);
    }
    $data = Yii::$app->request->post();
    $product = Product::findOne($id_product);
    if (!$product) {
        return $this->Response(204);
    }
    $product->scenario = Product::SCENARIO_UPDATE;
    $product->load($data, '');
    
    // Добавляем загрузку текущих значений категории и описания
    if (isset($data['category'])) {
        $product->id_category = $data['category'];
    }
    if (isset($data['description'])) {
        $product->description = $data['description'];
    }
    
    $photo = UploadedFile::getInstanceByName('photo');
    if ($this->ValidationError($product)) return $this->ValidationError($product);
    if (!is_null($photo)){
        $product->photo = $photo;
        $path = Yii::$app->basePath. '/assets/upload/' . hash('sha256', $product->photo->baseName) . '.' . $product->photo->extension;
        $product->photo->saveAs($path);
        $product->photo = $path;
    }
    
    $product->save();
    return $this->Response(200, ['data' => $product]);
}

// Добавляем новый метод для получения списка категорий
public function actionCategories() {
    $categories = Category::find()
        ->select(['id_category as id', 'name'])
        ->asArray()
        ->all();
    return $this->Response(200, ['data' => $categories]);
}

    public function actionProduct($id)
    {
            
            $product = product::findOne(['id_product' => Yii::$app->request->get('id')]);
            if($product){
                return $this->Response(200, ['data' => $product]);
            }
            else{
             return $this->Response(404, ['error' => ['message' => 'Товар не найден']]);
            }
    }
    public function actionAll()
{
    $products = Product::find()
        ->select([
            'id_product',
            'name',
            'photo',
            'price',
            'id_category', // нужно для связи
            'description',
        ])
        ->with('category') // жадная загрузка категории
        ->all();

    if ($products) {
        // Преобразуем данные, чтобы включить название категории
        $result = array_map(function ($product) {
            return [
                'id_product' => $product->id_product,
                'name' => $product->name,
                'id_category' => $product->id_category,
                'photo' => $product->photo,
                'price' => $product->price,
                'description' => $product->description,

            ];
        }, $products);

        return $this->Response(200, ['data' => $result]);
    }
    return $this->Response(204);
}


}
