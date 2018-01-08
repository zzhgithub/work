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
    protected $openId;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->view = new View();
        $this->assign('_action','index');
        //$openId = Session::get('openid');
        //if (!$openId) {
        //    if ($request->isAjax()) {
        //        return self::response(400, '请刷新页面重新登录');
        //    } else {
        //        $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        //        WeiXin::getOpenidAndAcessToken($url);
        //    }
        //}
        //$this->openId = $openId;
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
        $res_act = $act->where(['isindex'=>1])->order('sort')->select();
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
