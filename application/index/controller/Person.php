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
use \think\Session;

class Person extends Controller{

    public function index(){
        $openId = Session::get('openid');
        if (!$openId){
            WeiXin::getOpenidAndAcessToken();
        }
        $this->assign('_action','ucenter');
        return $this->fetch('ucenter/index');
    }
}