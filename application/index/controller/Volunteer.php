<?php

namespace app\index\controller;

/**
 * 文物志愿者
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:24
 */
use \app\index\model\Member;
use \app\index\model\Point;
use \app\index\model\Pointbanner;
use \app\index\model\Pointdetail;
use \app\index\model\TrainContent;
use \think\Exception;
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
     * @param Request $request
     * @return mixed|\think\response\Json
     * @throws \think\exception\DbException
     */
    public function trainList(Request $request)
    {
        $search = Request::instance()->param('search', null, 'stripslashes');
        $train = new TrainContent();
        if ($search){
            $list = $train->alias('a')->where('a.title', 'like', '%' . $search.'%')->order('a.id desc')->join('ly_train_cate b','a.cate_id = b.id','LEFT')->field('a.id,title,img,name')->paginate(10);
        }else{
            $list = $train->alias('a')->order('a.id desc')->join('ly_train_cate b','a.cate_id = b.id','LEFT')->field('a.id,title,img,name')->paginate(10);
        }

        $items = $list->items();
        if ($request->isAjax()) {
            if (!empty($items)) {
                return self::response(0, 'success', $items);
            }
            return self::response(400);
        }
        $this->assign('search', $search);
        $this->assign('curPage', 1);
        $this->assign('list', $items);
        return $this->fetch('volunteer/train_list');
    }

    /**
     * 培训详情页
     * @return mixed|\think\response\Redirect
     */
    public function trainDetail()
    {
        try{
            $id = intval(Request::instance()->param('id'));
            if (!$id){
                return redirect('/');
            }
            $trainContent = TrainContent::get($id);
            if ($trainContent == null){
                return redirect('/');
            }
            $this->assign('train',$trainContent);
        }catch (Exception $e){
            return redirect('/');
        }
        return $this->fetch('volunteer/train_detail');
    }

    /**
     * 巡查反馈列表页
     * @param null $query
     * @return mixed
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
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function inspectBackDetail($id)
    {
        $base = Point::get($id);
        $this->assign('base', $base);

        $ext = Pointdetail::get($id);
        $this->assign('ext', $ext);

        $client = new Pointbanner();
        $list = $client->where(['pid' => $id])->select();
        $this->assign('list', $list);
        //巡查反馈页

        return $this->fetch('volunteer/inspect_back_detail');
    }

    /**
     * @return mixed
     */
    public function certificate()
    {
        return $this->fetch('volunteer/certificate');
    }

    /**
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