<?php

namespace app\index\controller;

/**
 * 文物志愿者
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:24
 */
use app\index\model\Member;
use app\index\model\Point;
use think\Exception;
use \think\Request;
use \think\Controller;
use \think\Validate;

class Volunteer extends Controller
{
    /**
     * @deprecated
     * 文物注册页
     */
    public function register(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->post();
            unset($data['know']);
            // 表单验证
            $validate = new Validate([
                'name' => 'require|max:20|token',
                'gender' => 'require',
                'id_cards' => 'require',
                'email' => 'email',
                'phone' => 'require',
                'career' => 'require',
                'reason' => 'require',
                'from' => 'require'
            ], [
                    'name.require' => '姓名必须',
                    'name.max' => '姓名最多不能超过20个字符',
                    'gender.require' => '性别必须',
                    'id_cards.require' => '身份证必须',
                    'email' => '邮箱格式错误',
                    'phone.require' => '手机必须',
                    'career.require' => '职业必须',
                    'reason.require' => '加入原因必须',
                    'from.require' => '知晓来源必须',
                ]
            );

            if (!$validate->check($data)) {
                return self::response(400, $validate->getError(),['token'=>$request->token()]);
            }else{
                unset($data['__token__']);
                $member = New Member();
                $res = $member->data($data)->save();
                if ($res){
                    return self::response(0, '注册成功');
                }else{
                    return self::response(400, '注册失败',['token'=>$request->token()]);
                }
            }
        }
        return $this->fetch('volunteer/register');
    }

    /**
     * 文物巡查页 加载固定模板
     */
    public function inspect()
    {
        //todo
        return $this->fetch('volunteer/inspect');
    }

    /**
     * 文物保护培训列表页
     * @param $query
     */
    public function trainList()
    {
        //todo
        return $this->fetch('volunteer/train_list');

    }

    /**
     * 培训详情页
     * @param $id
     */
    public function trainDetail()
    {
        //todo
        return $this->fetch('volunteer/train_detail');

    }

    /**
     * 巡查反馈列表页
     * @param $query
     */
    public function inspectBackList($query = null)
    {
        try {
            $search = Request::instance()->param('search', null, 'stripslashes');
            if ($search) {
                $levelArr = [
                    '市宝' => 1,
                    '区宝' => 2,
                    '国宝' => 3,
                    '文物点' => 4
                ];
                if (key_exists($search, $levelArr)) {
                    $point = new Point();
                    $list = $point->where('name', 'like', '%' . $search)
                        ->whereOr('zone', 'like', '%' . $search)
                        ->whereOr('level', $levelArr[$search])
                        ->select();
                } else {
                    $point = new Point();
                    $list = $point->where('name', 'like', '%' . $search)
                        ->whereOr('zone', 'like', '%' . $search)
                        ->select();
                }
            } else {
                $list = Point::all();
            }
            $this->assign('list', $list);
            return $this->fetch('volunteer/inspect_back_list');
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

    /**
     * 巡查反馈详情页
     * @param $id
     */
    public function inspectBackDetail()
    {
        //todo
        return $this->fetch('volunteer/inspect_back_detail');

    }

    public function certificate()
    {
        return $this->fetch('volunteer/certificate');
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