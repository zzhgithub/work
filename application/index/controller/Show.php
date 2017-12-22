<?php

namespace app\index\controller;

/**
 * 文物展示相关
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:06
 */
use app\index\model\Inspect;
use app\index\model\Point;
use app\index\model\Pointbanner;
use app\index\model\Pointdetail;
use app\index\model\Route;
use app\index\model\Routepoint;
use think\Controller;
use think\Exception;
use think\Request;
use think\View;

class Show extends Controller
{
    protected $view;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
        $this->assign('_action','index');
    }

    /**
     * 历史建筑
     * @param Request $request
     * @return string
     */
    public function historyStruct(Request $request)
    {
        //默认情况没有查询条件
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
            $this->assign('curPage', 1);
            return $this->view->fetch('point/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 任务点打开
     * 加载配置的固定模板
     */
    public function punch()
    {
        //todo
    }

    /**
     * 推荐路线页(分页查询 这里交互模糊)
     * @param Request $request
     * @return string|\think\response\Json
     */
    public function pathList(Request $request)
    {
        try {
            $search = Request::instance()->param('search', null, 'stripslashes');
            $client = new Route();
            if ($search) {
                $list = $client->where('name', 'like', '%' . $search . '%')->order('sort')
                    ->paginate(8);
            } else {
                $list = $client->order('sort')->paginate(8);
            }
            $items = $list->items();
            if ($request->isAjax()) {
                if (!empty($items)) {
                    return self::response(0, 'success', $items);
                }
                return self::response(400);
            }
            $this->assign('list', $list);
            $this->assign('search', $search);
            $this->assign('curPage', 1);
            return $this->view->fetch('route/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 推荐路线查询页
     * @param $id
     * @return string
     */
    public function pathDetail($id)
    {
        try {
            $data = Route::get($id);
            $this->assign('data', $data);
            // 获文物点地址
            $client = new Routepoint();
            $list = $client->where(['rid' => $id])
                ->order('sort')
                ->select();
            foreach ($list as $k => $v) {
                //
                $tmp = Point::get($v['pid']);
                $list[$k]['name'] = $tmp['name'];
            }
            $this->assign('list', $list);
            return $this->view->fetch('route/detail');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 文物点详情页
     * @param $id
     * @return string
     */
    public function ponitDetail($id)
    {
        try {
            $base = Point::get($id);
            $this->assign('base', $base);

            $ext = Pointdetail::get($id);
            $this->assign('ext', $ext);

            $client = new Pointbanner();
            $list = $client->where(['pid' => $id])->select();
            $this->assign('list', $list);

            $inspect = new Inspect();
            $inspect_list = $inspect->where(['pid' => $id])
                ->select();
            $this->assign('inspect', $inspect_list);

            return $this->view->fetch('point/detail');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 任务点证书页 todo 这里和上面的对应关系是什么呢？
     * @param $id
     */
    public function paper($id)
    {
        //todo
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
