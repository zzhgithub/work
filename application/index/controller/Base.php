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

use app\index\model\Member;
use app\index\service\WxPayException;
use \think\Controller;
use think\exception\DbException;
use \think\Session;
use \think\View;
use think\Request;
use \app\index\service\WeiXin;

class Base extends Controller
{
    protected $openId;
    protected $view;
    protected $userPass;
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $openId = Session::get('openid');
        try {
            $user = Member::get(['uid' => WeiXin::getUserIdByOpenid($openId)]);
            $this->userPass = $user != null && $user->state == 1 ? true : false;
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
                $this->assign('userPass',$this->userPass);
                //$config = Config::get(1);
                //$this->assign('tel',$config->tel);
            }
        } catch (DbException $e) {
        } catch (WxPayException $e) {
        } catch (\Exception $e) {
        }
    }

    public function _empty()
    {
        $this->redirect('/');
    }
}