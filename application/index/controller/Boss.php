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
use think\Controller;
use think\Exception;
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
        if (empty($admin)){
            if ($request->isAjax()){
                return self::response(400,'请先登录~');
            }else{
                $this->redirect('/boss/login');
            }
        }
        $this->view = new View();
        $this->title = Config::get('boss_title');
        $this->admin = explode('|',$admin); // [id, name]
        if (!$request->isAjax()){
            $this->assign('admin',$this->admin);
            $this->assign('pathInfo',$_SERVER['PATH_INFO']);
        }
    }
    //boss后台设计
    public function index()
    {
        $this->assign('title',$this->title);
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
            return $this->view->fetch('boss/banner/newlist');
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
            return $this->view->fetch('boss/banner/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //banner 图片保存加载一条龙图片
    public function bannerSave(Request $request)
    {
        try {
            $inputData = $request->param('',null,'htmlspecialchars');
            $banner = new Banner();
            $data = new \stdClass();
            $data->img = $inputData['img'];
            $data->des = $inputData['des'];
            $data->url = $inputData['url'];
            foreach ((array)$data as $v){
                if (!$v){
                    return self::response(400,'请输入完整信息');
                }
            }
            $data->sort = $inputData['sort'];
            if (isset($inputData['id']) && intval($inputData['id']) > 0) {
                $banner->save($data, ['id' => intval($inputData['id'])]);
            } else {
                $banner->data($data);
                $res = $banner->save();
            }
            if(isset($res) && !$res){
                return self::response(400,'操作失败');
            }
            return self::response(0,'操作成功');
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
            $page = intval($request->request('page')) ? intval($request->get('page')) : 1;
            $limit = 10;
            $pointObj = new Point();
            $totalSize = $pointObj->count();
            $totalPage = ceil($totalSize / $limit);
            if ($page >= $totalPage){
                $page = $totalPage;
            }
            $list = $pointObj->order('sort')->limit(($page-1)*$limit, $limit)->select();
            $this->assign('page', $page);
            $this->assign('limit', $limit);
            $this->assign('totalPage', $totalPage);
            $this->assign('totalSize', $totalSize);
            $this->assign("title","文物点列表");
            $this->assign('list', $list);
            return $this->view->fetch('boss/point/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function pointAddOrModify($id = null)
    {
        try {
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
            return $this->view->fetch('boss/point/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //  文物点保存加载一条龙图片
    public function pointSave(Request $request)
    {
        try {
            $inputData = $request->param('',null,'htmlspecialchars');
            $banner = new Point();
            $data = new \stdClass();
            $data->img = $inputData['img'];
            $data->name = $inputData['name'];
            $data->addr = $inputData['addr'];
            $data->level = $inputData['level'];
            $data->zone = $inputData['zone'];
            foreach ((array)$data as $v){
                if (!$v){
                    return self::response(400,'请输入完整信息');
                }
            }
            $data->sort = $inputData['sort'];
            if (isset($inputData['id']) && (int)$inputData['id'] > 0) {
                $banner->save($data, ['id' => (int)$inputData['id']]);
            } else {
                $banner->data($data);
                $res = $banner->save();
            }
            if(isset($res) && !$res){
                return self::response(400,'操作失败');
            }
            return self::response(0,'操作成功');
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
            $this->assign("title","文物点详情");
            return $this->view->fetch('boss/point/detail');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //文物点详情保存
    public function pointDetailSave(Request $request)
    {
        try {
            $inputData = $request->param('',null,'htmlspecialchars');
            $client = new Pointdetail();
            $data = new \stdClass();
            $data->des = $inputData['des'];
            $data->x = $inputData['x'];
            $data->y = $inputData['y'];
            foreach ((array)$data as $v){
                if (!$v){
                    return self::response(400,'请输入完整信息');
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
            if(isset($res) && !$res){
                return self::response(400,'操作失败');
            }
            return self::response(0,'操作成功');
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
            $this->assign("title","banner列表");
            $this->assign('list', $res);
            $this->assign('pid', $id);
            return $this->view->fetch('boss/point/banner/list');
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
            return $this->view->fetch('boss/point/banner/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 添加或保存
    public function pointBannerSave(Request $request)
    {
        try {
            $inputData = $request->param('',null,'htmlspecialchars');
            $banner = new Pointbanner();
            $data = new \stdClass();
            $data->img = $inputData['img'];
            $data->des = $inputData['des'];
            $data->url = $inputData['url'];
            $data->pid = $inputData['pid'];
            foreach ((array)$data as $v){
                if (!$v){
                    return self::response(400,'请输入完整信息');
                }
            }
            $data->sort = $inputData['sort'];
            if (isset($inputData['id']) && (int)$inputData['id'] > 0) {
                $banner->save($data, ['id' => (int)$inputData['id']]);
            } else {
                $banner->data($data);
                $res = $banner->save();
            }
            if(isset($res) && !$res){
                return self::response(400,'操作失败');
            }
            return self::response(0,'操作成功');
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
            if ($page >= $totalPage){
                $page = $totalPage;
            }
            $list = $client->order('sort')->limit(($page-1)*$limit, $limit)->select();
            $this->assign('page', $page);
            $this->assign('limit', $limit);
            $this->assign('totalPage', $totalPage);
            $this->assign('totalSize', $totalSize);
            $this->assign('list', $list);
            $this->assign("title","推荐路线");
            return $this->view->fetch('boss/route/list');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //推荐路线 添加或者报错
    public function routeAddOrSave($id = null)
    {
        try {
            //
            if ($id != null) {
                $data = Route::get((int)$id);
                $this->assign('title', "推荐路线修改");
                $this->assign('data', $data);
            } else {
                $this->assign('title', '推荐路线添加');
                $this->assign('data', null);
            }
            return $this->view->fetch('boss/route/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //ajax 保存推荐路线
    public function routeSave(Request $request)
    {
        try {
            $inputData = $request->param('',null,'htmlspecialchars');
            $banner = new Route();
            $data = new \stdClass();
            $data->img = $inputData['img'];
            $data->des = $inputData['des'];
            $data->name = $inputData['name'];
            $data->num = $inputData['num'];
            $data->cost = $inputData['cost'];
            foreach ((array)$data as $v){
                if (!$v){
                    return self::response(400,'请输入完整信息');
                }
            }
            $data->sort = $inputData['sort'];
            if (isset($inputData['id']) && (int)$inputData['id'] > 0) {
                $banner->save($data, ['id' => (int)$inputData['id']]);
            } else {
                $banner->data($data);
                $res = $banner->save();
            }
            if(isset($res) && !$res){
                return self::response(400,'操作失败');
            }
            return self::response(0,'操作成功');
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
            $this->assign("title","推荐路线文物点设置");
            return $this->view->fetch('boss/route/point/list');
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
            return $this->view->fetch('boss/route/point/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function routePointSave(Request $request)
    {
        try {
            $inputData = $request->param('',null,'htmlspecialchars');
            $banner = new Routepoint();
            $data = new \stdClass();
            $data->pid = $inputData['pid'];
            $data->rid = $inputData['rid'];
            foreach ((array)$data as $v){
                if (!$v){
                    return self::response(400,'请输入完整信息');
                }
            }
            $data->sort = $inputData['sort'];
            if (isset($inputData['id']) && (int)$inputData['id'] > 0) {
                $banner->save($data, ['id' => (int)$inputData['id']]);
            } else {
                $banner->data($data);
                $res = $banner->save();
            }
            if(isset($res) && !$res){
                return self::response(400,'操作失败');
            }
            return self::response(0,'操作成功');
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
            if ($page >= $totalPage){
                $page = $totalPage;
            }
            $list = $client->order('sort')->limit(($page-1)*$limit, $limit)->select();
            $this->assign('page', $page);
            $this->assign('limit', $limit);
            $this->assign('totalPage', $totalPage);
            $this->assign('totalSize', $totalSize);
            $this->assign('list', $list);
            $this->assign("title","活动列表");
            return $this->view->fetch('boss/act/list');
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
            return $this->view->fetch('boss/act/newadd');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //保存 活动
    public function actSave(Request $request)
    {
        try {
            $inputData = $request->param('',null,'htmlspecialchars');
            $banner = new Act();
            $data = new \stdClass();
            $data->name = $inputData['name'];
            $data->des = $inputData['des'];
            $data->zone = $inputData['zone'];
            $data->img = $inputData['img'];
            foreach ((array)$data as $v){
                if (!$v){
                    return self::response(400,'请输入必填信息');
                }
            }
            $data->isfree = $inputData['isfree'];
            $data->cost = $inputData['cost'];
            if (!$data->isfree && !$data->cost){
                return self::response(400,'请输入报名费');
            }
            $data->isindex = $inputData['isindex'];
            $data->sort = $inputData['sort'];
            if (isset($inputData['id']) && (int)$inputData['id'] > 0) {
                $banner->save($data, ['id' => (int)$inputData['id']]);
            } else {
                $banner->data($data);
                $res = $banner->save();
            }
            if(isset($res) && !$res){
                return self::response(400,'操作失败');
            }
            return self::response(0,'操作成功');
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
        return $this->view->fetch('boss/donate/list');
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
            return $this->view->fetch('boss/donate/add');
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
        $type = Request::instance()->param('type','','htmlspecialchars');
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
            $list = $product->where(['state' => $state])->order('id desc')->paginate($limit);
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
        return $this->view->fetch('boss/product/list');
    }

    // 产品添加和编辑
    public function productSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post('',null,'htmlspecialchars');
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
            return $this->view->fetch('boss/product/add');
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
        return $this->view->fetch('boss/news/list');
    }

    // 网站公告添加和编辑
    public function newsSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post('',null,'htmlspecialchars');
            if (empty($data)) {
                return $this->response(400, '非法请求');
            }
            $news = new News();
            unset($data['file']);
            $res =  true;
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
            return $this->view->fetch('boss/news/add');
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
        $this->assign('title', '反馈管理-' . $this->title);
        return $this->view->fetch('boss/inspect/list');
    }

    //添加反馈
    public function inspectSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post('',null,'htmlspecialchars');
            if (empty($data)) {
                return $this->response(400, '非法请求');
            }
            $news = new Inspect();
            unset($data['file'], $data['content'], $data['imgs']);
            $res =  true;
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
            return $this->view->fetch('boss/inspect/add');
        }
    }

    public function inspectDel($id){
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
                    $config->where(['id'=>(int)$data['id']])->update($data);
                }
                if (!$res) {
                    return $this->response(400, '操作失败');
                }
                return $this->response(0, '操作成功');
            }
            $this->assign('about', $about);
            $this->assign('title', '关于我们' . $this->title);
            return $this->view->fetch('boss/about/index');
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
        return $this->view->fetch('boss/train/cate_list');
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
            return $this->view->fetch('boss/train/cate_add');
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
        $trains = $trainContent->where(['cate_id'=>$id])->find();
        if ($trains){
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
            $list = $train->alias('a')->order('a.id desc')->join('ly_train_cate b','a.cate_id = b.id','LEFT')->paginate($limit);
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
        return $this->view->fetch('boss/train/list');
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
            return $this->view->fetch('boss/train/add');
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
    public function certList(Request $request){
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
        return $this->view->fetch('boss/cert/list');
    }

    // 保存证书
    public function certSave(Request $request){
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
            return $this->view->fetch('boss/cert/add');
        }
    }

    // 删除证书
    public function certDel($id){
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

    public function nearList(Request $request){
        if ($request->isAjax()) {
            $limit = $request->request('limit');
            $limit = $limit ? intval($limit) : 10;
            $cert = new Pointnear();
            $list = $cert->paginate($limit);
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
        $this->assign('title', '附近管理-' . $this->title);
        return $this->view->fetch('boss/near/list');
    }

    public function nearSave(Request $request){
        if ($request->isAjax()) {
            $data = $request->post();
            if (empty($data)) {
                return $this->response(400, '非法请求');
            }
            $cert = new Pointnear();
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
            $cert = Pointnear::get($id);
            $this->assign('near', $cert);
            $this->assign('title', '添加/修改附近文物点-' . $this->title);
            return $this->view->fetch('boss/near/add');
        }
    }

    public function nearDel($id){
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
        if ($request->isAjax()){
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
            if (empty($admin)){
                return self::response(400, '管理员不存在~');
            }
            if ($admin->passwd != sha1(md5($data['oldpasswd']))){
                return self::response(400, '旧密码错误~');
            }
            if ($admin->passwd == sha1(md5($data['passwd']))){
                return self::response(400, '新旧密码不能相同~');
            }
            $admin->passwd = sha1(md5($data['passwd']));
            $res = $admin->save();
            if (!$res){
                return self::response(400, '密码修改失败');
            }
            Session::set('admin',null);
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
        // 查询订单 付款成功的订单
        $orderObj = new Order();
        $totalSize = $orderObj->where(['is_paied' => 1])->count();
        $totalPage = ceil($totalSize / $limit);
        if ($page >= $totalPage){
            $page = $totalPage;
        }
        $orderList = $orderObj->where(['is_paied' => 1])->order('id DESC')->field('order_no,total_price,total_price,name,phone,address,option,transaction_id,create_time')->limit(($page - 1) * $limit, $limit)->select();
        if ($orderList) {
            foreach ($orderList as $order) {
                $orderItemObj = new OrderItem();
                $orderItems = $orderItemObj->alias('a')->order('a.id DESC')->join($prefix . 'product b',
                    'a.pro_id = b.id', 'LEFT')->field('a.count,a.price,a.pro_id,b.name,b.img')->where(['a.order_no' => $order->order_no])->select();
                $order->orderItems = $orderItems;
            }
        }
        $this->assign('page', $page);
        $this->assign('limit', $limit);
        $this->assign('totalPage', $totalPage);
        $this->assign('totalSize', $totalSize);
        $this->assign('orderList', $orderList);
        $this->assign('title', '产品订单-' . $this->title);
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
            $list = $actRecordsObj->alias('a')->order('a.id desc')->join($prefix . 'act b', 'a.act_id = b.id','LEFT')->field('a.id,a.act_id,a.order_no,a.need_pay,a.price,a.name as user,a.phone,a.transaction_id,a.create_time,b.name')->where(['a.is_paied' => 1,'a.need_pay' => 1 - $isfree])->paginate($limit);
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
        return $this->view->fetch('boss/order/act');
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
            join($prefix . 'donate b', 'a.donate_id = b.id','LEFT')->
            join($prefix . 'user c', 'c.id = a.user_id','LEFT')->
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
        return $this->view->fetch('boss/order/donate');
    }

    /**
     * 添加备注信息
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function addOrderAttention(Request $request)
    {
        if (!$request->isAjax()){
            return self::response(400,'非法请求~');
        }
        $orderNo = $request->post('order_no','','htmlspecialchars');
        $option = $request->post('option','','htmlspecialchars');
        if (!$orderNo || !$option){
            return self::response(400,'非法请求');
        }
        $order = Order::get(['order_no' => $orderNo]);
        $order->option = $option;
        $res = $order->save();
        if (!$res){
            return self::response(400,'添加备注失败');
        }
        return self::response(0,'添加成功');
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
        if ($request->isAjax()) {
            $limit = $request->request('limit');
            $limit = $limit ? intval($limit) : 10;
            $memberObj = new Member();
            $list = $memberObj->order('id desc')->where(['`state`' => $state])->field('id,name,gender,id_cards,email,phone,career,reason,from,create_time')->paginate($limit);
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
        $this->assign('title', '注册信息-' . $this->title);
        return $this->view->fetch('boss/register/list');
    }

    /**
     * 修改注册审核状态
     * @param Request $request
     * @return \think\response\Json
     */
    public function registerPass(Request $request)
    {
        if (!$request->isAjax()){
            return self::response(400,'非法请求');
        }
        $id = (int)$request->post('id');
        if (!$id){
            return self::response(400,'非法请求');
        }
        $memberObj = new Member();
        $res = $memberObj->save(['state' => ['exp', '1-state']],['id'=>$id]);
        if (!$res){
            return self::response(400,'操作失败');
        }
        return self::response(0,'操作成功');
    }
}