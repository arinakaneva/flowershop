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
    return $this->Response(200, ['data' => Product::find()->select(['id_product','name', 'photo', 'price'])->all()]);
    }
    return $this->Response(204);
    }


    public function actionUser() {
        $user = User::getByToken();
        if (!($user && $user->isAuthorized())) {
        return $this->Response(401, ['error' => ['message' => 'Вы не авторизованы']]);
        }
        $query = order::find()->where(['id_user' => $user->id_user]);
        if ($query->count() == 0) {
        return $this->Response(204);
        }
        $orders = $query->with(['cart', 'cart.cartItems'])->all();
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
        $items[] = $item;
        }
        }
        $groupedOrders[$statuses[$order->status]][] = [
        'info' => $order,
        'items' => $items
        ];
        }
        return $this->Response(200, ['data' => $groupedOrders]);
        }

        public function actionItem() {
                $user = User::getByToken();
                if (!($user && $user->isAuthorized())) {
                return $this->Response(401, ['error' => ['message' => 'Вы не авторизованы']]);
                }
                $cart = Cart::find()->where(['id_user' => $user->id_user])->one();
                if (!$cart) {
                return $this->Response(400, ['error' => ['message' => 'Нет товаров в корзине']]);
                }
                $cart_items = CartItem::find()->where(['id_cart' => $cart->id_cart]);
                if ($cart_items->count() == 0) {
                return $this->Response(400, ['error' => ['message' => 'Нет товаров в корзине']]);
                }
                $items = [];
                foreach ($cart_items->asArray()->all() as $item) {
                if (isset($items[$item['id_product']])) {
                $items[$item['id_product']]['quantity']++;
                }
                else {
                $items[$item['id_product']] = $item;
                $items[$item['id_product']]['quantity'] = 1;
                }
                $items[$item['id_product']]['total_price'] = $item['price'] * $items[$item['id_product']]['quantity'];
                }
                
                return $this->Response(200, ['data' => array_values($items)]);
                
        }
        public function actionAddCart() {
            $user = User::getByToken();
            if (!($user && $user->isAuthorized())) {
            return $this->Response(401, ['error' => ['message' => 'Вы не авторизованы']]);
            }
            $cart = Cart::find()->where(['id_user' => $user->id_user])->one();
            if (!$cart) {
            $cart = new Cart();
            $cart->id_user = $user->id_user;
            $cart->save();
            }
            $id_product = Yii::$app->request->post('id_product');
            $cartItem = new CartItem();
            $cartItem->id_cart = $cart->id_cart;
            $cartItem->price = Product::find()->where(['id_product' => $id_product])->one()->price;
            $cartItem->id_product = $id_product;
            $cartItem->save();
            return $this->Response(201, [
            'message' => 'Товар добавлен в корзину'
            ]);
            }
            public function actionOnedel($id_product) {
                $user = User::getByToken();
                if (!($user && $user->isAuthorized())) {
                return $this->Response(401, ['error' => ['message' => 'Вы не авторизованы']]);
                }
                $cart = Cart::find()->where(['id_user' => $user->id_user])->one();
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
}
