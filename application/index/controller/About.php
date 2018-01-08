<?php
namespace app\index\controller;

use app\index\model\Config;
use \think\Controller;
use think\Request;
use \app\index\service\WeiXin;
use \think\Session;

class About extends Controller
{
    protected $view;
    protected $openId;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->assign('_action','about');
        //$openId = Session::get('openid');
        //if (!$openId) {
        //    if ($request->isAjax()) {
        //        return self::response(400, '请刷新页面重新登录');
        //    } else {
        //        $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        //        WeiXin::getOpenidAndAcessToken($url);
        //    }
        //}
        //$this->openId = $openId;
    }

    /**
     * 关于我们
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $about = Config::get(1);
        $this->assign('title','关于我们');
        $this->assign('about',$about);
        return $this->fetch('about/index');
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
