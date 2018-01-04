<?php

namespace app\index\controller;

use app\index\model\DonateRecords;
use \think\Controller;
use think\Request;
use think\View;
use app\index\model\Donate;
use \app\index\service\WeiXin;
use \think\Session;
use \app\index\service\WeiXinJs;
use think\Config;
use app\index\model\Log;

class Donatectl extends Controller
{
    protected $openId;
    protected $view;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
        $this->assign('_action','index');
        $openId = Session::get('openid');
        if (!$openId) {
            if ($request->isAjax()) {
                return self::response(400, '请刷新页面重新登录');
            } else {
                $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                WeiXin::getOpenidAndAcessToken($url);
            }
        }
        $this->openId = $openId;
    }

    /**
     * 捐款列表
     * @param Request $request
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function donateList(Request $request)
    {
        $donate = new Donate();
        $list = $donate->where(['state' => 1])->order('id desc')->paginate(6);
        $items = $list->items();
        $response = new \stdClass();
        if ($request->isAjax()) {
            $response->code = 400;
            $response->msg = '';
            $response->data = array();
            if (!empty($items)) {
                $response->code = 0;
                $response->data = $items;
            }
            return json($response);
        }
        $this->assign('curPage', 1);
        $this->assign('list', $items);
        $this->assign('title', '捐款活动');
        return $this->view->fetch('donate/list');
    }

    /**
     * 捐款详情
     * @param $id
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function donateDetail($id)
    {
        $id = intval($id);
        if (!$id) {
            $this->redirect('/');
        }
        $detail = Donate::get(['id' => $id, 'state' => 1]);
        if (empty($detail)){
            $this->redirect('/');
        }
        $this->assign('detail',$detail);
        $this->assign('title',$detail->name);
        return $this->view->fetch('donate/detail');
    }

    /**
     * 捐款详情页
     * @param $id
     * @return string
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function donateDo($id)
    {
        $id = intval($id);
        if (!$id) {
            $this->redirect('/');
        }
        $donate = new Donate();
        $detail = $donate->where(['id' => $id, 'state' => 1])->field('id,name,des,img')->find();
        if (empty($detail)){
            $this->redirect('/');
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
        $this->assign('detail',$detail);
        $this->assign('title',$detail->name);
        $this->assign('referer', isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'/donate/list');
        return $this->view->fetch('donate/pay');
    }

    /**
     * 保存捐款并支付
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\index\service\WxPayException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function donateSave(Request $request)
    {
        if (!$request->isAjax()) {
            return self::response(400, '非法请求');
        }
        $data = $request->post();
        $id = intval($data['id']);
        $amount = intval($data['amount']);
        if (empty($data) || $id <= 0 ||  $amount <= 0) {
            return self::response(400, '非法请求');
        }
        // 查询捐款
        $donate = new Donate();
        $detail = $donate->where(['id' => $id, 'state' => 1])->field('id,name,des,img')->find();
        if (empty($detail)){
            return self::response(400, '非法请求');
        }

        // 创建捐款订单
        $donateRecords = new DonateRecords();
        $donateRecords->donate_id = $id;
        $donateRecords->user_id = WeiXin::getUserIdByOpenid($this->openId);;
        $donateRecords->open_id = $this->openId;
        $donateRecords->money = $amount;
        $donateRecords->order_no = WeiXin::createOrderNo(WeiXin::ORDER_DONATE);
        $donateRecords->save();
        $donateRecordsId = $donateRecords->id;
        if (!$donateRecordsId) {
            return self::response(400, '捐款失败');
        }
        // 写入日志
        $log = new Log();
        $log->order_no = $donateRecords->order_no;
        $log->user_id = $donateRecords->user_id;
        $log->open_id = $this->openId;
        $log->type = WeiXin::ORDER_DONATE;
        $log->content = $detail['name'] . ':捐款发起支付';
        $log->price = $amount;
        $log->save();
        // 返回支付接口参数
        $wxPayConfig = json_decode(WeiXin::weiXinPayData('重庆老街捐款:' . $detail['name'], $donateRecords->order_no, $amount * 100, $this->openId), true);
        $wxPayConfig['order_no'] = $donateRecords->order_no;
        return self::response(0, '支付创建成功', $wxPayConfig);
    }

    /**
     * 捐款失败和取消处理
     * @param Request $request
     * @return \think\response\Json
     */
    public function donateFail(Request $request)
    {
        if (!$request->isAjax()){
            return self::response(400, '非法请求');
        }
        $orderNo = $request->param('order_no',null,'htmlspecialchars');
        $msg = $request->param('msg',null,'htmlspecialchars');
        if (!$orderNo || !$msg){
            return self::response(400, '非法请求');
        }
        // 根据订单 查询信息
        $donateRecordsObj = new DonateRecords();
        $donateRecords = $donateRecordsObj->getOneByOrder($orderNo);

        // 写入日志
        if ($donateRecords){
            $log = new Log();
            $log->order_no = $orderNo;
            $log->user_id = $donateRecords->user_id;
            $log->open_id = $donateRecords->open_id;
            $log->type = WeiXin::ORDER_DONATE;
            if ($msg == 'cancel'){
                $log->content = '支付取消';
            }else{
                $log->content = '支付失败';
            }
            $log->price = $donateRecords->money;
            $log->save();
            return self::response(0);
        }else{
            return self::response(400, '非法请求');
        }
    }

    /**
     * 异步处理
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
