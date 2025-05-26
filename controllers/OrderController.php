<?php

namespace app\controllers;

use app\models\Order;
use app\models\Cart;
use app\models\Product;
use app\models\CartItem;
use yii\rest\ActiveController;
use yii\web\Response;
use app\models\User;
use Yii;

class OrderController extends RestController
{
    public $modelClass = 'app\models\Order';
    public function actions()
    {
        return ['create'];
    }

    public function actionAll()
    {
        if (product::find()) {
            return $this->Response(200, ['data' => Product::find()->select(['id_product', 'name', 'photo', 'price'])->all()]);
        }
        return $this->Response(204);
    }

    public function actionCreate()
    {
        $user = User::getByToken();
        if (!($user && $user->isAuthorized())) {
            return $this->Response(401, ['error' => ['message' => 'Вы не авторизованы']]);
        }

        // Получаем данные из запроса
        $request = Yii::$app->request;
        $data = $request->post();

        // Проверяем обязательные поля
        $requiredFields = ['name', 'phone', 'address', 'pay', 'comment', 'id_cart'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->Response(400, ['error' => ['message' => "Поле $field обязательно для заполнения"]]);
            }
        }

        // Проверяем существование корзины
        $cart = Cart::findOne($data['id_cart']);
        if (!$cart) {
            return $this->Response(404, ['error' => ['message' => 'Корзина не найдена']]);
        }

        // Проверяем, что корзина принадлежит пользователю
        if ($cart->id_user != $user->id_user) {
            return $this->Response(403, ['error' => ['message' => 'Эта корзина принадлежит другому пользователю']]);
        }

        // Проверяем, что в корзине есть товары
        $cartItems = CartItem::find()->where(['id_cart' => $data['id_cart']])->all();
        if (empty($cartItems)) {
            return $this->Response(400, ['error' => ['message' => 'Нельзя создать заказ с пустой корзиной']]);
        }

        // Начинаем транзакцию
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Создаем новый заказ
            $order = new Order();
            $order->id_user = $user->id_user;
            $order->id_cart = $data['id_cart'];
            $order->name = $data['name'];
            $order->phone = $data['phone'];
            $order->address = $data['address'];
            $order->pay = $data['pay'];
            $order->comment = $data['comment'];
            $order->status = 'В работе'; // Статус по умолчанию
            $order->created = date('Y-m-d H:i:s'); // Текущая дата и время

            if (!$order->save()) {
                throw new \Exception('Ошибка при сохранении заказа');
            }

            // Деактивируем все старые корзины пользователя
            Cart::updateAll(['is_active' => 0], ['id_user' => $user->id_user]);

            // Создаем новую активную корзину
            $newCart = new Cart();
            $newCart->id_user = $user->id_user;
            $newCart->created = date('Y-m-d H:i:s');
            $newCart->is_active = 1; // Делаем новую корзину активной
            if (!$newCart->save()) {
                throw new \Exception('Ошибка при создании новой корзины');
            }

            // Фиксируем транзакцию
            $transaction->commit();

            return $this->Response(201, [
                'message' => 'Заказ успешно создан, новая корзина создана',
                'data' => [
                    'id_order' => $order->id_order,
                    'status' => $order->status,
                    'created' => $order->created,
                    'new_cart_id' => $newCart->id_cart
                ]
            ]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->Response(500, [
                'error' => [
                    'message' => 'Ошибка при создании заказа',
                    'details' => $e->getMessage(),
                    'order_errors' => isset($order) ? $order->getErrors() : null,
                    'cart_errors' => isset($newCart) ? $newCart->getErrors() : null
                ]
            ]);
        }
    }

    public function actionUser()
    {
        $user = User::getByToken();
        if (!($user && $user->isAuthorized())) {
            return $this->Response(401, ['error' => ['message' => 'Вы не авторизованы']]);
        }
        $query = order::find()->where(['id_user' => $user->id_user]);
        if ($query->count() == 0) {
            return $this->Response(204);
        }
        $orders = $query->with(['cart', 'cart.cartItems', 'cart.cartItems.product'])->all();
        $groupedOrders = [];
        $statuses = [
            'В работе' => 'on_moderation',
            'Завершён' => 'completed',
            'Отменен' => 'cancelled'
        ];
        foreach ($statuses as $status) {
            $groupedOrders[$status] = [];
        }
        foreach ($orders as $order) {
            $items = [];
            if ($order->cart && $order->cart->cartItems) {
                foreach ($order->cart->cartItems as $item) {
                    $product = $item->product; // Получаем связанный продукт
                    $items[] = [
                        'id_cart_item' => $item->id_cart_item,
                        'id_product' => $item->id_product,
                        'price' => $item->price,
                        'image' => $product ? $product->photo : null, // Исправлено: теперь это 'image'
                        'name' => $product ? $product->name : null,    // Добавляем название продукта
                    ];
                }
            }
            $groupedOrders[$statuses[$order->status]][] = [
                'info' => $order,
                'items' => $items
            ];
        }
        return $this->Response(200, ['data' => $groupedOrders]);
    }

   
    public function actionItem(){
        $user = User::getByToken();
        if (!($user && $user->isAuthorized())) {
            return $this->Response(401, ['error' => ['message' => 'Вы не авторизованы']]);
        }
        $cart = Cart::find()->where(['id_user' => $user->id_user, 'is_active' => 1])->one();
        if (!$cart) {
            return $this->Response(400, ['error' => ['message' => 'Нет товаров в корзине']]);
        }
        $cart_items = CartItem::find()->where(['id_cart' => $cart->id_cart]);
        if ($cart_items->count() == 0) {
            return $this->Response(400, ['error' => ['message' => 'Нет товаров в корзине']]);
        }
        $items = [];
        foreach ($cart_items->asArray()->all() as $item) {
            // Получаем информацию о продукте, включая фото и название
            $product = Product::find()->where(['id_product' => $item['id_product']])->select(['photo', 'name'])->one();
            
            if (isset($items[$item['id_product']])) {
                $items[$item['id_product']]['quantity']++;
            } else {
                $items[$item['id_product']] = $item;
                $items[$item['id_product']]['quantity'] = 1;
                // Добавляем фото и название продукта в данные
                $items[$item['id_product']]['photo'] = $product ? $product->photo : null;
                $items[$item['id_product']]['name'] = $product ? $product->name : null;
            }
            $items[$item['id_product']]['total_price'] = $item['price'] * $items[$item['id_product']]['quantity'];
        }
    
        return $this->Response(200, ['data' => array_values($items)]);
    }
    
    public function actionAddCart()
    {
        $user = User::getByToken();
        if (!($user && $user->isAuthorized())) {
            return $this->Response(401, ['error' => ['message' => 'Вы не авторизованы']]);
        }
        
        // Находим активную корзину или создаем новую
        $cart = Cart::find()->where(['id_user' => $user->id_user, 'is_active' => 1])->one();
        
        if (!$cart) {
            $cart = new Cart();
            $cart->id_user = $user->id_user;
            $cart->is_active = 1; // Убедимся, что новая корзина активна
            $cart->created = date('Y-m-d H:i:s');
            if (!$cart->save()) {
                return $this->Response(500, ['error' => ['message' => 'Ошибка при создании корзины']]);
            }
        }
        
        $id_product = Yii::$app->request->post('id_product');
        $product = Product::findOne($id_product);
        
        if (!$product) {
            return $this->Response(404, ['error' => ['message' => 'Товар не найден']]);
        }
        
        $cartItem = new CartItem();
        $cartItem->id_cart = $cart->id_cart;
        $cartItem->price = $product->price;
        $cartItem->id_product = $id_product;
        
        if (!$cartItem->save()) {
            return $this->Response(500, ['error' => ['message' => 'Ошибка при добавлении товара в корзину']]);
        }
        
        return $this->Response(201, [
            'message' => 'Товар добавлен в корзину',
            'data' => [
                'cart_id' => $cart->id_cart,
                'product_id' => $id_product
            ]
        ]);
    }

    public function actionOnedel($id_product)
    {
        $user = User::getByToken();
        if (!($user && $user->isAuthorized())) {
            return $this->Response(401, ['error' => ['message' => 'Вы не авторизованы']]);
        }
        $cart = Cart::find()->where(['id_user' => $user->id_user, 'is_active' => 1])->one();
        if (!$cart) {
            return $this->Response(400, ['error' => ['message' => 'Нет товаров в корзине']]);
        }
        $cart_item = CartItem::find()->where(['id_cart' => $cart->id_cart, 'id_product' => $id_product])->one();
        if (!$cart_item) {
            return $this->Response(400, ['error' => ['message' => 'Товар не найден в корзине']]);
        }
        $cart_item->delete();
        return $this->Response(204);
    }
    public function actionStatus($id_order)
{
    $user = User::getByToken();
    if (!($user && $user->isAuthorized() && $user->isAdmin())) {
        return $this->Response(403, ['error' => ['message' => 'Доступ запрещен']]);
    }

    $order = Order::findOne($id_order);
    if (!$order) {
        return $this->Response(404, ['error' => ['message' => 'Заказ не найден']]);
    }

    $data = Yii::$app->request->post();
    if (empty($data['status'])) {
        return $this->Response(400, ['error' => ['message' => 'Не указан новый статус']]);
    }

    // Проверяем, что статус допустимый
    $allowedStatuses = ['В работе', 'Завершён', 'Отменен'];
    if (!in_array($data['status'], $allowedStatuses)) {
        return $this->Response(400, ['error' => ['message' => 'Недопустимый статус']]);
    }

    $order->status = $data['status'];
    
    if (!$order->save()) {
        return $this->Response(500, [
            'error' => [
                'message' => 'Ошибка при обновлении статуса',
                'details' => $order->getErrors()
            ]
        ]);
    }

    return $this->Response(200, [
        'message' => 'Статус заказа успешно обновлен',
        'data' => [
            'id_order' => $order->id_order,
            'new_status' => $order->status
        ]
    ]);
}
public function actionAdmin()
{
    $user = User::getByToken();
    if (!($user && $user->isAuthorized() && $user->isAdmin())) {
        return $this->Response(403, ['error' => ['message' => 'Доступ запрещен']]);
    }

    $query = Order::find()->with(['user', 'cart', 'cart.cartItems', 'cart.cartItems.product']);
    
    // Получаем параметры фильтрации из запроса
    $request = Yii::$app->request;
    $status = $request->get('status');
    $dateFrom = $request->get('date_from');
    $dateTo = $request->get('date_to');
    
    // Применяем фильтры, если они заданы
    if ($status) {
        $query->andWhere(['status' => $status]);
    }
    if ($dateFrom) {
        $query->andWhere(['>=', 'created', $dateFrom]);
    }
    if ($dateTo) {
        $query->andWhere(['<=', 'created', $dateTo]);
    }
    
    // Сортировка по дате создания (новые сначала)
    $query->orderBy(['created' => SORT_DESC]);
    
    $orders = $query->all();
    
    if (empty($orders)) {
        return $this->Response(204);
    }
    
    $result = [];
    foreach ($orders as $order) {
        $items = [];
        if ($order->cart && $order->cart->cartItems) {
            foreach ($order->cart->cartItems as $item) {
                $product = $item->product;
                $items[] = [
                    'id_product' => $item->id_product,
                    'name' => $product ? $product->name : null,
                    'price' => $item->price,
                    'quantity' => 1, // Можно добавить подсчет количества, если нужно
                    'image' => $product ? $product->photo : null
                ];
            }
        }
        
        $result[] = [
            'id_order' => $order->id_order,
            'user' => [
                'id_user' => $order->user ? $order->user->id_user : null,
                'name' => $order->user ? $order->user->name : null,
                'email' => $order->user ? $order->user->email : null
            ],
            'status' => $order->status,
            'created' => $order->created,
            'name' => $order->name,
            'phone' => $order->phone,
            'address' => $order->address,
            'pay' => $order->pay,
            'comment' => $order->comment,
            'items' => $items,
            'total' => array_reduce($items, function($sum, $item) {
                return $sum + $item['price'];
            }, 0)
        ];
    }
    
    return $this->Response(200, ['data' => $result]);
}
} 