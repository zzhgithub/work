<?php
namespace app\index\controller;
/**
 * 活动于文创相关
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:36
 */
use app\index\model\Act;
use think\Controller;
use think\Exception;
use think\Request;
use think\View;

class Activity extends Controller
{
    protected $view;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
    }

    /**
     * 活动列表页
     */
    public function activityList(){
        try{
            //
            $client = new Act();
            $list = $client->order('sort')
                ->select();
            $this->assign('list',$list);
            return $this->view->fetch('act/list');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    /**
     * 活动详情页
     */
    public function activityDetail($id){
        try{
            $data = Act::get($id);
            $this->assign('data',$data);
            return $this->view->fetch('act/detail');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    /**
     * 活动报名页
     * @param $id
     */
    public function joinActivity($id){
        //
    }

    /**
     * 报名页表单处理
     */
    public function doJoin(){
        //
    }

    /**
     * 捐款列表页
     */
    public function DonteList(){
        //
    }

    /**
     * 捐款详情页
     */
    public function DonteDetail($id){

    }

    /**
     * 捐款/文创 支付页
     * @param $id
     */
    public function payPage($id,$type){

    }

    //todo 获取捐款的第三方接口


    /**
     * 文创产品列表页
     */
    public function productList(){

    }

    /**
     * 文创产品详情页
     */
    public function productDetail(){

    }

    /**
     * 关于我们
     */
    public function about(){

    }
}