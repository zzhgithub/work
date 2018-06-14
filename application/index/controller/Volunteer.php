<?php

namespace app\index\controller;

/**
 * 文物志愿者
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:24
 */
use app\index\model\Cert;
use app\index\model\Inspect;
use \app\index\model\Member;
use \app\index\model\Point;
use \app\index\model\Pointbanner;
use \app\index\model\Pointdetail;
use \app\index\model\TrainContent;
use app\index\service\WeiXin;
use app\index\service\WxPayException;
use \think\Exception;
use think\exception\DbException;
use \think\Request;
use \think\Validate;
use \think\config;

class Volunteer extends Base
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->assign('_action', 'index');
    }

    /**
     * 文物注册页
     * @param Request $request
     * @return mixed|\think\response\Json
     * @throws DbException
     */
    public function register(Request $request)
    {
        try {
            $uid = WeiXin::getUserIdByOpenid($this->openId);
            if ($request->isPost()) {
                $data = $request->post('', null, 'htmlspecialchars');
                unset($data['know']);
                // 表单验证
                $validate = new Validate([
                    'name' => 'require|max:20|token',
                    'gender' => 'require',
                    'id_cards' => 'require',
                    'email' => 'email',
                    'phone' => 'require',
                    'career' => 'require',
                    'address' => 'require',
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
                        'address.require' => '通讯地址必须',
                        'reason.require' => '加入原因必须',
                        'from.require' => '知晓来源必须',
                    ]
                );

                if (!$validate->check($data)) {
                    return self::response(400, $validate->getError(), ['token' => $request->token()]);
                } else {
                    unset($data['__token__']);
                    $member = New Member();
                    $user = Member::get($uid);
                    // 修改
                    if ($user != null) {
                        $member->save($data, ['uid' => $uid]);
                        return self::response(0, '你的信息已提交成功，工作人员稍后会和你联系！');
                    } else {      // 新增
                        $data['uid'] = $uid;
                        $res = $member->data($data)->save();
                    }
                    if ($res) {
                        return self::response(0, '你的信息已提交成功，工作人员稍后会和你联系！');
                    }
                    return self::response(400, '注册失败', ['token' => $request->token()]);
                }
            }
            $user = Member::get(['uid' => $uid]);
            $otherFrom = '';
            if ($user != null && $user->from){
                $fromArr = explode('|',$user->from);
                if (isset($fromArr[count($fromArr)-2]) && $fromArr[count($fromArr)-2] == '其他'){
                    $otherFrom = end($fromArr);
                }
            }
            $this->assign('title', '志愿者注册');
            $this->assign('user', $user);
            $this->assign('otherFrom', $otherFrom);
            return $this->fetch('volunteer/register');
        } catch (WxPayException $e) {
            return $e->getMessage();
        }
    }

    /**
     * 文物巡查页 加载固定模板
     */
    public function inspect()
    {
        $this->assign('title', '文物巡查');
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
        if ($search) {
            $list = $train->alias('a')->where('a.title', 'like',
                '%' . $search . '%')->order('a.id desc')->join('ly_train_cate b', 'a.cate_id = b.id',
                'LEFT')->field('a.id,title,img,name')->paginate(10);
        } else {
            $list = $train->alias('a')->order('a.id desc')->join('ly_train_cate b', 'a.cate_id = b.id',
                'LEFT')->field('a.id,title,img,name')->paginate(10);
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
        $this->assign('title', '文物保护培训');
        return $this->fetch('volunteer/train_list');
    }

    /**
     * 培训详情页
     * @return mixed|\think\response\Redirect
     */
    public function trainDetail()
    {
        try {
            $id = intval(Request::instance()->param('id'));
            if (!$id) {
                return redirect('/');
            }
            $trainContent = TrainContent::get($id);
            if ($trainContent == null) {
                return redirect('/');
            }
            $this->assign('train', $trainContent);
            $this->assign('title', $trainContent->title);
        } catch (Exception $e) {
            return redirect('/');
        }
        return $this->fetch('volunteer/train_detail');
    }

    /**
     * 巡查反馈列表页
     * @param Request $request
     * @return string|\think\response\Json
     */
    public function inspectBackList(Request $request)
    {
        try {
            $search = Request::instance()->param('search', null, 'stripslashes');
            $point = new Point();
            if ($search) {
                $levelArr = [
                    '市宝' => 1,
                    '区宝' => 2,
                    '国宝' => 3,
                    '文物点' => 4
                ];
                if (key_exists($search, $levelArr)) {
                    $list = $point->where('name', 'like', '%' . $search . '%')
                        ->whereOr('zone', 'like', '%' . $search . '%')
                        ->whereOr('level', $levelArr[$search])
                        ->paginate(8);
                } else {
                    $list = $point->where('name', 'like', '%' . $search . '%')
                        ->whereOr('zone', 'like', '%' . $search . '%')
                        ->paginate(8);
                }
            } else {
                $list = $point->paginate(8);
            }
            $items = $list->items();
            if ($request->isAjax()) {
                if (!empty($items)) {
                    return self::response(0, 'success', $items);
                }
                return self::response(400);
            }
            $this->assign('list', $items);
            $this->assign('search', $search);
            $this->assign('title', '巡查反馈');
            $this->assign('curPage', 1);
            return $this->view->fetch('volunteer/inspect_back_list');
        } catch (Exception $e) {
            return $e->getMessage();
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
        $id = intval($id);
        $request = Request::instance();
        if ($request->isAjax()){
            $_data = $request->post('',null,'htmlspecialchars');
            $data = [
                'msg' => $_data['msg'],
                '__token__' => $_data['__token__']
            ];
            $validate = new Validate([
                'msg' => 'require|max:500|token',
            ], [
                'msg.require' => '请输入反馈信息']
            );
            if (!$validate->check($data)) {
                return self::response(400, $validate->getError());
            }
            $data['uid'] = $this->uid;
            $data['pid'] = $id;
            $data['des'] = $data['msg'];
            unset($data['__token__'],$data['msg']);
            $inspect = new Inspect();
            $res = $inspect->data($data)->save();
            if (!$res){
                return self::response(400, '巡查留言提交失败');
            }
            return self::response(0, '巡查留言已提交！');
        }
        $base = Point::get($id);
        $this->assign('base', $base);

        $ext = Pointdetail::get($id);
        $this->assign('ext', $ext);

        //$client = new Pointbanner();
        //$list = $client->where(['pid' => $id])->select();

        $inspect_Client = new Inspect();
        $prefix = config("database.prefix");
        $inspect = $inspect_Client->alias('a')->join($prefix . 'user b',
            'a.uid = b.id', 'LEFT')->field('a.des,b.nickname,b.headimgurl')->where(['a.pid' => $id,'a.state' => 1])->order('a.id DESC')->select();

        $levelArr = Config::get('point.point_level');
        $this->assign('level', $levelArr);
        $this->assign("inspect", $inspect);
        //$this->assign('list', $list);
        $this->assign('id', $id);
        $this->assign('token', Request::instance()->token());
        $this->assign('title', $base->name . '-巡查反馈');
        //巡查反馈页

        return $this->fetch('volunteer/inspect_back_detail');
    }

    /**
     * 任务点证书
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function certificate()
    {
        // 获取证书的数据
        $client = new Cert();
        $list = $client->order('sort')->select();
        $this->assign('list', $list);
        $this->assign('title', '任务点证书');

        return $this->fetch('volunteer/certificate');
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