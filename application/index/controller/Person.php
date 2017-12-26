<?php
namespace app\index\controller;
/**
 * 个人中心
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:53
 */
use think\Controller;
use \app\index\service\WeiXin;
use think\Request;
use \think\Session;

class Person extends Controller
{
    protected $openId;

    /**
     * 个人中心
     * @return mixed
     * @throws \Exception
     */
    public function index(Request $request){
        $openId = Session::get('openid');
        if (!$openId) {
            if ($request->isAjax()) {
                return self::response(400, '请刷新页面重新登录');
            } else {
                WeiXin::getOpenidAndAcessToken();
            }
        }
        $this->openId = $openId;
        $this->assign('_action','ucenter');
        $this->assign('title','个人中心');
        return $this->fetch('ucenter/index');
    }
}