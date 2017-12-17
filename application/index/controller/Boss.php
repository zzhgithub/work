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
use app\index\model\Banner;
use app\index\model\Donate;
use app\index\model\Point;
use app\index\model\Pointbanner;
use app\index\model\Pointdetail;
use app\index\model\Product;
use app\index\model\ProductContent;
use app\index\model\Route;
use app\index\model\Routepoint;
use think\Controller;
use think\Exception;
use think\Request;
use think\View;
use think\Config;

class Boss extends Controller
{
    protected $view;
    protected $title;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
        $this->title = Config::get('boss_title');
    }
    //boss后台设计

    // banner显示列表
    public function bannerList(){
        try {
            $banners = new Banner();
            $res = $banners
                ->order('sort')
                ->select();
            if($res === false){
                throw new Exception($banners->getLastSql());
            }
            $this->assign('list',$res);
            $this->assign('title',"轮播图列表");
            return $this->view->fetch('boss/banner/newlist');
        }catch (Exception $e){
            //
        }
    }

    //加载 banner 页的修改或者 添加页
    public function bannerAddOrModify($id = null){
        try{
            if($id ==  null){
                //如果这里id没有传过来就是说明 是 添加
                $this->assign('title','添加banner图');
                $this->assign('data',null);
            }else{
                // 获取单条数据
                $this->assign('title','修改banner图');
                $res = Banner::get($id);
                $this->assign('data',$res);
            }
            return $this->view->fetch('boss/banner/add');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //banner 图片保存加载一条龙图片
    public function bannerSave(){
        try {
            $banner = new Banner();
            $data = new \stdClass();
            $data->img = $_POST['img'];
            $data->des = $_POST['des'];
            $data->url = $_POST['url'];
            $data->sort = $_POST['sort'];
            if (isset($_POST['id']) && $_POST['id'] > 0) {
                $res = $banner->save($data,['id'=>$_POST['id']]);
            }else{
                $banner->data($data);
                $res = $banner->save();
            }

            echo $res;
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
            //redirect('/index.php?s=boss/banner/list', 5, '页面跳转中...');
    }
    //首页的banner删除
    public function bannerDel($id){
        try{
            Banner::destroy($id);
            //删除后加载列表页
            return $this->bannerList();
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    // 获取文物点列表

    public function pointList(){
        try{
            $list = Point::all();
            $this->assign('list',$list);
            return $this->view->fetch('boss/point/list');
        }catch (Exception $e){
            var_dump("");
        }
    }

    public function pointAddOrModify($id = null){
        try{
            if($id ==  null){
                //如果这里id没有传过来就是说明 是 添加
                $this->assign('title','添加文物点');
                $this->assign('data',null);
            }else{
                // 获取单条数据
                $this->assign('title','修改文物点');
                $res = Point::get($id);
                $this->assign('data',$res);
            }
            return $this->view->fetch('boss/point/add');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //  文物点保存加载一条龙图片
    public function pointSave(){
        try {
            $banner = new Point();
            $data = new \stdClass();
            $data->img = $_POST['img'];
            $data->name = $_POST['name'];
            $data->addr = $_POST['addr'];
            $data->level = $_POST['level'];
            $data->zone = $_POST['zone'];
            $data->sort = $_POST['sort'];
            if (isset($_POST['id']) && $_POST['id'] > 0) {
                $res = $banner->save($data,['id'=>$_POST['id']]);
            }else{
                $banner->data($data);
                $res = $banner->save();
            }

            echo $res;
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
        //redirect('/index.php?s=boss/banner/list', 5, '页面跳转中...');
    }

    //删除 文物点
    public function pointDel($id){
        try{
            Point::destroy($id);
            //删除后加载列表页
            return $this->pointList();
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    // 编辑详情页
    public function pagePointDetail($id){
        try{
            //先获取 详情的值 如果不存在就什么也不显示
            $test = Pointdetail::get($id);
            if($test == null){
                $data = array(
                    'id' => $id,
                    'des' => null,
                    'x' => null,
                    'y' => null
                );
                $this->assign('data',$data);
            }else{
                $this->assign('data',$test);
            }
            return $this->view->fetch('boss/point/detail');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //文物点详情保存
    public function pointDetailSave(){
        try{
            $client = new Pointdetail();
            $data = new \stdClass();
            $data->des = $_POST['des'];
            $data->x = $_POST['x'];
            $data->y = $_POST['y'];

            $test = Pointdetail::get($_POST['id']);

            if($test == null){
                $data->id = $_POST['id'];
                $res = $client->data($data)->save();
            }else{
                $res = $client->save($data,['id'=>$_POST['id']]);
            }
            return $res;
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //获取 文物点banner列表
    public function pointBannerList($id){
        try{
            //获取相应的banner列表
            $client = new Pointbanner();
            $res = $client->where(['pid'=>$id])->select();
            $this->assign('list',$res);
            return $this->view->fetch('boss/point/banner/list');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //文物点banner 的添加或者保存
    public function pointBannerAddOrSave($id=null,$pid=null){
        try{
            if($id != null){
                $data = Pointbanner::get($id);
                $this->assign('title','文物点 修改banner图');
                $this->assign('pid',$data->pid);
                $this->assign('data',$data);
            }else if($pid != null){
                $this->assign('title','文物点 添加banner图');
                $this->assign('pid',$pid);
                $this->assign('data',null);
            }

            return $this->view->fetch('boss/point/banner/add');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    // 添加或保存
    public function pointBannerSave(){
        try{
            $banner = new Pointbanner();
            $data = new \stdClass();
            $data->img = $_POST['img'];
            $data->des = $_POST['des'];
            $data->url = $_POST['url'];
            $data->sort = $_POST['sort'];
            $data->pid = $_POST['pid'];
            if (isset($_POST['id']) && $_POST['id'] > 0) {
                $res = $banner->save($data,['id'=>$_POST['id']]);
            }else{
                $banner->data($data);
                $res = $banner->save();
            }
            echo $res;
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //删除 文物点banner图
    public function pointBannerDell($id,$pid){
        try{
            $res = Pointbanner::destroy($id);
            return $this->pointBannerList($pid);
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }


    //推荐路线列表
    public function routeList(){
        try{
            //
            $client = new Route();
            $list = $client
                ->order('sort')
                ->select();
            $this->assign('list',$list);

            return $this->view->fetch('boss/route/list');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //推荐路线 添加或者报错
    public function routeAddOrSave($id = null){
        try{
            //
            if($id != null){
                $data = Route::get($id);
                $this->assign('title',"推荐路线修改");
                $this->assign('data',$data);
            }else{
                $this->assign('title','推荐路线添加');
                $this->assign('data',null);
            }
            return $this->view->fetch('boss/route/add');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //ajax 保存推荐路线
    public function routeSave(){
        try{
            $banner = new Route();
            $data = new \stdClass();
            $data->img = $_POST['img'];
            $data->des = $_POST['des'];
            $data->name = $_POST['name'];
            $data->sort = $_POST['sort'];
            $data->num = $_POST['num'];
            $data->cost = $_POST['cost'];

            if (isset($_POST['id']) && $_POST['id'] > 0) {
                $res = $banner->save($data,['id'=>$_POST['id']]);
            }else{
                $banner->data($data);
                $res = $banner->save();
            }
            echo $res;
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //删除推荐 路线
    public function routeDell($id){
        try{
            Route::destroy($id);
            return $this->routeList();
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //推荐路线文物点设置
    public function routePointList($rid){
        try{
            $client = new Routepoint();
            $list = $client->where(['rid'=>$rid])
                ->order('sort')
                ->select();
            $this->assign('list',$list);

            return $this->view->fetch('boss/route/point/list');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //添加或者修改 推荐路线文物点
    public function routePointAddOrSave($id = null,$rid = null){
        try{
            if($id != null){
                $data = Routepoint::get($id);
                $this->assign('title','修改 推荐路线文物点');
                $this->assign('rid',$data->rid);
                $this->assign('data',$data);
            }else if($rid != null){
                $this->assign('title','添加 推荐路线文物点');
                $this->assign('rid',$rid);
                $this->assign('data',null);
            }
            return $this->view->fetch('boss/route/point/add');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    public function  routePointSave(){
        try{
            $banner = new Routepoint();
            $data = new \stdClass();
            $data->pid = $_POST['pid'];
            $data->rid = $_POST['rid'];
            $data->sort = $_POST['sort'];
            if (isset($_POST['id']) && $_POST['id'] > 0) {
                $res = $banner->save($data,['id'=>$_POST['id']]);
            }else{
                $banner->data($data);
                $res = $banner->save();
            }
            echo $res;
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //删除
    public function routepointDell($id,$rid){
        try{
            Routepoint::destroy($id);
            return $this->routePointList($rid);
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    // 获取活动编辑列表
    public function actList(){
        try{
            $client = new Act();
            $list = $client->order('sort')
                ->select();
            $this->assign('list',$list);
            return $this->view->fetch('boss/act/list');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    // 活动编辑和添加
    public function actAddOrSave($id = null){
        try{
            if($id != null){
                $data = Act::get($id);
                $this->assign('title',"活动 修改");
                $this->assign('data',$data);
            }else{
                $this->assign('title','活动 添加');
                $this->assign('data',null);
            }
            return $this->view->fetch('boss/act/add');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //保存 活动
    public function actSave(){
        try{
            $banner = new Act();
            $data = new \stdClass();
            $data->name = $_POST['name'];
            $data->isfree = $_POST['isfree'];
            $data->cost = $_POST['cost'];
            $data->des = $_POST['des'];
            $data->isindex = $_POST['isindex'];
            $data->zone = $_POST['zone'];
            $data->img = $_POST['img'];
            $data->sort = $_POST['sort'];
            if (isset($_POST['id']) && $_POST['id'] > 0) {
                $res = $banner->save($data,['id'=>$_POST['id']]);
            }else{
                $banner->data($data);
                $res = $banner->save();
            }
            echo $res;
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    //删除 活动
    public function delSave($id){
        try{
            Act::destroy($id);
            return $this->actList();
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    // 捐款列表
    public function donateList(Request $request)
    {
        if ($request->isAjax()){
            $limit = $request->request('limit');
            $state = $request->request('state');
            $state = intval($state) ?1:0;
            $limit = $limit ? intval($limit):10;
            $donate = new Donate();
            $list = $donate->where(['state'=>$state])->order('sort asc,id desc')->paginate($limit);
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg ='';
            $response->data = array();
            if (!empty($list)){
                $response->data = $list->items();
            }
            return json($response);
        }

        $this->assign('title','捐款列表-'.$this->title);
        return $this->view->fetch('boss/donate/list');
    }

    // 捐款添加和编辑
    public function donateSave(Request $request)
    {
        if ($request->isPost()){
            $response = new \stdClass();
            $response->code = 400;
            $response->data = '';
            $response->msg = '非法请求';
            $data = $request->post();
            if (empty($data)){
                return json($response);
            }
            $donate = new Donate();
            unset($data['file']);
            if (isset($data['id'])&&intval($data['id'])){
                $data['id'] = intval($data['id']);
                $res = $donate->save($data, ['id'=>$data['id']]);
            }else{
                $donate->data($data);
                $res = $donate->save();
            }
            if ($res){
                $response->code = 0;
                $response->msg = '操作成功';
            }else{
                $response->code = 400;
                $response->msg = '操作失败';
            }
            return json($response);
        }else{
            $detail = Donate::get(intval($request->param('id')));
            $this->assign('title','添加捐款项-'.$this->title);
            $this->assign('detail',$detail);
            return $this->view->fetch('boss/donate/add');
        }

    }

    // 删除捐款
    public function donateState()
    {
        $id = intval($_POST['id']);
        if ($id <= 0){
            return $this->response(400,'非法请求');
        }
        $res = Donate::where('id', $id)->update(['state' => ['exp','1-state']]);
        if ($res){
            return $this->response(0,'操作成功');
        }
        return $this->response(400,'操作失败');
    }

    // 文件上传
    public function upload(){
        $response = [
            'code' => 400,
            'data' =>[
                'src' => '',
                'title' => ''
            ],
            'msg' => '非法请求'
        ];
        if (!Request::instance()->isPost()){
            return json($response);
        }
        $type = Request::instance()->post('type');
        if (!$type){
            $type = '';
        }
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads'.  DS . $type);
        if($info){
            // 成功上传后 获取上传信息
            $response['code'] = 0;
            if ($type){
                $response['data']['src'] = '/uploads'.  DS . $type . DS . $info->getSaveName();
            }else{
                $response['data']['src'] = '/uploads'.  DS . $info->getSaveName();
            }
            $response['data']['title'] = $info->getFilename();
            $response['msg'] = '上传成功';
        }else{
            //上传失败
            $response['code'] = 400;
            $response['data'] = $file->getError();
            $response['msg'] = '上传失败';
        }
        return json($response);
    }

    private function response($code,$msg,$data=[])
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
        if ($request->isAjax()){
            $limit = $request->request('limit');
            $state = $request->request('state');
            $state = intval($state) ?1:0;
            $limit = $limit ? intval($limit):10;
            $donate = new Product();
            $list = $donate->where(['state'=>$state])->order('id desc')->paginate($limit);
            $response = new \stdClass();
            $response->code = 0;
            $response->count = $list->total();
            $response->msg ='';
            $response->data = array();
            if (!empty($list)){
                $response->code = 400;
                $response->data = $list->items();
            }
            return json($response);
        }

        $this->assign('title','文创产品列表-'.$this->title);
        return $this->view->fetch('boss/product/list');
    }

    // 捐款添加和编辑
    public function productSave(Request $request)
    {
        if ($request->isAjax()){
            echo 1222;die;
            $data = $request->post();
            if (empty($data)){
                return $this->response(400,'非法请求');
            }
            $product = new Product();
            $productContent = new ProductContent();
            $content = $data['content'];
            unset($data['file'],$data['content']);
            if (isset($data['id'])&&intval($data['id'])){
                $data['id'] = intval($data['id']);
                $res = $product->save($data, ['id'=>$data['id']]);
                if ($res){
                    $res = $productContent->save(['content'=>$content],['id'=>$data['id']]);
                }
            }else{
                $product->data($data);
                $res = $product->save();
                if ($res){
                    $productContent->data(['id'=>$product->id,'content'=>$content]);
                    $res = $productContent->save();
                }
            }
            if ($res){
                return $this->response(0,'操作成功');
            }else{
                return $this->response(400,'操作失败');
            }
        }else{
            $detail = Donate::get(intval($request->param('id')));
            $this->assign('title','添加产品-'.$this->title);
            $this->assign('detail',$detail);
            return $this->view->fetch('boss/product/add');
        }

    }
}