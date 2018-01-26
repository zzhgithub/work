<?php
namespace app\index\controller;

use \app\index\model\Act;
use \app\index\model\Banner;
use \app\index\model\Product;
use \app\index\model\News;
use \think\Request;

class Index extends Base
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->assign('_action','index');
    }

    /**
     * 首页
     * @param Request $request
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        //获取首页的banner图
        $banner = new Banner();
        $res_banner = $banner->order('sort')->select();
        $this->assign('banner',$res_banner);

        // 获取公告
        $news = News::all();
        $this->assign('news', $news);
        //活动
        $act = new Act();
        $res_act = $act->order('sort')->select();
        $this->assign('actlist',$res_act);

        // product
        $product = new Product();
        $list = $product->where(['state' => 1])->order('id desc')->field('id,name,img,price,store')->paginate(6);
        $items = $list->items();
        $this->assign('curPage', 1);
        $this->assign('title', '重庆老街');
        $this->assign('productList', $items);
        return $this->view->fetch('index/index');
    }
}
