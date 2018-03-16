<?php

namespace app\index\controller;

/**
 * 个人中心
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:53
 */
use app\index\model\ActRecords;
use app\index\model\CertRecords;
use app\index\model\DonateRecords;
use app\index\model\Inspect;
use app\index\model\Log;
use app\index\model\Order;
use app\index\model\OrderItem;
use app\index\model\User;
use app\index\service\WeiXin;
use app\index\service\WeiXinJs;
use think\Config;
use think\Request;

class Person extends Base
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->assign('_action', 'ucenter');
    }

    /**
     * 个人中心
     * @return mixed
     * @throws \Exception
     */
    public function index()
    {
        $prefix = config("database.prefix");
        // 用户信息
        $user = User::get(['openid' => $this->openId]);

        // 获取相关订单信息
        // 参与的活动
        $actRecordsObj = new ActRecords();
        $actList = $actRecordsObj->alias('a')->order('a.id desc')->join($prefix . 'act b', 'a.act_id = b.id',
            'LEFT')->field('a.is_paied,a.need_pay,b.id,b.name,b.img,b.des')->where(['a.open_id' => $this->openId])->select();
        // 产品订单
        $orderObj = new Order();
        $orderList = $orderObj->order('id DESC')->where(function ($query) {
            $query->where(['open_id'=>$this->openId,'is_paied' => 1]);
        })->whereOr(function ($query) {
            $query->where(['open_id'=>$this->openId,'is_paied' => 0,'is_update' => 0]);
        })->field('order_no,is_paied')->select();
        //$sql = "SELECT `order_no`,`is_paied` FROM `ly_order` WHERE  (`open_id` = '$this->openId'  AND `is_paied` = 1) OR (`open_id` = '$this->openId'  AND `is_paied` = 0  AND `is_update` = 0) ORDER BY id DESC";
        //$orderList = $orderObj->query($sql);
        $notPaied = false;
        if ($orderList) {
            foreach ($orderList as $order) {
                if ($order->is_paied == 0) {
                    $notPaied = true;
                }
                $orderItemObj = new OrderItem();
                $orderItems = $orderItemObj->alias('a')->order('a.id DESC')->join($prefix . 'product b',
                    'a.pro_id = b.id',
                    'LEFT')->field('a.count,a.price,a.pro_id,b.name,b.img')->where(['a.order_no' => $order['order_no']])->select();
                $order->orderItems = $orderItems;
            }
        }
        // 捐款
        $donateRecordsObj = new DonateRecords();
        $donateList = $donateRecordsObj->alias('a')->order('a.id desc')->join($prefix . 'donate b',
            'a.donate_id = b.id', 'LEFT')->field('a.money,a.create_time,b.name')->where([
            'a.is_paied' => 1,
            'a.open_id' => $this->openId
        ])->select();

        // 我的证书
        $certRecordsObj = new CertRecords();
        $certRecords = $certRecordsObj->alias('a')->order('a.id DESC')->join($prefix . 'member b', 'a.uid = b.uid',
            'LEFT')->join($prefix . 'cert c', 'a.cert_id = c.id',
            'LEFT')->field('a.id,a.cert_id,a.create_time,b.uid,b.name,c.img,c.num,c.des')->select();

        // 我的反馈
        $inspect_Client = new Inspect();
        $inspect = $inspect_Client->field('des')->where(['uid' => $this->uid, 'state' => 1])->select();

        $jsApi = new WeiXinJs();
        if ($notPaied) {
            $accessToken = WeiXin::getAccessToken();
            $jsApiTicket = WeiXin::getJsApiTicket($accessToken);
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $jsApi->appId = Config::get('weixin.APPID');
            $jsApi->nonceStr = WeiXin::getNonceStr();
            $jsApi->timestamp = time();
            $jsApi->signature = WeiXin::signature($jsApiTicket, $jsApi->nonceStr, $jsApi->timestamp, $url);
            $this->assign('jsApi', $jsApi);
        }

        $this->assign('user', $user);
        $this->assign('actList', $actList);
        $this->assign('orderList', $orderList);
        $this->assign('donateList', $donateList);
        $this->assign('inspect', $inspect);
        $this->assign('certRecords', $certRecords);
        $this->assign('title', '个人中心');
        return $this->fetch('ucenter/index');
    }

    /**
     * 订单支付
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\index\service\WxPayException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pay(Request $request)
    {
        if (!$request->isAjax()) {
            return self::response(400, '非法请求');
        }
        $data = $request->post('', null, 'htmlspecialchars');
        if (empty($data)) {
            return self::response(400, '非法请求');
        }

        $result = $this->validate(
            $data,
            [
                'order' => 'require|token'
            ],
            [
                'order.require' => '请选择订单'
            ]
        );
        if (true !== $result) {
            // 验证失败 输出错误信息
            return self::response(400, $result, ['token' => $request->token()]);
        }

        // 查询订单
        $orderObj = new Order();
        $order = $orderObj->where([
            'order_no' => $data['order'],
            'user_id' => $this->uid
        ])->field('order_no,is_paied,total_price,is_update,transaction_id,create_time')->find();

        if (empty($order)){
            return self::response(400, '订单无效', ['token' => $request->token()]);

        }
        if ($order->is_paied || $order->is_update || $order->transaction_id || $order->create_time < date('Y-m-d H:i:s', (time() - 1800))){
            return self::response(400, '订单无效', ['token' => $request->token()]);
        }

        // 写入日志
        $log = new Log();
        $log->order_no = $order->order_no;
        $log->user_id = $this->uid;
        $log->open_id = $this->openId;
        $log->type = WeiXin::ORDER_PRODUCT;
        $log->content = '重庆老街订单:' . $order->order_no . '重新发起支付';
        $log->price = $order->total_price;
        $log->save();
        // 返回支付接口参数
        $wxPayConfig = json_decode(WeiXin::weiXinPayData('重庆老街订单:' . $order->order_no.'支付', $order->order_no, $order->total_price * 100,
            $this->openId), true);
        $wxPayConfig['order_no'] = $order->order_no;
        $wxPayConfig['token'] = $request->token();
        return self::response(0, '支付创建成功', $wxPayConfig);
    }

    /**
     * 异步返回
     * @param $code
     * @param string $msg
     * @param array $data
     * @return \think\response\Json
     */
    private static function response($code, $msg = '', $data = [])
    {
        $response = new \stdClass();
        $response->code = $code;
        $response->data = $data;
        $response->msg = $msg;
        return json($response);
    }
}