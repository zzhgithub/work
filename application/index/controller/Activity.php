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
    protected $response;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
        $this->response = [
            'code' => 200,
            'message' => '',
            'data' => []
        ];
        $this->assign('_action','index');
        $openId = Session::get('openid');
        if (!$openId){
            WeiXin::getOpenidAndAcessToken();
        }
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
            if ($data->isfree != 1){
                $accessToken = WeiXin::getAccessToken();
                $jsApiTicket = WeiXin::getJsApiTicket($accessToken);
                $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $jsApi = new WeiXinJs();
                $jsApi->appId = Config::get('weixin.APPID');
                $jsApi->nonceStr = WeiXin::getNonceStr();
                $jsApi->timestamp = time();
                $jsApi->signature = WeiXin::signature($jsApiTicket,$jsApi->nonceStr,$jsApi->timestamp,$url);
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
        if (!$request->isAjax()){
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
            $data = Act::get($data['id']);
            if (empty($data)){
                $this->response['message'] = '非法请求';
                $this->response['code'] = 400;
                return json($this->response);
            }
            //

            $this->response['message'] = '报名成功';
            $this->response['code'] = 200;
        } catch (Exception $e) {
            $this->response['code'] = 400;
            $this->response['message'] = $e->getMessage();
        }
        return json($this->response);
    }

    private function checkName($value)
    {
        if (mb_strlen($value) < 2 || mb_strlen($value) > 5) {
            return '用户名长度在2-5之间';
        }
        $pattern = '/^[a-zA-Z0-9_^\x00-\x80\s·]+$/';
        if (!preg_match($pattern, $value)) {
            return '用户名不能包含特殊字符';
        }
    }

    private function checkPhone($value)
    {
        if (mb_strlen($value) !== 11 || intval($value) <=0 || substr($value,0,1) != 1) {
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