<?php
namespace app\index\controller;
/**
 * 个人中心
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:53
 */
use app\index\model\ActRecords;
use app\index\model\DonateRecords;
use app\index\model\Order;
use app\index\model\OrderItem;
use app\index\model\User;
use \think\Controller;
use \app\index\service\WeiXin;
use \think\Session;

class Person extends Controller
{
    protected $openId;

    /**
     * 个人中心
     * @return mixed
     * @throws \Exception
     */
    public function index(){
        //$openId = Session::get('openid');
        //if (!$openId) {
        //    $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        //    WeiXin::getOpenidAndAcessToken($url);
        //}
        //$this->openId = $openId;
        $prefix = config("database.prefix");
        // 用户信息
        $user = User::get(['openid' => $this->openId]);

        // 获取相关订单信息
        // 参与的活动
        $actRecordsObj = new ActRecords();
        $actList = $actRecordsObj->alias('a')->order('a.id desc')->join($prefix.'act b','a.act_id = b.id','LEFT')->field('a.is_paied,a.need_pay,b.id,b.name,b.img,b.des')->where(['a.open_id'=>$this->openId])->select();
        // 产品订单
        $orderObj = new Order();
        $orderList = $orderObj->order('id DESC')->where(['open_id'=>$this->openId,'is_paied'=>1])->field('order_no')->select();
        if ($orderList){
            foreach ($orderList as $order) {
                $orderItemObj = new OrderItem();
                $orderItems = $orderItemObj->alias('a')->order('a.id DESC')->join($prefix.'product b','a.pro_id = b.id','LEFT')->field('a.count,a.price,a.pro_id,b.name,b.img')->where(['a.order_no'=>$order->order_no])->select();
                $order->orderItems = $orderItems;
            }
        }
        // 捐款
        $donateRecordsObj = new DonateRecords();
        $donateList = $donateRecordsObj->alias('a')->order('a.id desc')->join($prefix.'donate b','a.donate_id = b.id','LEFT')->field('a.money,a.create_time,b.name')->where(['a.is_paied'=>1,'a.open_id'=>$this->openId])->select();

        $this->assign('user',$user);
        $this->assign('actList',$actList);
        $this->assign('orderList',$orderList);
        $this->assign('donateList',$donateList);
        $this->assign('_action','ucenter');
        $this->assign('title','个人中心');
        return $this->fetch('ucenter/index');
    }
}