<?php
/**
 * ,__,
 * (oo)_____
 * (__)     )\
 * ````||---|| *
 * Class BossLogin
 * @package app\index\controller  <br>
 * @author mutou <br>
 * @version 1.0.0
 * @description 管理登录
 * @date 2017/12/23 <br>
 */

namespace app\index\controller;

use \app\index\model\Admin;
use \think\Controller;
use \think\Request;
use \think\captcha\Captcha;
use \think\Session;

class BossLogin extends Controller
{
    public function login(Request $request)
    {
        if (Session::get('admin')){
            return redirect('/boss/index');
        }
        if ($request->isAjax()) {
            $data = $request->param();
            $data['passwd'] = $data['pwd'];
            $data['name'] = $data['login'];
            $result = $this->checkName($data['name']);
            if ($result) {
                return self::response(400, $result, ['token' => $request->token()]);
            }
            $result = $this->validate(
                $data,
                [
                    'name' => 'require|token',
                    'passwd' => 'require|min:6',
                    'code' => 'require|captcha'
                ],
                [
                    'name.require' => '用户名不能为空',
                    'passwd.require' => '密码不能为空',
                    'passwd.min' => '密码长度不能小于6',
                    'code.require' => '验证码不能为空'
                ]
            );
            if (true !== $result) {
                // 验证失败 输出错误信息
                return self::response(400, $result, ['token' => $request->token()]);
            }

            $adminModel = new Admin();
            $admin = $adminModel->where(['name' => $data['name']])->find();
            if (empty($admin)){
                return self::response(400, '用户不存在', ['token' => $request->token()]);
            }
            if($admin->passwd !== sha1(md5($data['passwd']))){
                return self::response(400, '密码错误', ['token' => $request->token()]);
            }
            Session::set('admin',$admin->id.'|'.$admin->name);
            return self::response(0, '登录成功');
        }
        $this->assign('token', $request->token());
        return $this->fetch('boss/login/index');
    }

    public function logout()
    {
        Session::set('admin',null);
        return $this->redirect('/boss/login');
    }

    public function verify()
    {
        $config = [
            // 验证码位数
            'length' => 4
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

    private static function response($code, $msg = '', $data = [])
    {
        $response = new \stdClass();
        $response->code = $code;
        $response->data = $data;
        $response->msg = $msg;
        return json($response);
    }

    private function checkName($value)
    {
        if (mb_strlen($value) < 2 || mb_strlen($value) > 20) {
            return '用户名长度在2-20之间';
        }
        $pattern = '/^[a-zA-Z0-9_^\x00-\x80\s·]+$/';
        if (!preg_match($pattern, $value)) {
            return '用户名不能包含特殊字符';
        }
    }
}