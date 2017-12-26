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
use app\index\model\Order;
use app\index\model\OrderItem;
use \app\index\model\ProductContent;
use \app\index\model\ProductImg;
use \think\Controller;
use \think\Request;
use \app\index\model\Product;
use \think\Session;
use \app\index\service\WeiXin;
use \app\index\service\WeiXinJs;
use think\Config;
use app\index\model\Log;

class Productctl extends Controller
{
    protected $view;
    protected $openId;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->assign('_action', 'index');
        $openId = Session::get('openid');
        if (!$openId) {
            if ($request->isAjax()) {
                return self::response(400, '请刷新页面重新登录');
            } else {
                WeiXin::getOpenidAndAcessToken();
            }
        }
        $this->openId = $openId;
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
        if (empty($product) || $product->state != 1) {
            return self::response(400, '产品不存在');
        }
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
                $products = $product->where('id', 'exp', ' IN (' . implode(',', $ids) . ') ')->where(['state' => 1])->field('id,name,img,price,store')->select();
                if ($products) {
                    foreach ($products as $k=>&$product) {
                        if ($count[$product->id] <= $product->store){
                            $product->count = $count[$product->id];
                        }else{
                            unset($products[$k]);
                        }
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
            $accessToken = WeiXin::getAccessToken();
            $jsApiTicket = WeiXin::getJsApiTicket($accessToken);
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $jsApi = new WeiXinJs();
            $jsApi->appId = Config::get('weixin.APPID');
            $jsApi->nonceStr = WeiXin::getNonceStr();
            $jsApi->timestamp = time();
            $jsApi->signature = WeiXin::signature($jsApiTicket, $jsApi->nonceStr, $jsApi->timestamp, $url);
            $this->assign('jsApi', $jsApi);
            $this->assign('carts', $carts);
            $this->assign('referer', $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '/product/list');
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

    public function pay(Request $request)
    {
        if (!$request->isAjax()) {
            return self::response(400, '非法请求');
        }
        // {'name': name, 'phone': phone, '__token__': token,'address':address}
        $data = $request->post();
        if (empty($data)) {
            return self::response(400, '非法请求');
        }
        $carts = self::getCart();
        if (empty($carts)) {
            return self::response(400, '请先选择产品');
        }
        $result = $this->checkName($data['name']);
        if ($result) {
            return self::response(400, $result);
        }
        $result = $this->checkPhone($data['phone']);
        if ($result) {
            return self::response(400, $result);
        }
        $result = $this->checkAddress($data['address']);
        if ($result) {
            return self::response(400, $result);
        }

        $result = $this->validate(
            $data,
            [
                'ids' => 'require|token'
            ],
            [
                'ids.require' => '请先选择产品'
            ]
        );
        if (true !== $result) {
            // 验证失败 输出错误信息
            return self::response(400, $result, ['token' => $request->token()]);
        }

        // 先下单后减库存，30分钟失效
        // 总价
        $idArr = array_flip(array_flip(explode(',', $data['ids'])));
        $totalPrice = 0.00;
        foreach ($carts as $cart) {
            $totalPrice += sprintf("%.2f", $cart->count * $cart->price);
        }

        // 创建订单
        $orderObj = new Order();
        $orderObj->order_no = WeiXin::createOrderNo(WeiXin::ORDER_PRODUCT);
        $orderObj->user_id = WeiXin::getUserIdByOpenid($this->openId);
        $orderObj->open_id = $this->openId;
        $orderObj->total_price = $totalPrice;
        $orderObj->name = $data['name'];
        $orderObj->phone = $data['phone'];
        $orderObj->address = $data['address'];
        $orderObj->save();
        $orderId = $orderObj->id;

        // 减库存
        if (!$orderId) {
            return self::response(400, '订单发起失败', ['token' => $request->token()]);
        }
        $res = null;
        $productName = [];
        foreach ($carts as $_cart) {
            $productObj = new Product();
            $res = $productObj->save(['store' => ['exp', 'store-' . $_cart->count]],
                ['id' => $_cart->id, 'state' => 1]);
            if (!$res) {
                return self::response(400, '产品' . $_cart->name . '库存不足', ['token' => $request->token()]);
            }
            $productName[] = $_cart->name;
            // 创建order_item
            $orderItemObj = new OrderItem();
            $orderItemObj->order_no = $orderObj->order_no;
            $orderItemObj->pro_id = $_cart->id;
            $orderItemObj->count = $_cart->count;
            $orderItemObj->price = $_cart->price;
            $orderItemObj->save();
        }

        // 写入日志
        $proName = implode(',', $productName);
        $log = new Log();
        $log->order_no = $orderObj->order_no;
        $log->user_id = $orderObj->user_id;
        $log->open_id = $this->openId;
        $log->type = WeiXin::ORDER_PRODUCT;
        $log->content = '重庆老街产品:' . $proName . '发起支付';
        $log->price = $totalPrice;
        $log->save();
        // 返回支付接口参数
        $wxPayConfig = json_decode(WeiXin::weiXinPayData('重庆老街产品:' . $proName, $orderObj->order_no, $totalPrice * 100,
            $this->openId), true);
        $wxPayConfig['order_no'] = $orderObj->order_no;
        $wxPayConfig['token'] = $request->token();
        return self::response(0, '支付创建成功', $wxPayConfig);
    }

    public function fail(Request $request)
    {
        if (!$request->isAjax()) {
            return self::response(400, '非法请求');
        }
        $orderNo = $request->param('order_no');
        $msg = $request->param('msg');
        if (!$orderNo || !$msg) {
            return self::response(400, '非法请求');
        }
        // 根据订单 查询信息
        $orderObj = new Order();
        $order = $orderObj->getOneByOrder($orderNo);

        // 写入日志
        if ($order) {
            $log = new Log();
            $log->order_no = $orderNo;
            $log->user_id = $order->user_id;
            $log->open_id = $order->open_id;
            $log->type = WeiXin::ORDER_PRODUCT;
            if ($msg == 'cancel') {
                $log->content = '支付取消';
            } else {
                $log->content = '支付失败';
            }
            $log->price = $order->total_price;
            $log->save();
            return self::response(0);
        } else {
            return self::response(400, '非法请求');
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

    private function checkName($value)
    {
        $value = trim($value);
        if ($value == '' || mb_strlen($value) < 2 || mb_strlen($value) > 5) {
            return '姓名长度在2-5之间';
        }
    }

    private function checkPhone($value)
    {
        $value = trim($value);
        if ($value == '' || mb_strlen($value) !== 11 || intval($value) <= 0 || substr($value, 0, 1) != 1) {
            return '请输入正确的手机号';
        }
    }

    private function checkAddress($value)
    {
        $value = trim($value);
        if ($value == '' || mb_strlen($value) < 9 || mb_strlen($value) > 30) {
            return '请输入地址，且不小于9字';
        }
    }
}