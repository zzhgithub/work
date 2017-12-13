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
    }

    /**
     * 历史建筑
     * 根据参数获取
     * @param $query
     */
    public function historyStruct($query = null){
        //默认情况没有查询条件
        try{
            //
            $list = Point::all();
            $this->assign('list',$list);
            return $this->view->fetch('point/list');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    /**
     * 任务点打开
     * 加载配置的固定模板
     */
    public function punch(){
        //todo
    }

    /**
     * 推荐路线页(分页查询 这里交互模糊)
     * 支持条件查询
     * @param $query
     */
    public function pathList($query = null){
        try{
            $client = new Route();
            $list =
                $client->order('sort')
                ->select();
            $this->assign('list',$list);
            return $this->view->fetch('route/list');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    /**
     * 推荐路线查询页
     * @param $id
     */
    public function pathDetail($id){
        try{
            $data = Route::get($id);
            $this->assign('data',$data);
            // 获文物点地址
            $client = new Routepoint();
            $list = $client->where(['rid'=>$id])
                ->order('sort')
                ->select();
            foreach ($list as $k=>$v){
                //
                $tmp = Point::get($v['pid']);
                $list[$k]['name'] = $tmp['name'];
            }
            $this->assign('list',$list);
            return $this->view->fetch('route/detail');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    /**
     * 文物点详情页
     * @param $id
     */
    public function ponitDetail($id){
        try{
            $base = Point::get($id);
            $this->assign('base',$base);

            $ext = Pointdetail::get($id);
            $this->assign('ext',$ext);

            $client = new Pointbanner();
            $list = $client->where(['pid'=>$id])->select();
            $this->assign('list',$list);

            return $this->view->fetch('point/detail');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    /**
     * 任务点证书页 todo 这里和上面的对应关系是什么呢？
     * @param $id
     */
    public function paper($id){
        //todo
    }
}
