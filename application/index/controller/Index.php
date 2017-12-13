<?php
namespace app\index\controller;

use app\index\model\Act;
use app\index\model\Banner;
use \think\Controller;
use think\Request;
use think\View;

class Index extends Controller
{

    protected $view;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
    }

    public function index()
    {
        //获取首页的banner图
        $banner = new Banner();
        $res_banner = $banner->limit(3)->order('sort')->select();
        $this->assign('banner',$res_banner);

        //活动
        $act = new Act();
        $res_act = $act->where(['isindex'=>1])
            ->order('sort')
            ->select();
        $this->assign('actlist',$res_act);
        return $this->view->fetch('index/index');
    }

}
