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
}
