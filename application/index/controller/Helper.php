<?php

namespace app\index\controller;

/**
 * 帮助类
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午6:06
 */

use app\index\model\ActRecords;
use app\index\model\Config;
use app\index\model\DonateRecords;
use app\index\model\Order;
use app\index\model\User;
use app\index\service\WeiXin;
use think\Controller;
use think\Exception;
use think\Session;
use \app\index\model\Log;

class Helper extends Controller
{
    public function Upload()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            // 成功上传后 获取上传信息
            // 输出 jpg
            //echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg echo $info->getSaveName();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            echo $info->getSaveName();
            //echo $info->getFilename();
            //echo $info->getSaveName();
        } else {
            //上传失败
            echo $file->getError();
        }
    }

    //登录测试
    public function sendTest()
    {
        try {
            $appid = "wxfcc662fea0340227";
            $callbackurl = "http://www.cqlaojie.com/index.php?s=helper/back";//暂时定位测试地址
            $scope = "snsapi_userinfo";

            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid="
                . $appid
                . "&redirect_uri="
                . urlencode($callbackurl)
                . "&response_type=code&scope="
                . $scope
                . "&state=STATE#wechat_redirect";
            header("Location:" . $url);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 微信网页登录授权
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function weixinBack()
    {
        //try{
        //    $appid = "wxfcc662fea0340227";
        //    $secret = "923658e6d9f6beee3eeacce5b940d9c1";
        //    $code = $_GET["code"];
        //    $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
        //    $json_obj = json_decode(file_get_contents($get_token_url));
        //    $access_token = $json_obj->access_token;
        //    $openid = $json_obj->openid;
        //    $get_user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        //    $user_obj = json_decode(file_get_contents($get_user_info_url));
        //    //var_dump($user_obj);
        //    //设置用的cookie
        //    $client = new User();
        //    $user = new \stdClass();
        //    $user->openid = $user_obj->openid;
        //    $user->nickname = $user_obj->nickname;
        //    $user->headimgurl = $user_obj->headimgurl;
        //    $user->sex = $user_obj->sex;
        //
        //    $res = $client->where(['openid'=>$user_obj->openid])
        //        ->find();
        //    if($res == null ||!isset($res->id) || $res->id == 0 || $res->id == null) {
        //        //没有数据进行更新
        //        $client->data($user)->save();
        //    }
        //    //设置登录的cookie
        //    Session::set("openid",$user_obj->openid);
        //    $this->redirect("/");
        //}catch (Exception $e){
        //    var_dump($e);
        //}
        $openid = Session::get('openid');
        if (!$openid) {
            $data = WeiXin::getOpenidAndAcessToken();
            $access_token = $data->access_token;
            $openid = $data->openid;
            $getUserInfoUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
            $userObj = json_decode(Weixin::get($getUserInfoUrl));
            //var_dump($user_obj);
            //设置用的cookie
            $client = new User();
            $user = new \stdClass();
            $user->openid = $userObj->openid;
            $user->nickname = $userObj->nickname;
            $user->headimgurl = $userObj->headimgurl;
            $user->sex = $userObj->sex;

            $res = $client->where(['openid' => $userObj->openid])
                ->find();
            if ($res == null || !isset($res->id) || $res->id == 0 || $res->id == null) {
                //没有数据进行更新
                $client->data($user)->save();
            }
            //设置登录的cookie
            Session::set("openid", $userObj->openid);
        }
        $this->redirect("/");
    }

    /**
     * 微信支付异步通知 处理订单信息
     * @throws \Exception
     */
    public function notify()
    {
        $fp = fopen("notifylock.txt", "w+");
        if(!flock($fp,LOCK_EX | LOCK_NB)){
            file_put_contents('../data/log/notify/' . date('Y-m-d') . '-notify.log',
                date('Y-m-d H:i:s') . ':-----系统繁忙，稍后继续------'. PHP_EOL . '------------------------------------------' . PHP_EOL,FILE_APPEND);
            return false;
        }
        $xml = file_get_contents('php://input');
        $data = WeiXin::fromXmlToArray($xml);
        $sign = WeiXin::makeSign($data);
        $result = [];
        if ($sign === $data['sign']) {  // 签名校验通过
            $out_trade_no = $data['out_trade_no'];
            $type = substr($out_trade_no, 0, 3);
            if ($type == 'act') {
                // 更新订单状态
                $actRecordsObj = new ActRecords();
                // 根据订单 查询信息
                $actRecords = $actRecordsObj->getOneByOrder($data['out_trade_no']);
                if ($actRecords->price * 100 == $data['total_fee']){
                    $res = $actRecordsObj->save([
                        'is_paied' => 1,
                        'transaction_id' => $data['transaction_id']
                    ], ['order_no' => $data['out_trade_no']]);
                    if ($res) {
                        // 更新日志
                        $log = new Log();
                        $log->order_no = $data['out_trade_no'];
                        $log->user_id = $actRecords->user_id;
                        $log->open_id = $actRecords->open_id;
                        $log->type = WeiXin::ORDER_ACT;
                        $log->content = '支付成功';
                        $log->price = $actRecords->price;
                        $log->transaction_id = $data['transaction_id'];
                        $log->save();
                        $msg = '交易成功';
                        $result['return_code'] = 'SUCCESS';
                        $result['return_msg'] = '';
                    } else {
                        $msg = '交易失败';
                        $result['return_code'] = 'FAIL';
                        $result['return_msg'] = '业务处理失败';
                    }
                }else{
                    $msg = '交易失败';
                    $result['return_code'] = 'FAIL';
                    $result['return_msg'] = '签名失败';
                }
            } elseif ($type == 'don') {   // 捐款
                // 更新订单状态
                $donateRecordsObj = new DonateRecords();
                // 根据订单 查询信息
                $donateRecords = $donateRecordsObj->getOneByOrder($data['out_trade_no']);
                if ($donateRecords->money * 100 == $data['total_fee']){
                    $res = $donateRecordsObj->save([
                        'is_paied' => 1,
                        'transaction_id' => $data['transaction_id']
                    ], ['order_no' => $data['out_trade_no']]);
                    if ($res) {
                        // 更新日志
                        $log = new Log();
                        $log->order_no = $data['out_trade_no'];
                        $log->user_id = $donateRecords->user_id;
                        $log->open_id = $donateRecords->open_id;
                        $log->type = WeiXin::ORDER_DONATE;
                        $log->content = '支付成功';
                        $log->price = $donateRecords->money;
                        $log->transaction_id = $data['transaction_id'];
                        $log->save();
                        $msg = '交易成功';
                        $result['return_code'] = 'SUCCESS';
                        $result['return_msg'] = '';
                    } else {
                        $msg = '交易失败';
                        $result['return_code'] = 'FAIL';
                        $result['return_msg'] = '业务处理失败';
                    }
                }else{
                    $msg = '交易失败';
                    $result['return_code'] = 'FAIL';
                    $result['return_msg'] = '签名失败';
                }
            } elseif ($type == 'pro') {
                // 更新订单状态
                $orderObj = new Order();
                // 根据订单 查询信息
                $order = $orderObj->getOneByOrder($data['out_trade_no']);
                if ($order->total_price * 100 == $data['total_fee']){
                    $res = $orderObj->save([
                        'is_paied' => 1,
                        'transaction_id' => $data['transaction_id']
                    ], ['order_no' => $data['out_trade_no']]);
                    if ($res) {
                        // 更新日志
                        $log = new Log();
                        $log->order_no = $data['out_trade_no'];
                        $log->user_id = $order->user_id;
                        $log->open_id = $order->open_id;
                        $log->type = WeiXin::ORDER_PRODUCT;
                        $log->content = '支付成功';
                        $log->price = $order->total_price;
                        $log->transaction_id = $data['transaction_id'];
                        $log->save();
                        $msg = '交易成功';
                        $result['return_code'] = 'SUCCESS';
                        $result['return_msg'] = '';
                    } else {
                        $msg = '交易失败';
                        $result['return_code'] = 'FAIL';
                        $result['return_msg'] = '业务处理失败';
                    }
                }else{
                    $msg = '交易失败';
                    $result['return_code'] = 'FAIL';
                    $result['return_msg'] = '签名失败';
                }
            } else {
                $msg = '交易失败';
                $result['return_code'] = 'FAIL';
                $result['return_msg'] = '业务处理失败';
            }
        } else {
            $msg = '交易失败';
            $result['return_code'] = 'FAIL';
            $result['return_msg'] = '签名失败';
        }
        $returnStr = WeiXin::fromArrayToXml($result);
        file_put_contents('../data/log/notify/' . date('Y-m-d') . '-notify.log',
            date('Y-m-d H:i:s') . ':' . $data['out_trade_no'] . ':' . $data['transaction_id'] . '-----' . $msg . PHP_EOL . $returnStr . PHP_EOL . '------------------------------------------' . PHP_EOL,
            FILE_APPEND);
        flock($fp,LOCK_UN);     //释放锁
        fclose($fp);
        echo $returnStr;
    }
}