<?php

namespace app\index\controller;

/**
 * ,__,
 * (oo)_____
 * (__)     )\
 * ````||---|| *
 * @author mutou <br>
 * @version 1.0.0
 * @description product <br>
 * @date 2017/12/18 <br>
 */
use \app\index\model\ProductContent;
use \app\index\model\ProductImg;
use \think\Controller;
use \think\Request;
use \app\index\model\Product;
use \think\Session;

class Productctl extends Controller
{
    protected $view;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->assign('_action','index');
    }

    public function productList(Request $request)
    {
        $product = new Product();
        $list = $product->where(['state' => 1])->order('id desc')->field('id,name,img,price,store')->paginate(4);
        $items = $list->items();
        $cart = Session::get('cart');
        $cartArr = $cart != null ? explode(',', $cart) : [];
        $count = [];
        if ($cartArr) {
            foreach ($cartArr as $cart) {
                $index = strpos($cart, ':');
                $count[substr($cart, 0, $index)] = intval(substr($cart, $index + 1));
            }
        }
        if ($items) {
            foreach ($items as &$item) {
                $item->count = isset($count[$item->id]) ? $count[$item->id] : 0;
            }
        }

        if ($request->isAjax()) {
            if (!empty($items)) {
                return self::response(0, 'success', $items);
            }
            return self::response(400);
        }
        $this->assign('curPage', 1);
        $this->assign('list', $items);
        return $this->fetch('product/list');
    }

    public function productDetail($id)
    {
        $id = intval($id);
        if (!$id) {
            $this->redirect('/');
        }
        $product = Product::get(['id' => $id, 'state' => 1]);
        if (empty($product)) {
            $this->redirect('/');
        }
        // 购物车信息
        $cartCount = self::getCart($id);
        $this->assign('cartCount', $cartCount);
        // 产品信息
        $productCon = new ProductContent();
        $productContent = $productCon->where(['id' => $id])->field('id,content')->find();
        $productImg = new ProductImg();
        $productImgs = $productImg->where(['pro_id' => $id])->field('id,img_path')->select();
        $this->assign('product', $product);
        $this->assign('productContent', $productContent);
        $this->assign('productImgs', $productImgs);
        return $this->fetch('product/detail');
    }

    /**
     * 加入购物车
     * @param Request $request
     * @return \think\response\Json
     */
    public function productModCart(Request $request)
    {
        $id = (int)$request->param('id');
        $num = (int)$request->param('num');
        if (!$id || $num < 0 || !$request->isAjax()) {
            return self::response(400, '非法请求');
        }
        $cart = Session::get('cart');
        $cartArr = $cart != null ? explode(',', $cart) : [];
        $product = Product::get($id);
        $ids = [];
        if (!empty($cartArr)) {
            foreach ($cartArr as $k => &$value) {
                $index = strpos($value, ':');
                if (substr($value, 0, $index) == $id && $product->state == 1 && $product->store >= $num) {
                    if ($num === 0) {
                        unset($cartArr[$k]);
                    } else {
                        $value = $id . ':' . $num;
                    }
                    break;
                }
            }
            if ($cartArr) {
                foreach ($cartArr as $v) {
                    $index = strpos($v, ':');
                    $ids[] = substr($v, 0, $index);
                }
            }
        }
        if (!in_array($id, $ids) && $num > 0 && $product->state == 1 && $product->store >= $num) {
            array_push($cartArr, $id . ':' . $num);
        }
        Session::set('cart', implode(',', $cartArr));
        return self::response(0, 'success');
    }

    /**
     * 获取购物车信息
     * @param int $id
     * @return array|int
     */
    private static function getCart($id = 0)
    {
        $cart = Session::get('cart');
        $cartArr = $cart != null ? explode(',', $cart) : [];
        if (!$id) {
            if ($cartArr) {
                $ids = $count = [];
                foreach ($cartArr as $cart) {
                    $index = strpos($cart, ':');
                    $_id = substr($cart, 0, $index);
                    $ids[] = $_id;
                    $count[$_id] = intval(substr($cart, $index + 1));
                }

                if (empty($ids)) {
                    return [];
                }
                // 查询产品信息
                $product = new Product();
                $products = $product->where('id', 'exp', ' IN (' . implode(',',
                        $ids) . ') ')->where(['state' => 1])->field('id,name,img,price,store')->select();
                if ($products) {
                    foreach ($products as &$product) {
                        $product->count = $count[$product->id];
                    }
                    return $products;
                } else {
                    return [];
                }
            } else {
                return $cartArr;
            }
        }
        if (empty($cartArr)) {
            return 0;
        }
        $count = 0;
        foreach ($cartArr as $value) {
            $index = strpos($value, ':');
            if (substr($value, 0, $index) == $id) {
                $count = intval(substr($value, $index + 1));
                break;
            }
        }
        return $count;
    }

    public function productCart(Request $request)
    {
        $carts = self::getCart();
        if ($request->isGet()) {
            $totalPrice = 0.00;
            if ($carts) {
                foreach ($carts as $cart) {
                    $totalPrice += sprintf("%.2f", $cart->count * $cart->price);
                }
            }
            $this->assign('carts', $carts);
            $this->assign('totalPrice', sprintf("%.2f", $totalPrice));
            return $this->fetch('product/pay');
        }
        // 异步请求
        if ($carts) {
            return self::response(0, 'success', $carts);
        } else {
            return self::response(400, 'no data');
        }
    }

    private static function response($code, $msg = '', $data = [])
    {
        $response = new \stdClass();
        $response->code = $code;
        $response->data = $data;
        $response->msg = $msg;
        return json($response);
    }
}

 