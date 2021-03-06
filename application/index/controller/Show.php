<?php

namespace app\index\controller;

/**
 * 文物展示相关
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:06
 */
use app\index\model\Point;
use app\index\model\Pointbanner;
use app\index\model\Pointdetail;
use app\index\model\Pointnear;
use app\index\model\Route;
use app\index\model\Routepoint;
use app\index\model\Zone;
use think\Config;
use think\Exception;
use think\Request;

class Show extends Base
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
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
            $prefix = config("database.prefix");
            $point = new Point();
            $zoneId = (int)$request->get('cate');
            $search = Request::instance()->param('search', null, 'stripslashes');
            if($zoneId) {
                $list  = $point->alias('a')->join($prefix . 'zone b',
                    'a.zone_id = b.id', 'LEFT')->where(['a.zone_id' => $zoneId])->field('a.id,a.zone_id,a.name,a.number,a.img,a.level,a.addr,b.name as zone')->paginate(8);
            }else{
                if ($search) {
                    $levelArr = array_flip(Config::get('point.point_level'));
                    if (key_exists($search, $levelArr)) {
                        $list = $point->alias('a')->join($prefix . 'zone b',
                            'a.zone_id = b.id', 'LEFT')->where('a.name', 'like', '%' . $search . '%')
                            ->whereOr('b.name', 'like', '%' . $search . '%')
                            ->whereOr('a.level', $levelArr[$search])
                            ->field('a.id,a.zone_id,a.name,a.number,a.img,a.level,a.addr,b.name as zone')->paginate(8);
                    } else {
                        $list = $point->alias('a')->join($prefix . 'zone b',
                            'a.zone_id = b.id', 'LEFT')->where('a.name', 'like', '%' . $search . '%')
                            ->whereOr('b.name', 'like', '%' . $search . '%')
                            ->field('a.id,a.zone_id,a.name,a.number,a.img,a.level,a.addr,b.name as zone')->paginate(8);
                    }
                } else {
                    $list = $point->paginate(8);
                }
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
            $this->assign('title', '历史建筑');
            $this->assign('curPage', 1);
            return $this->view->fetch('point/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 获取文物点区域信息
     * @param Request $request
     * @return string
     */
    public function historyZone(Request $request)
    {
        try {
            $zones = Zone::all();
            $this->assign('zones', $zones);
            $this->assign('title', '历史建筑');
            return $this->view->fetch('classify/classify');
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
                $list = $client->where('name', 'like', '%' . $search . '%')
                    ->where('num', 'like', '%' . $search . '%')
                    ->order('sort')
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
            $this->assign('list', $items);
            $this->assign('search', $search);
            $this->assign('title', '推荐线路');
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
            $id = intval($id);
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
            $this->assign('title', '推荐线路详情');
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
            $id = intval($id);
            $base = Point::get($id);
            $this->assign('base', $base);

            $ext = Pointdetail::get($id);
            $this->assign('ext', $ext);
            $this->assign('title', $base->name);

            $levelArr = Config::get('point.point_level');
            $this->assign('level',$levelArr);

            $prefix = config("database.prefix");
            $nearcleint = new Pointnear();
            $near = $nearcleint->alias('a')->join($prefix . 'point b',
                'a.nid = b.id', 'LEFT')->where(['pid'=>$id])->field('a.nid,b.name')->select();
            $this->assign("nearlist",$near);

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
