<?php

namespace app\index\controller;

/**
 * 活动于文创相关
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:36
 */
use app\index\model\Act;
use app\index\model\ActRecords;
use app\index\model\Log;
use app\index\service\WeiXinJs;
use think\Config;
use think\Controller;
use think\Exception;
use think\Request;
use think\View;
use \app\index\service\WeiXin;
use \think\Session;

class Activity extends Controller
{
    protected $view;
    protected $openId;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
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

    /**
     * 活动列表页
     * @param Request $request
     * @return string|\think\response\Json
     */
    public function activityList(Request $request)
    {
        try {
            $client = new Act();
            $list = $client->order('sort ASC,id DESC')->paginate(5);
            $items = $list->items();
            if ($request->isAjax()) {
                if (!empty($items)) {
                    return self::response(0,'success',$items);
                }
                return self::response(400);
            }
            $this->assign('curPage', 1);
            $this->assign('list', $list);
            $this->assign('title', '老街活动');
            return $this->view->fetch('act/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 活动详情页
     * @param $id
     * @return string|void
     */
    public function activityDetail($id)
    {
        if (!$id) {
            $this->redirect('/');
        }
        try {
            $data = Act::get($id);
            $this->assign('data', $data);
            $this->assign('title', $data->name);
            return $this->view->fetch('act/detail');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 活动报名页
     * @param $id
     * @return string|void
     * @throws \Exception
     */
    public function joinActivity($id)
    {
        if (!$id) {
            $this->redirect('/');
        }
        try {
            $data = Act::get($id);
            if (empty($data)){
                $this->redirect('/');
            }
            // 查询是否已报名
            $actRecordsObj = new ActRecords();
            if ($data['isfree']){   // 公益活动
                $actRecords = $actRecordsObj->where(['open_id' => $this->openId,'act_id' => $data['id']])->find();
            }else{                  // 付费活动
                $actRecords = $actRecordsObj->where(['open_id' => $this->openId,'act_id' => $data['id'],'is_paied' => 1])->find();
            }
            if ($actRecords){
                echo $this->fail('抱歉,你已经报过名了~','/act/detail/'.$id,5,3);
                exit;
            }
            // 返回微信参数
            if ($data->isfree != 1) {
                $accessToken = WeiXin::getAccessToken();
                $jsApiTicket = WeiXin::getJsApiTicket($accessToken);
                $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $jsApi = new WeiXinJs();
                $jsApi->appId = Config::get('weixin.APPID');
                $jsApi->nonceStr = WeiXin::getNonceStr();
                $jsApi->timestamp = time();
                $jsApi->signature = WeiXin::signature($jsApiTicket, $jsApi->nonceStr, $jsApi->timestamp, $url);
                $this->assign('jsApi', $jsApi);
            }
            $this->assign('referer', isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'/act/list');
            $this->assign('data', $data);
            $this->assign('title', $data->name);
            return $this->view->fetch('act/join');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 报名页表单处理
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\index\service\WxPayException
     */
    public function doJoin(Request $request)
    {
        if (!$request->isAjax()) {
            return self::response(400, '非法请求');
        }
        $data = $request->post();
        if (empty($data)) {
            return self::response(400, '非法请求');
        }
        $result = $this->checkName($data['name']);
        if ($result) {
            return self::response(400, $result);
        }
        $result = $this->checkPhone($data['phone']);
        if ($result) {
            return self::response(400, $result);
        }
        $result = $this->validate(
            $data,
            [
                'id' => 'require|token',
                'name' => 'require',
                'phone' => 'require',
            ],
            [
                'id.require' => '请先选择活动',
                'name.require' => '姓名不能为空',
                'phone.require' => '请输入电话号码'
            ]
        );
        if (true !== $result) {
            // 验证失败 输出错误信息
            return self::response(400, $result, ['token' => $request->token()]);
        }

        try {
            $act = Act::get($data['id']);
            if (empty($act)) {
                return self::response(400, $result, ['token' => $request->token()]);
            }
            // 创建活动订单
            $actRecords = new ActRecords();
            $actRecords->act_id = $act->id;
            $actRecords->open_id = $this->openId;
            $actRecords->user_id = WeiXin::getUserIdByOpenid($this->openId);
            $actRecords->name = $data['name'];
            $actRecords->phone = $data['phone'];
            if ($act->isfree != 1) {
                $actRecords->price = $act['cost'];
                $actRecords->need_pay = 1;
                $actRecords->order_no = WeiXin::createOrderNo(1);
            }
            $actRecords->save();
            // 获取报名记录ID
            $recordsid = $actRecords->id;

            if ($act->isfree != 1) {
                if (!$recordsid) {
                    return self::response(400, '报名失败', ['token' => $request->token()]);
                }
                // 写入日志
                $log = new Log();
                $log->order_no = $actRecords->order_no;
                $log->user_id = $actRecords->user_id;
                $log->open_id = $this->openId;
                $log->type = 1;
                $log->content = $act['name'] . ':活动报名发起支付';
                $log->price = $act['cost'];
                $log->save();
                // 返回支付接口参数
                $wxPayConfig = json_decode(WeiXin::weiXinPayData('重庆老街活动报名:' . $act['name'], $actRecords->order_no, $act['cost'] * 100, $this->openId), true);
                $wxPayConfig['token'] = $request->token();
                $wxPayConfig['order_no'] = $actRecords->order_no;
                return self::response(0, '支付创建成功', $wxPayConfig);
            }
            if ($recordsid) {
                return self::response(0, '报名成功');
            } else {
                return self::response(400, '报名失败', ['token' => $request->token()]);
            }
        } catch (Exception $e) {
            return self::response(400, $e->getMessage(), ['token' => $request->token()]);
        }
    }

    /**
     * 报名失败和取消处理
     * @param Request $request
     * @return \think\response\Json
     */
    public function failJoin(Request $request)
    {
        if (!$request->isAjax()){
            return self::response(400, '非法请求');
        }
        $orderNo = $request->param('order_no');
        $msg = $request->param('msg');
        if (!$orderNo || !$msg){
            return self::response(400, '非法请求');
        }
        // 根据订单 查询信息
        $actRecordsObj = new ActRecords();
        $actRecords = $actRecordsObj->getOneByOrder($orderNo);

        // 写入日志
        if ($actRecords){
            $log = new Log();
            $log->order_no = $orderNo;
            $log->user_id = $actRecords->user_id;
            $log->open_id = $actRecords->open_id;
            $log->type = 1;
            if ($msg == 'cancel'){
                $log->content = '支付取消';
            }else{
                $log->content = '支付失败';
            }
            $log->price = $actRecords->price;
            $log->save();
            return self::response(0);
        }else{
            return self::response(400, '非法请求');
        }
    }

    /**
     * 验证姓名
     * @param $value
     * @return string
     */
    private function checkName($value)
    {
        if (mb_strlen($value) < 2 || mb_strlen($value) > 5) {
            return '姓名长度在2-5之间';
        }
    }

    /**
     * 验证手机号
     * @param $value
     * @return string
     */
    private function checkPhone($value)
    {
        if (mb_strlen($value) !== 11 || intval($value) <= 0 || substr($value, 0, 1) != 1) {
            return '请输入正确的手机号';
        }
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

    /**
     * @param string $msg
     * @param string $url
     * @param string $icon
     * @param int $time
     * @return string
     */
    private function fail($msg='',$url='',$icon='',$time=3){
        $str = '<script type="text/javascript" src="/static/js/jquery-1.11.3.min.js"></script><script type="text/javascript" src="/static/js/layer/layer.js"></script>';//加载jquery和layer
        $str .= '<script>$(function(){layer.msg("<span style=font-size:20px;width:200px;height:100px;>'.$msg.'</span>",{icon:'.$icon.',time:'.($time*1000).'});setTimeout(function(){self.location.href="'.$url.'"},2000)});</script>';//主要方法
        return $str;
    }
}