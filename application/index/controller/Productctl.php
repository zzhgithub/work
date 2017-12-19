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
    }

    public function productList(Request $request)
    {
        $product = new Product();
        $list = $product->where(['state' => 1])->order('id desc')->field('id,name,img,price,store')->paginate(4);
        $items = $list->items();
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

    public function productModCart(Request $request)
    {
        $id = (int)$request->param('id');
        $num = (int)$request->param('num');
        if (!$id || $num < 0 || !$request->isAjax()) {
            return self::response(400, '非法请求');
        }
        $cart = Session::get('cart');
        $cartArr = $cart != null ? explode(',', $cart) : [];
        $ids = [];
        if (!empty($cartArr)) {
            foreach ($cartArr as $k => &$value) {
                $index = strpos($value, ':');
                if (substr($value, 0, $index) == $id) {
                    if ($num === 0) {
                        unset($cartArr[$k]);
                    } else {
                        $value = $id . ':' . $num;
                    }
                    break;
                }
            }
            if ($cartArr) {
                foreach ($cartArr as $value) {
                    $index = strpos($value, ':');
                    $ids[] = substr($value, 0, $index);
                }
            }
        }

        if (!in_array($id, $ids) && $num > 0) {
            array_push($cartArr, $id . ':' . $num);
        }
        Session::set('cart', implode(',', $cartArr));
        return self::response(0, 'success');
    }

    private static function getCart($id = 0)
    {
        $cart = Session::get('cart');
        $cartArr = $cart != null ? explode(',', $cart) : [];
        if (!$id) {
            return $cartArr;
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

    private static function response($code, $msg = '', $data = [])
    {
        $response = new \stdClass();
        $response->code = $code;
        $response->data = $data;
        $response->msg = $msg;
        return json($response);
    }
}

 