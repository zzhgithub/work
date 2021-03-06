<?php

namespace app\index\controller;

/**
 * 后台
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午6:06
 */
use app\index\model\Act;
use app\index\model\ActRecords;
use app\index\model\Admin;
use app\index\model\Banner;
use app\index\model\Cert;
use app\index\model\CertRecords;
use app\index\model\Donate;
use app\index\model\DonateRecords;
use app\index\model\Inspect;
use app\index\model\Member;
use app\index\model\Order;
use app\index\model\OrderItem;
use app\index\model\Point;
use app\index\model\Pointbanner;
use app\index\model\Pointdetail;
use app\index\model\Pointnear;
use app\index\model\Product;
use app\index\model\ProductContent;
use app\index\model\ProductImg;
use app\index\model\Route;
use app\index\model\Routepoint;
use app\index\model\News;
use app\index\model\TrainCate;
use app\index\model\TrainContent;
use app\index\model\Zone;
use app\lib\Excel;
use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\Request;
use think\Session;
use think\View;
use \app\index\model\Config;

class Boss extends Controller
{
    protected $view;
    protected $title;
    protected $admin;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $admin = Session::get('admin');
        if (empty($admin)) {
            if ($request->isAjax()) {
                return self::response(400, '请先登录~');
            } else {
                $this->redirect('/boss/login');
            }
        }
        $this->view = new View();
        $this->title = Config::get('boss_title');
        $this->admin = explode('|', $admin); // [id, name]
        if (!$request->isAjax()) {
            $this->assign('admin', $this->admin);
            $this->assign('pathInfo', $_SERVER['PATH_INFO']);
        }
    }

    //boss后台设计
    public function index()
    {
        $this->assign('title', $this->title);
        $this->redirect('/boss/point/list');
        //return $this->fetch('boss/index/index');
    }

    // banner显示列表
    public function bannerList()
    {
        try {
            $bannerObj = new Banner();
            $list = $bannerObj->order('sort')->select();
            $this->assign('list', $list);
            $this->assign('title', "轮播图列表");
            return $this->fetch('boss/banner/newlist');
        } catch (Exception $e) {
        }
    }

    //加载 banner 页的修改或者 添加页
    public function bannerAddOrModify($id = null)
    {
        try {
            if ($id == null) {
                //如果这里id没有传过来就是说明 是 添加
                $this->assign('title', '添加banner图');
                $this->assign('data', null);
            } else {
                $id = intval($id);
                // 获取单条数据
                $this->assign('title', '修改banner图');
                $res = Banner::get($id);
                $this->assign('data', $res);
            }
            return $this->fetch('boss/banner/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //banner 图片保存加载一条龙图片
    public function bannerSave(Request $request)
    {
        try {
            $inputData = $request->param('', null, 'htmlspecialchars');
            $banner = new Banner();
            $data = new \stdClass();
            $data->img = $inputData['img'];
            $data->des = $inputData['des'];
            $data->url = $inputData['url'];
            foreach ((array)$data as $v) {
                if (!$v) {
                    return self::response(400, '请输入完整信息');
                }
            }
            $data->sort = $inputData['sort'];
            if (isset($inputData['id']) && intval($inputData['id']) > 0) {
                $banner->save($data, ['id' => intval($inputData['id'])]);
            } else {
                $banner->data($data);
                $res = $banner->save();
            }
            if (isset($res) && !$res) {
                return self::response(400, '操作失败');
            }
            return self::response(0, '操作成功');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //首页的banner删除
    public function bannerDel($id)
    {
        try {
            Banner::destroy(intval($id));
            //删除后加载列表页
            return $this->bannerList();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 获取文物点列表
    public function pointList(Request $request)
    {
        try {
            $pointLevel = \think\Config::get('point.point_level');
            $prefix = config("database.prefix");
            $page = intval($request->request('page')) ? intval($request->get('page')) : 1;
            $limit = 10;
            $pointObj = new Point();
            $totalSize = $pointObj->count();
            $totalPage = ceil($totalSize / $limit);
            if ($page >= $totalPage) {
                $page = $totalPage;
            }
            $list = $pointObj->alias('a')->order('sort ASC,a.id DESC')->join($prefix . 'zone b', 'a.zone_id = b.id',
                'LEFT')->field('a.id,a.name,a.img,a.level,a.addr,a.sort,b.name as zone')->limit(($page - 1) * $limit,
                $limit)->select();
            $this->assign('page', $page);
            $this->assign('limit', $limit);
            $this->assign('totalPage', $totalPage);
            $this->assign('totalSize', $totalSize);
            $this->assign("title", "文物点列表");
            $this->assign('list', $list);
            $this->assign('pointLevel', $pointLevel);
            return $this->fetch('boss/point/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function pointAddOrModify($id = null)
    {
        try {
            $pointLevel = \think\Config::get('point.point_level');
            $zones = Zone::all();
            $detail = Pointdetail::get($id);
            $ponitNearObj = new Pointnear();
            $prefix = config("database.prefix");
            $nears = $ponitNearObj->alias('a')->join($prefix . 'point b', 'a.nid = b.id',
                'LEFT')->where(['pid' => $id])->field('nid,b.name')->select();
            $nearIdStr = '';
            if ($nears) {
                $nearIdArr = [];
                foreach ($nears as $near) {
                    $nearIdArr[] = $near->nid;
                }
                $nearIdStr = implode(',', $nearIdArr);
            }
            $points = Point::all(function ($query) {
                $query->field('id,name')->order('id DESC');
            });

            if ($id == null) {
                //如果这里id没有传过来就是说明 是 添加
                $this->assign('title', '添加文物点');
                $this->assign('data', null);
            } else {
                // 获取单条数据
                $this->assign('title', '修改文物点');
                $res = Point::get(intval($id));
                $this->assign('data', $res);
            }
            $this->assign('pointLevel', $pointLevel);
            $this->assign('zones', $zones);
            $this->assign('detail', $detail);
            $this->assign('nears', $nears);
            $this->assign('points', $points);
            $this->assign('nearIdStr', $nearIdStr);
            return $this->fetch('boss/point/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //  文物点保存加载一条龙图片
    public function pointSave(Request $request)
    {
        try {
            $inputData = $request->param('', null, 'htmlspecialchars');
            $point = new Point();
            $data = new \stdClass();
            $detail = new \stdClass();
            $data->img = $inputData['img'];
            $data->name = $inputData['name'];
            $data->addr = $inputData['addr'];
            $data->level = $inputData['level'];
            $data->zone_id = (int)$inputData['zone_id'];
            $zone = Zone::get($data->zone_id);
            $data->zone = $zone->name;
            $data->number = $inputData['number'];
            foreach ((array)$data as $v) {
                if (!$v) {
                    return self::response(400, '请输入完整信息');
                }
            }
            $data->sort = $inputData['sort'];

            $detail->des = $_POST['des'];
            $detail->x = $inputData['x'];
            $detail->y = $inputData['y'];
            $pointNear = new Pointnear();
            $pointDetail = new Pointdetail();
            $id = (int)$inputData['id'];
            if (isset($inputData['id']) && $id > 0) {
                $point->save($data, ['id' => $id]);
                // update near
                $pointNear->where(['pid' => $id])->delete();
            } else {
                $point->data($data);
                $res = $point->save();
            }
            $id = $id ? $id : $point->id;
            // update detail
            if ($pointDetail->get($id)) {
                $pointDetail->save($detail, ['id' => $id]);
            } else {
                $detail->id = $id;
                $pointDetail->data($detail);
                $pointDetail->save();
            }
            $nears = array_filter(explode(',', $inputData['nears']));
            if ($nears) {
                $nearArr = [];
                foreach ($nears as $k => $value) {
                    $nearArr[$k]['nid'] = $value;
                    $nearArr[$k]['pid'] = $id;
                }
                $pointNear->saveAll($nearArr);
            }
            if (isset($res) && !$res) {
                return self::response(400, '操作失败');
            }
            return self::response(0, '操作成功');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //删除 文物点
    public function pointDel($id)
    {
        try {
            Point::destroy((int)$id);
            //删除后加载列表页
            return $this->pointList($this->request);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 编辑详情页
    public function pagePointDetail($id)
    {
        try {
            //先获取 详情的值 如果不存在就什么也不显示
            $id = intval($id);
            $test = Pointdetail::get($id);
            if ($test == null) {
                $data = array(
                    'id' => $id,
                    'des' => null,
                    'x' => null,
                    'y' => null
                );
                $this->assign('data', $data);
            } else {
                $this->assign('data', $test);
            }
            $this->assign("title", "文物点详情");
            return $this->fetch('boss/point/detail');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //文物点详情保存
    public function pointDetailSave(Request $request)
    {
        try {
            $inputData = $request->param('', null, 'htmlspecialchars');
            $client = new Pointdetail();
            $data = new \stdClass();
            $data->des = $inputData['des'];
            $data->x = $inputData['x'];
            $data->y = $inputData['y'];
            foreach ((array)$data as $v) {
                if (!$v) {
                    return self::response(400, '请输入完整信息');
                }
            }
            $id = intval($inputData['id']);
            $test = Pointdetail::get($id);

            if ($test == null) {
                $data->id = $id;
                $res = $client->data($data)->save();
            } else {
                $client->save($data, ['id' => $id]);
            }
            if (isset($res) && !$res) {
                return self::response(400, '操作失败');
            }
            return self::response(0, '操作成功');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //获取 文物点banner列表
    public function pointBannerList($id)
    {
        try {
            //获取相应的banner列表
            $id = intval($id);
            $client = new Pointbanner();
            $res = $client->where(['pid' => $id])->select();
            $this->assign("title", "banner列表");
            $this->assign('list', $res);
            $this->assign('pid', $id);
            return $this->fetch('boss/point/banner/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //文物点banner 的添加或者保存
    public function pointBannerAddOrSave($id = null, $pid = null)
    {
        try {
            if ($id != null) {
                $data = Pointbanner::get(intval($id));
                $this->assign('title', '文物点 修改banner图');
                $this->assign('pid', $data->pid);
                $this->assign('data', $data);
            } else {
                if ($pid != null) {
                    $this->assign('title', '文物点 添加banner图');
                    $this->assign('pid', intval($pid));
                    $this->assign('data', null);
                }
            }
            return $this->fetch('boss/point/banner/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 添加或保存
    public function pointBannerSave(Request $request)
    {
        try {
            $inputData = $request->param('', null, 'htmlspecialchars');
            $banner = new Pointbanner();
            $data = new \stdClass();
            $data->img = $inputData['img'];
            $data->des = $inputData['des'];
            $data->url = $inputData['url'];
            $data->pid = $inputData['pid'];
            foreach ((array)$data as $v) {
                if (!$v) {
                    return self::response(400, '请输入完整信息');
                }
            }
            $data->sort = $inputData['sort'];
            if (isset($inputData['id']) && (int)$inputData['id'] > 0) {
                $banner->save($data, ['id' => (int)$inputData['id']]);
            } else {
                $banner->data($data);
                $res = $banner->save();
            }
            if (isset($res) && !$res) {
                return self::response(400, '操作失败');
            }
            return self::response(0, '操作成功');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //删除 文物点banner图
    public function pointBannerDell($id, $pid)
    {
        try {
            $res = Pointbanner::destroy(intval($id));
            return $this->pointBannerList(intval($pid));
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //推荐路线列表
    public function routeList(Request $request)
    {
        try {
            $page = intval($request->request('page')) ? intval($request->get('page')) : 1;
            $limit = 10;
            $client = new Route();
            $totalSize = $client->count();
            $totalPage = ceil($totalSize / $limit);
            if ($page >= $totalPage) {
                $page = $totalPage;
            }
            $list = $client->order('sort')->limit(($page - 1) * $limit, $limit)->select();
            $this->assign('page', $page);
            $this->assign('limit', $limit);
            $this->assign('totalPage', $totalPage);
            $this->assign('totalSize', $totalSize);
            $this->assign('list', $list);
            $this->assign("title", "推荐路线");
            return $this->fetch('boss/route/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //推荐路线 添加或者报错
    public function routeAddOrSave($id = null)
    {
        try {
            $routePointObj = new Routepoint();
            $prefix = config("database.prefix");
            $nears = $routePointObj->alias('a')->join($prefix . 'point b', 'a.pid = b.id',
                'LEFT')->where(['rid' => $id])->field('a.pid,b.name')->select();
            $nearIdStr = '';
            if ($nears) {
                $nearIdArr = [];
                foreach ($nears as $near) {
                    $nearIdArr[] = $near->pid;
                }
                $nearIdStr = implode(',', $nearIdArr);
            }
            $points = Point::all(function ($query) {
                $query->field('id,name')->order('id DESC');
            });
            //
            if ($id != null) {
                $data = Route::get((int)$id);
                $this->assign('title', "推荐路线修改");
                $this->assign('data', $data);
            } else {
                $this->assign('title', '推荐路线添加');
                $this->assign('data', null);
            }
            $this->assign('nearIdStr', $nearIdStr);
            $this->assign('points', $points);
            $this->assign('nears', $nears);

            return $this->fetch('boss/route/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //ajax 保存推荐路线
    public function routeSave(Request $request)
    {
        try {
            $inputData = $request->param('', null, 'htmlspecialchars');
            $route = new Route();
            $data = new \stdClass();
            $data->img = $inputData['img'];
            $data->des = $inputData['des'];
            $data->name = $inputData['name'];
            $data->num = $inputData['num'];
            $data->cost = $inputData['cost'];
            foreach ((array)$data as $v) {
                if (!$v) {
                    return self::response(400, '请输入完整信息');
                }
            }
            $data->sort = $inputData['sort'];
            $id = (int)$inputData['id'];
            $routePoint = new Routepoint();
            if (isset($inputData['id']) && $id > 0) {
                $route->save($data, ['id' => $id]);
                $routePoint->where(['rid' => $id])->delete();
            } else {
                $route->data($data);
                $res = $route->save();
            }
            $id = $id ? $id : $route->id;
            $nears = array_filter(explode(',', $inputData['nears']));
            if ($nears) {
                $nearArr = [];
                foreach ($nears as $k => $value) {
                    if ($value && $id) {
                        $nearArr[$k]['pid'] = (int)$value;
                        $nearArr[$k]['rid'] = $id;
                    }
                }
                $routePoint->saveAll($nearArr);
            }
            if (isset($res) && !$res) {
                return self::response(400, '操作失败');
            }
            return self::response(0, '操作成功');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //删除推荐 路线
    public function routeDell($id)
    {
        try {
            Route::destroy(intval($id));
            return $this->routeList($this->request);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //推荐路线文物点设置
    public function routePointList($rid)
    {
        try {
            $client = new Routepoint();
            $list = $client->where(['rid' => (int)$rid])
                ->order('sort')
                ->select();
            $this->assign('list', $list);
            $this->assign('rid', $rid);
            $this->assign("title", "推荐路线文物点设置");
            return $this->fetch('boss/route/point/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //添加或者修改 推荐路线文物点
    public function routePointAddOrSave($id = null, $rid = null)
    {
        try {
            if ($id != null) {
                $data = Routepoint::get((int)$id);
                $this->assign('title', '修改 推荐路线文物点');
                $this->assign('rid', $data->rid);
                $this->assign('data', $data);
            } else {
                if ($rid != null) {
                    $this->assign('title', '添加 推荐路线文物点');
                    $this->assign('rid', (int)$rid);
                    $this->assign('data', null);
                }
            }
            return $this->fetch('boss/route/point/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function routePointSave(Request $request)
    {
        try {
            $inputData = $request->param('', null, 'htmlspecialchars');
            $banner = new Routepoint();
            $data = new \stdClass();
            $data->pid = $inputData['pid'];
            $data->rid = $inputData['rid'];
            foreach ((array)$data as $v) {
                if (!$v) {
                    return self::response(400, '请输入完整信息');
                }
            }
            $data->sort = $inputData['sort'];
            if (isset($inputData['id']) && (int)$inputData['id'] > 0) {
                $banner->save($data, ['id' => (int)$inputData['id']]);
            } else {
                $banner->data($data);
                $res = $banner->save();
            }
            if (isset($res) && !$res) {
                return self::response(400, '操作失败');
            }
            return self::response(0, '操作成功');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //删除
    public function routepointDell($id, $rid)
    {
        try {
            Routepoint::destroy((int)$id);
            return $this->routePointList((int)$rid);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 获取活动编辑列表
    public function actList(Request $request)
    {
        try {
            $page = intval($request->request('page')) ? intval($request->get('page')) : 1;
            $limit = 10;
            $client = new Act();
            $totalSize = $client->count();
            $totalPage = ceil($totalSize / $limit);
            if ($page >= $totalPage) {
                $page = $totalPage;
            }
            $list = $client->order('sort')->limit(($page - 1) * $limit, $limit)->select();
            $this->assign('page', $page);
            $this->assign('limit', $limit);
            $this->assign('totalPage', $totalPage);
            $this->assign('totalSize', $totalSize);
            $this->assign('list', $list);
            $this->assign("title", "活动列表");
            return $this->fetch('boss/act/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 活动编辑和添加
    public function actAddOrSave($id = null)
    {
        try {
            if ($id != null) {
                $data = Act::get((int)$id);
                $this->assign('title', "活动 修改");
                $this->assign('data', $data);
            } else {
                $this->assign('title', '活动 添加');
                $this->assign('data', null);
            }
            return $this->fetch('boss/act/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //保存 活动
    public function actSave(Request $request)
    {
        try {
            $inputData = $request->param('', null, 'htmlspecialchars');
            $banner = new Act();
            $data = new \stdClass();
            $data->name = $inputData['name'];
            $data->des = $inputData['des'];
            $data->zone = $inputData['zone'];
            $data->img = $inputData['img'];
            $data->link = $inputData['link'];
            foreach ($data as $v) {
                if (!$v) {
                    return self::response(400, '请输入必填信息');
                }
            }
            if (isset($inputData['id']) && (int)$inputData['id'] > 0) {
                $banner->save($data, ['id' => (int)$inputData['id']]);
            } else {
                $banner->data($data);
                $res = $banner->save();
            }
            if (isset($res) && !$res) {
                return self::response(400, '操作失败');
            }
            return self::response(0, '操作成功');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //删除 活动
    public function delSave($id)
    {
        try {
            Act::destroy(intval($id));
            return $this->actList($this->request);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 捐款列表
    public function donateList(Request $request)
    {
        if ($request->isAjax()) {
            $limit = $request->request('limit');
            $state = $request->request('state');
            $state = intval($state) ? 1 : 0;
            $limit = $limit ? intval($limit) : 10;
            $donate = new Donate();
            $list = $donate->where(['state' => $state])->order('sort asc,id desc')->paginate($limit);
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg = '';
            $response->data = array();
            if (!empty($list)) {
                $response->data = $list->items();
            }
            return json($response);
        }

        $this->assign('title', '捐款列表-' . $this->title);
        return $this->fetch('boss/donate/list');
    }

    // 捐款添加和编辑
    public function donateSave(Request $request)
    {
        if ($request->isPost()) {
            $response = new \stdClass();
            $response->code = 400;
            $response->data = '';
            $response->msg = '非法请求';
            $data = $request->post();
            if (empty($data)) {
                return json($response);
            }
            $donate = new Donate();
            unset($data['file']);
            if (isset($data['id']) && intval($data['id'])) {
                $data['id'] = intval($data['id']);
                $res = $donate->save($data, ['id' => $data['id']]);
            } else {
                $donate->data($data);
                $res = $donate->save();
            }
            if ($res) {
                $response->code = 0;
                $response->msg = '操作成功';
            } else {
                $response->code = 400;
                $response->msg = '操作失败';
            }
            return json($response);
        } else {
            $id = intval($request->param('id'));
            $detail = Donate::get($id);
            $this->assign('detail', $detail);
            $this->assign('title', '添加/编辑捐款项-' . $this->title);
            return $this->fetch('boss/donate/add');
        }

    }

    // 删除捐款
    public function donateState()
    {
        $id = intval($this->request->post('id'));
        if ($id <= 0) {
            return $this->response(400, '非法请求');
        }
        $donate = new Donate();
        $res = $donate->where('id', $id)->update(['state' => ['exp', '1-state']]);
        if ($res) {
            return $this->response(0, '操作成功');
        }
        return $this->response(400, '操作失败');
    }

    // 文件上传
    public function upload()
    {
        $response = [
            'code' => 400,
            'data' => [
                'src' => '',
                'title' => ''
            ],
            'msg' => '非法请求'
        ];
        if (!Request::instance()->isPost()) {
            return json($response);
        }
        $type = Request::instance()->param('type', '', 'htmlspecialchars');
        if (!$type) {
            $type = '';
        }
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $type);
        if ($info) {
            // 成功上传后 获取上传信息
            $response['code'] = 0;
            if ($type) {
                $response['data']['src'] = '/uploads' . DS . $type . DS . $info->getSaveName();
            } else {
                $response['data']['src'] = '/uploads' . DS . $info->getSaveName();
            }
            $response['data']['title'] = $info->getFilename();
            $response['msg'] = '上传成功';
        } else {
            //上传失败
            $response['code'] = 400;
            $response['data'] = $file->getError();
            $response['msg'] = '上传失败';
        }
        return json($response);
    }

    // 异步返回
    private function response($code, $msg = '', $data = [])
    {
        $response = new \stdClass();
        $response->code = $code;
        $response->data = $data;
        $response->msg = $msg;
        return json($response);
    }

    // 产品列表
    public function productList(Request $request)
    {
        if ($request->isAjax()) {
            $limit = $request->request('limit');
            $state = $request->request('state');
            $state = intval($state) ? 1 : 0;
            $limit = $limit ? intval($limit) : 10;
            $product = new Product();
            try {
                $list = $product->where(['state' => $state])->order('id desc')->paginate($limit);
            } catch (DbException $e) {
                return $e->getMessage();
            }
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg = '';
            $response->data = array();
            if (!empty($list)) {
                $response->code = 0;
                $response->data = $list->items();
            }
            return json($response);
        }
        $this->assign('title', '文创产品列表-' . $this->title);
        return $this->fetch('boss/product/list');
    }

    // 产品添加和编辑
    public function productSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post('', null, 'htmlspecialchars');
            if (empty($data)) {
                return $this->response(400, '非法请求');
            }
            $product = new Product();
            $productContent = new ProductContent();
            $productImg = new ProductImg();
            $content = $_POST['content'];
            $imgs = $data['imgs'];
            unset($data['file'], $data['content'], $data['imgs']);
            if (isset($data['id']) && intval($data['id'])) { // 修改
                $data['id'] = intval($data['id']);
                $res = $product->save($data, ['id' => $data['id']]);
                if ($res) {
                    $res = $productContent->save(['content' => $content], ['id' => $data['id']]);
                    $productImg->where(['pro_id' => $data['id']])->delete();
                }
            } else {              //添加
                $product->data($data);
                $res = $product->save();
                if ($res) {
                    $productContent->data(['id' => $product->id, 'content' => $content]);
                    $res = $productContent->save();
                }
            }
            if ($res) {
                $imgs = array_filter(explode('|', $imgs));
                if ($imgs) {
                    $imgArr = [];
                    foreach ($imgs as $k => $value) {
                        $imgArr[$k]['pro_id'] = $product->id;
                        $imgArr[$k]['img_path'] = $value;
                    }
                    $productImg->saveAll($imgArr);
                }
                return $this->response(0, '操作成功');
            } else {
                return $this->response(400, '操作失败');
            }
        } else {
            $id = intval($request->param('id'));
            $product = Product::get($id);
            $productContent = ProductContent::get($id);
            $productImg = new ProductImg();
            $productImgs = $productImg->where(['pro_id' => $id])->select();
            $this->assign('product', $product);
            $this->assign('productContent', $productContent);
            $this->assign('productImgs', $productImgs);
            $productImgsStr = '';
            if ($productImgs) {
                foreach ($productImgs as $value) {
                    $productImgsStr .= '|' . $value->img_path;
                }
            }
            $this->assign('productImgsStr', $productImgsStr);
            $this->assign('title', '添加/修改产品-' . $this->title);
            return $this->fetch('boss/product/add');
        }

    }

    // 上架 下架 产品
    public function productState()
    {
        $id = intval($this->request->post('id'));
        if ($id <= 0) {
            return $this->response(400, '非法请求');
        }
        $product = new Product();
        $res = $product->where('id', $id)->update(['state' => ['exp', '1-state']]);
        if ($res) {
            return $this->response(0, '操作成功');
        }
        return $this->response(400, '操作失败');
    }

    // 删除产品图片
    public function productImgDel($id = 0)
    {
        $id = intval($id);
        if ($id < 0) {
            return $this->response(400, '删除失败');
        }
        $res = ProductImg::destroy($id);
        if ($res) {
            return $this->response(0, '删除成功');
        } else {
            return $this->response(400, '删除失败');
        }
    }

    // 网站公告管理
    public function newsList(Request $request)
    {
        if ($request->isAjax()) {
            $limit = $request->request('limit');
            $limit = $limit ? intval($limit) : 10;
            $news = new News();
            $list = $news->order('id desc')->paginate($limit);
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg = '';
            $response->data = array();
            if (!empty($list)) {
                $response->code = 0;
                $response->data = $list->items();
            }
            return json($response);
        }
        $this->assign('title', '公告管理-' . $this->title);
        return $this->fetch('boss/news/list');
    }

    // 网站公告添加和编辑
    public function newsSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post('', null, 'htmlspecialchars');
            if (empty($data)) {
                return $this->response(400, '非法请求');
            }
            $news = new News();
            unset($data['file']);
            $res = true;
            if (isset($data['id']) && intval($data['id'])) { // 修改
                $data['id'] = intval($data['id']);
                $news->save($data, ['id' => $data['id']]);
            } else {              //添加
                $news->data($data);
                $res = $news->save();
            }
            if ($res) {
                return $this->response(0, '操作成功');
            } else {
                return $this->response(400, '操作失败');
            }
        } else {
            $id = intval($request->param('id'));
            $news = News::get($id);
            $this->assign('news', $news);
            $this->assign('title', '添加/修改公告-' . $this->title);
            return $this->fetch('boss/news/add');
        }
    }

    // 删除公告
    public function newsDel($id)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->response(400, '非法请求');
        }
        if (!Request::instance()->isAjax()) {
            return $this->response(400, '非法请求');
        }
        $res = News::destroy($id);
        if (!$res) {
            return $this->response(400, '删除失败');
        }
        return $this->response(0, '删除成功');
    }

    //后台反馈列表
    public function InspectList(Request $request)
    {
        if ($request->isAjax()) {
            $limit = $request->request('limit');
            $limit = $limit ? intval($limit) : 10;
            $news = new Inspect();
            $prefix = config("database.prefix");
            $list = $news->alias('a')->join($prefix . 'user b',
                'a.uid = b.id',
                'LEFT')->order('a.id desc')->field('a.id,a.pid,a.des,a.state,b.nickname,b.headimgurl')->paginate($limit);
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg = '';
            $response->data = array();
            if (!empty($list)) {
                $response->code = 0;
                $response->data = $list->items();
            }
            return json($response);
        }
        $this->assign('title', '反馈管理-' . $this->title);
        return $this->fetch('boss/inspect/list');
    }

    //添加反馈
    public function inspectSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post('', null, 'htmlspecialchars');
            if (empty($data)) {
                return $this->response(400, '非法请求');
            }
            // 判断志愿者id 和 文物点id
            $member = Member::get(['uid' => (int)$data['uid'], 'state' => 1]);
            $point = Point::get(['id' => (int)$data['pid']]);
            if (empty($member)) {
                return $this->response(400, '志愿者：' . $data['uid'] . '，不存在');
            }
            if (empty($point)) {
                return $this->response(400, '文物点：' . $data['uid'] . '，不存在');
            }
            $news = new Inspect();
            unset($data['file'], $data['content'], $data['imgs']);
            $res = true;
            if (isset($data['id']) && intval($data['id'])) { // 修改
                $data['id'] = intval($data['id']);
                $news->save($data, ['id' => $data['id']]);
            } else {              //添加
                $news->data($data);
                $res = $news->save();
            }
            if ($res) {
                return $this->response(0, '操作成功');
            } else {
                return $this->response(400, '操作失败');
            }
        } else {
            $id = intval($request->param('id'));
            $news = Inspect::get($id);
            $this->assign('inspect', $news);
            $this->assign('title', '添加/修改反馈-' . $this->title);
            return $this->fetch('boss/inspect/add');
        }
    }

    public function inspectPass(Request $request)
    {
        if (!$request->isAjax()) {
            return self::response(400, '非法请求');
        }
        $id = (int)$request->post('id');
        if (!$id) {
            return self::response(400, '非法请求');
        }
        $inspectObj = new Inspect();
        $res = $inspectObj->save(['state' => ['exp', '1-state']], ['id' => $id]);
        if (!$res) {
            return self::response(400, '操作失败');
        }
        return self::response(0, '操作成功');
    }

    public function inspectDel($id)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->response(400, '非法请求');
        }
        if (!Request::instance()->isAjax()) {
            return $this->response(400, '非法请求');
        }
        $res = Inspect::destroy($id);
        if (!$res) {
            return $this->response(400, '删除失败');
        }
        return $this->response(0, '删除成功');
    }

    public function about(Request $request)
    {
        try {
            $config = new Config();
            $about = $config->find(1);
            if ($request->isAjax()) {
                $data = $request->param();
                unset($data['file']);
                $res = true;
                if ($about == null) {
                    $res = $config->data($data)->save();
                } else {
                    $config->where(['id' => (int)$data['id']])->update($data);
                }
                if (!$res) {
                    return $this->response(400, '操作失败');
                }
                return $this->response(0, '操作成功');
            }
            $this->assign('about', $about);
            $this->assign('title', '关于我们' . $this->title);
            return $this->fetch('boss/about/index');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 培训分类列表
    public function cateList(Request $request)
    {
        if ($request->isAjax()) {
            $limit = $request->request('limit');
            $limit = $limit ? intval($limit) : 10;
            $trainCate = new TrainCate();
            $list = $trainCate->order('id desc')->paginate($limit);
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg = '';
            $response->data = array();
            if (!empty($list)) {
                $response->code = 0;
                $response->data = $list->items();
            }
            return json($response);
        }
        $this->assign('title', '培训分类管理-' . $this->title);
        return $this->fetch('boss/train/cate_list');
    }

    // 培训分类添加和编辑
    public function cateSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post();
            unset($data['file']);
            if (empty($data)) {
                return $this->response(400, '非法请求');
            }
            $trainCate = new TrainCate();
            $res = true;
            if (isset($data['id']) && intval($data['id'])) { // 修改
                $data['id'] = intval($data['id']);
                $trainCate->save($data, ['id' => $data['id']]);
            } else {              //添加
                $trainCate->data($data);
                $res = $trainCate->save();
            }
            if ($res) {
                return $this->response(0, '操作成功');
            } else {
                return $this->response(400, '操作失败');
            }
        } else {
            $id = intval($request->param('id'));
            $cate = TrainCate::get($id);
            $this->assign('cate', $cate);
            $this->assign('title', '添加/修改培训分类-' . $this->title);
            return $this->fetch('boss/train/cate_add');
        }
    }

    // 删除培训分类
    public function cateDel($id)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->response(400, '非法请求');
        }
        if (!Request::instance()->isAjax()) {
            return $this->response(400, '非法请求');
        }
        $trainContent = new TrainContent();
        $trains = $trainContent->where(['cate_id' => $id])->find();
        if ($trains) {
            return $this->response(400, '请先删除此分类下的内容');
        }
        $res = TrainCate::destroy($id);
        if (!$res) {
            return $this->response(400, '删除失败');
        }
        return $this->response(0, '删除成功');
    }

    // 培训内容列表
    public function trainList(Request $request)
    {
        if ($request->isAjax()) {
            $limit = $request->request('limit');
            $limit = $limit ? intval($limit) : 10;
            $train = new TrainContent();
            $list = $train->alias('a')->order('a.id desc')->join('ly_train_cate b', 'a.cate_id = b.id',
                'LEFT')->paginate($limit);
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg = '';
            $response->data = array();
            if (!empty($list)) {
                $response->code = 0;
                $response->data = $list->items();
            }
            return json($response);
        }
        $this->assign('title', '培训管理-' . $this->title);
        return $this->fetch('boss/train/list');
    }

    // 培训添加和编辑
    public function trainSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post();
            if (empty($data)) {
                return $this->response(400, '非法请求');
            }
            $train = new TrainContent();
            unset($data['file']);
            $res = true;
            if (isset($data['id']) && intval($data['id'])) { // 修改
                $data['id'] = intval($data['id']);
                $train->save($data, ['id' => $data['id']]);
            } else {              //添加
                $train->data($data);
                $res = $train->save();
            }
            if ($res) {
                return $this->response(0, '操作成功');
            } else {
                return $this->response(400, '操作失败');
            }
        } else {
            $id = intval($request->param('id'));
            $train = TrainContent::get($id);
            $cates = TrainCate::all();
            $this->assign('cates', $cates);
            $this->assign('train', $train);
            $this->assign('title', '添加/修改培训-' . $this->title);
            return $this->fetch('boss/train/add');
        }
    }

    // 删除培训
    public function trainDel($id)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->response(400, '非法请求');
        }
        if (!Request::instance()->isAjax()) {
            return $this->response(400, '非法请求');
        }
        $res = TrainCate::destroy($id);
        if (!$res) {
            return $this->response(400, '删除失败');
        }
        return $this->response(0, '删除成功');
    }

    // 证书列表
    public function certList(Request $request)
    {
        if ($request->isAjax()) {
            $limit = $request->request('limit');
            $limit = $limit ? intval($limit) : 10;
            $cert = new Cert();
            $list = $cert->order('sort')->paginate($limit);
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg = '';
            $response->data = array();
            if (!empty($list)) {
                $response->code = 0;
                $response->data = $list->items();
            }
            return json($response);
        }
        $this->assign('title', '证书管理-' . $this->title);
        return $this->fetch('boss/cert/list');
    }

    // 保存证书
    public function certSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post();
            if (empty($data)) {
                return $this->response(400, '非法请求');
            }
            $cert = new Cert();
            unset($data['file']);
            if (isset($data['id']) && intval($data['id'])) { // 修改
                $data['id'] = intval($data['id']);
                $res = $cert->save($data, ['id' => $data['id']]);
            } else {              //添加
                $cert->data($data);
                $res = $cert->save();
            }
            if ($res) {
                return $this->response(0, '操作成功');
            } else {
                return $this->response(400, '操作失败');
            }
        } else {
            $id = intval($request->param('id'));
            $cert = Cert::get($id);
            $this->assign('cert', $cert);
            $this->assign('title', '添加/修改证书-' . $this->title);
            return $this->fetch('boss/cert/add');
        }
    }

    // 删除证书
    public function certDel($id)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->response(400, '非法请求');
        }
        if (!Request::instance()->isAjax()) {
            return $this->response(400, '非法请求');
        }
        $res = Cert::destroy($id);
        if (!$res) {
            return $this->response(400, '删除失败');
        }
        return $this->response(0, '删除成功');
    }

    // 颁发/取消 证书
    public function certAward(Request $request)
    {
        if (!$request->isAjax() || !$request->isPost()) {
            return $this->response(400, '非法请求');
        }
        $option = intval($request->param('option'));
        $uid = intval($request->param('uid'));
        $certId = intval($request->param('certId'));
        if (!isset($option) || !$uid || !$certId) {
            return $this->response(400, '非法请求');
        }
        $data = [
            'uid' => $uid,
            'cert_id' => $certId
        ];
        if (Member::get(['uid' => $uid, 'state' => 1]) == null) {
            return $this->response(400, '志愿者不存在');
        }
        $certRecords = new CertRecords();
        if ($option == 0) { // 取消证书
            $msg = '取消';
            $res = $certRecords->where($data)->delete();
        } else {              //添加
            if ($certRecords->where($data)->find()) {
                return $this->response(400, '已经颁发过证书');
            }
            $msg = '颁发';
            $certRecords->data($data);
            try {
                $res = $certRecords->save();
            } catch (\Exception $e) {
                return $this->response(400, $e->getMessage());
            }
        }
        if (!$res) {
            return $this->response(400, $msg . '失败');
        }
        return $this->response(0, $msg . '成功');
    }

    // 已颁发证书列表
    public function certAwardList(Request $request)
    {
        $certRecordsObj = new CertRecords();
        $prefix = config("database.prefix");
        $page = intval($request->request('page')) ? intval($request->get('page')) : 1;
        $limit = 10;
        $totalSize = $certRecordsObj->count();
        $totalPage = ceil($totalSize / $limit);
        if ($page >= $totalPage) {
            $page = $totalPage;
        }
        $list = null;
        try {
            $list = $certRecordsObj->alias('a')->order('a.id DESC')->join($prefix . 'member b', 'a.uid = b.uid',
                'LEFT')->join($prefix . 'cert c', 'a.cert_id = c.id',
                'LEFT')->field('a.id,a.cert_id,a.create_time,b.uid,b.name,c.img,c.num,c.des')->limit(($page - 1) * $limit,
                $limit)->select();
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        $this->assign('page', $page);
        $this->assign('limit', $limit);
        $this->assign('totalPage', $totalPage);
        $this->assign('totalSize', $totalSize);
        $this->assign("title", "证书颁发记录");
        $this->assign('list', $list);
        return $this->fetch('boss/cert/awardlist');
    }

    public function nearList(Request $request)
    {
        $pid = $request->param('pid');
        $pid = $pid ? intval($pid) : 0;
        if ($request->isAjax()) {
            $response = new \stdClass();
            $response->code = 400;
            $response->count = 0;
            $response->msg = '';
            $response->data = array();
            if (!$pid) {
                return json($response);
            }
            $limit = $request->param('limit');
            $limit = $limit ? intval($limit) : 10;
            $cert = new Pointnear();
            $list = $cert->where(['pid' => $pid])->paginate($limit);
            $response->count = $list->total();
            if (!empty($list)) {
                $response->code = 0;
                $response->data = $list->items();
            }
            return json($response);
        }
        $this->assign('pid', $pid);
        $this->assign('title', '附近管理-' . $this->title);
        return $this->fetch('boss/near/list');
    }

    public function nearSave(Request $request)
    {
        $pid = intval($request->param('pid'));
        if ($request->isAjax()) {
            $data = $request->post();
            //这里在post请求的时候不一定有pid值
            if (empty($data)) {
                return $this->response(400, '非法请求');
            }
            $cert = new Pointnear();
            unset($data['file']);
            if (isset($data['id']) && intval($data['id'])) { // 修改
                $data['id'] = intval($data['id']);
                $cert->save($data, ['id' => $data['id']]);
            } else {              //添加
                $cert->data($data);
                $res = $cert->save();
            }
            if (isset($res) && !$res) {
                return $this->response(400, '操作失败');
            }
            return $this->response(0, '操作成功');
        } else {
            $id = intval($request->param('id'));
            $cert = Pointnear::get($id);
            $this->assign('near', $cert);
            //zzh fix object problem
            //$cert_array = (array)$cert;
            //if (isset($cert_array['pid'])){
            //    $pid = $cert->pid;
            //}
            $pid = isset($cert->pid) ? $cert->pid : $pid;
            $this->assign('pid', $pid);
            $this->assign('title', '添加/修改附近文物点-' . $this->title);
            return $this->fetch('boss/near/add');
        }
    }

    public function nearDel($id)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->response(400, '非法请求');
        }
        if (!Request::instance()->isAjax()) {
            return $this->response(400, '非法请求');
        }
        $res = Pointnear::destroy($id);
        if (!$res) {
            return $this->response(400, '删除失败');
        }
        return $this->response(0, '删除成功');
    }

    /**
     * 修改密码
     * @param Request $request
     * @return mixed|\think\response\Json
     * @throws \think\exception\DbException
     */
    public function modify(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post();
            $result = $this->validate(
                $data,
                [
                    'oldpasswd' => 'require|min:6',
                    'passwd' => 'require|min:6|different:oldpasswd',
                    'repasswd' => 'require|min:6|confirm:passwd'
                ],
                [
                    'oldpasswd.require' => '旧密码不能为空',
                    'oldpasswd.min' => '旧密码长度不能小于6',
                    'passwd.require' => '新密码不能为空',
                    'passwd.min' => '新密码长度不能小于6',
                    'passwd.different' => '新旧密码不能相同~',
                    'repasswd.require' => '确认密码不能为空',
                    'repasswd.min' => '确认密码长度不能小于6',
                    'repasswd.confirm' => '两次密码输入不一致',
                ]
            );
            if (true !== $result) {
                // 验证失败 输出错误信息
                return self::response(400, $result);
            }
            $admin = Admin::get($this->admin[0]);
            if (empty($admin)) {
                return self::response(400, '管理员不存在~');
            }
            if ($admin->passwd != sha1(md5($data['oldpasswd']))) {
                return self::response(400, '旧密码错误~');
            }
            if ($admin->passwd == sha1(md5($data['passwd']))) {
                return self::response(400, '新旧密码不能相同~');
            }
            $admin->passwd = sha1(md5($data['passwd']));
            $res = $admin->save();
            if (!$res) {
                return self::response(400, '密码修改失败');
            }
            Session::set('admin', null);
            return self::response(0, '密码修改成功');
        }
        $this->assign('title', '修改密码-' . $this->title);
        return $this->fetch('boss/login/passwd');
    }

    /**
     * 产品订单
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function proOrder(Request $request)
    {
        $prefix = config("database.prefix");
        $page = intval($request->request('page')) ? intval($request->get('page')) : 1;
        $limit = 10;
        $op = $request->param('op');

        // 根据状态查询订单
        $state = $request->request('state') ? intval($request->request('state')) : 0;
        $orderObj = new Order();
        $totalSize = $orderObj->where(['is_paied' => $state])->count();
        $totalPage = ceil($totalSize / $limit);
        if ($page >= $totalPage) {
            $page = $totalPage;
        }
        $orderList = $orderObj->where(['is_paied' => $state])->order('id DESC')->field('order_no,cost,goods_price,total_price,is_paied,name,phone,address,option,transaction_id,create_time')->limit(($page - 1) * $limit,
            $limit)->select();
        if ($orderList) {
            foreach ($orderList as $order) {
                $orderItemObj = new OrderItem();
                $orderItems = $orderItemObj->alias('a')->order('a.id DESC')->join($prefix . 'product b',
                    'a.pro_id = b.id',
                    'LEFT')->field('a.count,a.price,a.pro_id,b.name,b.img')->where(['a.order_no' => $order->order_no])->select();
                $order->orderItems = $orderItems;
            }
        }
        if ($op === 'export' && $orderList) {
            $srvExcel = new Excel();
            $srvExcel->has_title = false;
            $srvExcel->file_name = '订单信息-第' . $page . '页';
            $exportData = collection($orderList)->toArray() ?: [];
            $data[] = [
                '订单号',
                '订单状态',
                '产品id',
                '产品名',
                '产品下单价',
                '产品数量',
                '运费总价',
                '产品总价',
                '订单总价',
                '订单姓名',
                '订单电话',
                '订单地址',
                '订单时间',
                '微信交易单号',
                '备注'
            ];
            if (!empty($exportData)) {
                foreach ($exportData as $key => $export) {
                    $export = [];
                    $export['order_no'] = $exportData[$key]['order_no'];
                    $export['is_paied'] = $exportData[$key]['is_paied'] == 1 ? '已付款' : '未付款';
                    $productIdArr = $productNameArr = $productPriceArr = $productNumArr = [];
                    foreach ($exportData[$key]['orderItems'] as $product) {
                        $productIdArr[] = $product['pro_id'];
                        $productNameArr[] = $product['name'];
                        $productPriceArr[] = $product['price'];
                        $productNumArr[] = $product['count'];
                    }
                    $export['productId'] = implode('/', $productIdArr);
                    $export['product'] = implode('/', $productNameArr);
                    $export['price'] = implode('/', $productPriceArr);
                    $export['num'] = implode('/', $productNumArr);
                    $export['cost'] = $exportData[$key]['cost'];
                    $export['goods_price'] = $exportData[$key]['goods_price'];
                    $export['total_price'] = $exportData[$key]['total_price'];
                    $export['name'] = $exportData[$key]['name'];
                    $export['phone'] = $exportData[$key]['phone'];
                    $export['address'] = $exportData[$key]['address'];
                    $export['create_time'] = $exportData[$key]['create_time'];
                    $export['transaction_id'] = $exportData[$key]['transaction_id'];
                    $export['option'] = $exportData[$key]['option'];
                    $data[] = array_values($export);
                }
            }
            unset($exportData);
            return $srvExcel->exportExcel($data, array_pad(array(), 15, 's'));
        }
        $this->assign('page', $page);
        $this->assign('limit', $limit);
        $this->assign('totalPage', $totalPage);
        $this->assign('totalSize', $totalSize);
        $this->assign('orderList', $orderList);
        $this->assign('title', '产品订单-' . $this->title);
        $this->assign('state', $state);
        return $this->fetch('boss/order/product');
    }

    /**
     * 活动报名
     * @param Request $request
     * @return string|\think\response\Json
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function actOrder(Request $request)
    {
        $isfree = $request->request('isfree') ? 1 : 0;
        if ($request->isAjax()) {
            $prefix = config("database.prefix");
            $limit = $request->request('limit');
            $limit = $limit ? intval($limit) : 10;
            $actRecordsObj = new ActRecords();
            if ($isfree) {  // 免费活动
                $list = $actRecordsObj->alias('a')->order('a.id desc')->join($prefix . 'act b', 'a.act_id = b.id',
                    'LEFT')->field('a.id,a.act_id,a.order_no,a.need_pay,a.price,a.name as user,a.phone,a.transaction_id,a.create_time,b.name')->where(['a.need_pay' => 0])->paginate($limit);
            } else {          // 付费
                $list = $actRecordsObj->alias('a')->order('a.id desc')->join($prefix . 'act b', 'a.act_id = b.id',
                    'LEFT')->field('a.id,a.act_id,a.order_no,a.need_pay,a.price,a.name as user,a.phone,a.transaction_id,a.create_time,b.name')->where([
                    'a.is_paied' => 1,
                    'a.need_pay' => 1
                ])->paginate($limit);
            }
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg = '';
            $response->data = array();
            if (!empty($list)) {
                $response->code = 0;
                $response->data = $list->items();
            }
            return json($response);
        }
        $this->assign('title', '活动报名-' . $this->title);
        $this->assign('isfree', $isfree);
        return $this->fetch('boss/order/act');
    }

    /**
     * 捐款信息
     * @param Request $request
     * @return string|\think\response\Json
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function donOrder(Request $request)
    {
        if ($request->isAjax()) {
            $prefix = config("database.prefix");
            $limit = $request->request('limit');
            $limit = $limit ? intval($limit) : 10;
            $donateRecordsObj = new DonateRecords();
            $list = $donateRecordsObj->alias('a')->
            order('a.id desc')->
            join($prefix . 'donate b', 'a.donate_id = b.id', 'LEFT')->
            join($prefix . 'user c', 'c.id = a.user_id', 'LEFT')->
            field('a.id,a.donate_id,a.order_no,a.money,a.transaction_id,a.create_time,b.name,c.nickname')->
            where(['a.is_paied' => 1])->paginate($limit);
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg = '';
            $response->data = array();
            if (!empty($list)) {
                $response->code = 0;
                $response->data = $list->items();
            }
            return json($response);
        }
        $this->assign('title', '捐款信息-' . $this->title);
        return $this->fetch('boss/order/donate');
    }

    /**
     * 添加备注信息
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function addOrderAttention(Request $request)
    {
        if (!$request->isAjax()) {
            return self::response(400, '非法请求~');
        }
        $orderNo = $request->post('order_no', '', 'htmlspecialchars');
        $option = $request->post('option', '', 'htmlspecialchars');
        if (!$orderNo || !$option) {
            return self::response(400, '非法请求');
        }
        $order = Order::get(['order_no' => $orderNo]);
        $order->option = $option;
        $res = $order->save();
        if (!$res) {
            return self::response(400, '添加备注失败');
        }
        return self::response(0, '添加成功');
    }

    /**
     * 报名信息
     * @param Request $request
     * @return string|\think\response\Json
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function registerList(Request $request)
    {
        $state = $request->request('state') ? 1 : 0;
        $limit = $request->request('limit');
        $limit = $limit ? intval($limit) : 10;
        $page = (int)$request->request('page') ?: 1;
        $op = $request->param('op');

        if ($request->isAjax() || $op === 'export') {
            $memberObj = new Member();
            $list = $memberObj->order('id desc')->where(['`state`' => $state])->field('id,uid,name,gender,id_cards,email,phone,career,reason,from,state,create_time')->paginate($limit);
            if ($op === 'export') {
                $srvExcel = new Excel();
                $srvExcel->has_title = false;
                $srvExcel->file_name = '志愿者信息-第' . $page . '页';
                $exportData = $list->toArray()['data'] ?: [];
                if (!empty($exportData)) {
                    foreach ($exportData as $key => $export) {
                        $export['state'] = $export['state'] == 1 ? '是' : '否';
                        $export['gender'] = $export['gender'] == 1 ? '男' : '女';
                        unset($exportData[$key], $export['id']);
                        $exportData[$key] = array_values($export);
                    }
                }
                $data = array(
                    array('志愿者id', '姓名', '性别', '身份证', '邮箱', '电话', '职业', '申请原因', '申请来源', '审核状态', '申请时间')
                );
                return $srvExcel->exportExcel(array_merge($data, $exportData), array_pad(array(), 5, 's'));
            }
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg = '';
            $response->data = array();
            if (!empty($list)) {
                $response->code = 0;
                $response->data = $list->items();
            }
            return json($response);
        }
        $this->assign('state', $state);
        $this->assign('page', $page);
        $this->assign('limit', $limit);
        $this->assign('title', '注册信息-' . $this->title);
        return $this->fetch('boss/register/list');
    }

    /**
     * 修改注册审核状态
     * @param Request $request
     * @return \think\response\Json
     */
    public function registerPass(Request $request)
    {
        if (!$request->isAjax()) {
            return self::response(400, '非法请求');
        }
        $id = (int)$request->post('id');
        if (!$id) {
            return self::response(400, '非法请求');
        }
        $memberObj = new Member();
        $res = $memberObj->save(['state' => ['exp', '1-state']], ['id' => $id]);
        if (!$res) {
            return self::response(400, '操作失败');
        }
        return self::response(0, '操作成功');
    }

    public function _empty()
    {
        $this->redirect('/boss/index');
    }

    // 文物点区域管理
    public function zoneList(Request $request)
    {
        try {
            $page = intval($request->request('page')) ? intval($request->get('page')) : 1;
            $limit = 10;
            $client = new Zone();
            $totalSize = $client->count();
            $totalPage = ceil($totalSize / $limit);
            if ($page >= $totalPage) {
                $page = $totalPage;
            }
            $list = $client->order('id DESC')->limit(($page - 1) * $limit, $limit)->select();
            $this->assign('page', $page);
            $this->assign('limit', $limit);
            $this->assign('totalPage', $totalPage);
            $this->assign('totalSize', $totalSize);
            $this->assign('list', $list);
            $this->assign('title', '区域管理-' . $this->title);
            return $this->fetch('boss/zone/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 区域添加和编辑
    public function zoneSave(Request $request)
    {
        try {
            if ($request->isAjax()) {
                $data = $request->post('', null, 'htmlspecialchars');
                if (empty($data)) {
                    return $this->response(400, '非法请求');
                }
                $zone = new Zone();
                $res = true;
                unset($data['file']);
                if (isset($data['id']) && intval($data['id'])) { // 修改
                    $data['id'] = intval($data['id']);
                    $zone->save($data, ['id' => $data['id']]);
                } else {              //添加
                    $zone->data($data);
                    $res = $zone->save();
                }
                if ($res) {
                    return $this->response(0, '操作成功');
                } else {
                    return $this->response(400, '操作失败');
                }
            } else {
                $id = intval($request->param('id'));
                $zone = Zone::get($id);
                $this->assign('zone', $zone);
                $this->assign('title', '添加/修改区域-' . $this->title);
                return $this->fetch('boss/zone/add');
            }
        } catch (DbException $e) {
            return $e->getMessage();
        }
    }
}
