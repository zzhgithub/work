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
     */
    public function activityList()
    {
        try {
            //
            $client = new Act();
            $list = $client->order('sort')
                ->select();
            $this->assign('list', $list);
            return $this->view->fetch('act/list');
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

    /**
     * 活动详情页
     */
    public function activityDetail($id)
    {
        if (!$id) {
            return $this->redirect('/');
        }
        try {
            $data = Act::get($id);
            $this->assign('data', $data);
            return $this->view->fetch('act/detail');
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

    /**
     * @param $id
     * @return string|void
     * @throws \Exception
     */
    public function joinActivity($id)
    {
        if (!$id) {
            return $this->redirect('/');
        }
        try {
            $data = Act::get($id);

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
            $this->assign('data', $data);
            return $this->view->fetch('act/join');
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

    /**
     * 报名页表单处理
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
                $log->relate_id = $recordsid;
                $log->user_id = $actRecords->user_id;
                $log->open_id = $this->openId;
                $log->type = 1;
                $log->content = $act['name'] . ':活动报名发起支付';
                $log->price = $act['cost'];
                $log->save();
                // 返回支付接口参数
                $wxPayConfig = json_decode(WeiXin::weiXinPayData('重庆老街活动报名:' . $act['name'], $recordsid, $act['cost'] * 100, $this->openId), true);
                $wxPayConfig['token'] = $request->token();
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

    private function checkName($value)
    {
        if (mb_strlen($value) < 2 || mb_strlen($value) > 5) {
            return '用户名长度在2-5之间';
        }
    }

    private function checkPhone($value)
    {
        if (mb_strlen($value) !== 11 || intval($value) <= 0 || substr($value, 0, 1) != 1) {
            return '请输入正确的手机号';
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