<?php
/**
 * ,__,
 * (oo)_____
 * (__)     )\
 * ````||---|| *
 * Class Base <br>
 * @package app\index\controller <br>
 * @author mutou <br>
 * @version 1.0.0
 * @date 23/01/2018 <br>
 */

namespace app\index\controller;

use \think\Controller;
use \think\Session;
use \think\View;
use app\index\model\Config;
use think\Request;
use \app\index\service\WeiXin;

class Base extends Controller
{
    protected $openId;
    protected $view;

    public function __construct(Request $request)
    {
        parent::__construct($request);
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
        if (!$request->isAjax()){
            $this->view = new View();
            //$config = Config::get(1);
            //$this->assign('tel',$config->tel);
        }
    }

    public function _empty()
    {
        $this->redirect('/');
    }
}