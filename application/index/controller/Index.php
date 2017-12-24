<?php
namespace app\index\controller;

use \app\index\model\Act;
use \app\index\model\Banner;
use \app\index\model\Product;
use \app\index\model\News;
use \app\index\service\WeiXin;
use \think\Controller;
use \think\Request;
use \think\Session;
use \think\View;

class Index extends Controller
{

    protected $view;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
        $this->assign('_action','index');
        $openId = Session::get('openid');
        if (!$openId){
            WeiXin::getOpenidAndAcessToken();
        }
    }

    public function index(Request $request)
    {
        //获取session登录信息
        //if(Session::get("openid") == null){
        //    $helper = new Helper();
        //    $helper->sendTest();
        //}
        //获取首页的banner图
        $banner = new Banner();
        $res_banner = $banner->limit(3)->order('sort')->select();
        $this->assign('banner',$res_banner);

        // 获取公告
        $news = News::all();
        $this->assign('news', $news);
        //活动
        $act = new Act();
        $res_act = $act->where(['isindex'=>1])->order('sort')->select();
        $this->assign('actlist',$res_act);

        // product
        $product = new Product();
        $list = $product->where(['state' => 1])->order('id desc')->field('id,name,img,price,store')->paginate(6);
        $items = $list->items();
        $this->assign('curPage', 1);
        $this->assign('productList', $items);
        return $this->view->fetch('index/index');
    }
}
