<?php
/**
 * ,__,
 * (oo)_____
 * (__)     )\
 * ````||---|| *
 * Class WeiXin
 * @package app\index\service  <br>
 * @author mutou <br>
 * @version 1.0.0
 * @description weixin <br>
 * @date 2017/12/23 <br>
 */

namespace app\index\service;

use app\index\model\User;
use think\Cache;
use think\Config;
use think\Request;
use think\Session;

class WeiXin
{
    const ORDER_ACT = 1;
    const ORDER_DONATE = 2;
    const ORDER_PRODUCT = 3;

    public static function weiXinPayData($body, $out_trade_no, $total_fee, $openId)
    {
        $appid = Config::get('weixin.APPID');
        $mch_id = Config::get('weixin.MCHID');
        $nonceStr = self::getNonceStr();
        $spbill_create_ip = Request::instance()->ip();
        $notify_url = 'http://' . $_SERVER['HTTP_HOST'] . '/notify';
        $time_start = date('YmdHis');
        $time_expire = date('YmdHis', time() + 900);
        $trade_type = 'JSAPI';
        $attach = $out_trade_no;
        // 生成预处理签名
        $stringA = "appid=$appid&attach=$attach&body=$body&mch_id=$mch_id&nonce_str=$nonceStr&notify_url=$notify_url&openid=$openId&out_trade_no=$out_trade_no&spbill_create_ip=$spbill_create_ip&time_expire=$time_expire&time_start=$time_start&total_fee=$total_fee&trade_type=$trade_type";
        $stringSignTemp = $stringA . '&key=' . Config::get('weixin.KEY');//注：key为商户平台设置的密钥key

        $sign = strtoupper(md5($stringSignTemp));//注：MD5签名方式

        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $xmlTpl = "<xml>
                       <appid><![CDATA[%s]]></appid>
                       <attach><![CDATA[%s]]></attach>
                       <body><![CDATA[%s]]></body>
                       <mch_id><![CDATA[%s]]></mch_id>
                       <nonce_str><![CDATA[%s]]></nonce_str>
                       <notify_url><![CDATA[%s]]></notify_url>
                       <out_trade_no><![CDATA[%s]]></out_trade_no>
                       <spbill_create_ip><![CDATA[%s]]></spbill_create_ip>
                       <time_start><![CDATA[%s]]></time_start>
                       <time_expire><![CDATA[%s]]></time_expire>                       
                       <total_fee><![CDATA[%s]]></total_fee>
                       <trade_type><![CDATA[%s]]></trade_type>
                       <sign><![CDATA[%s]]></sign>
                       <openid><![CDATA[%s]]></openid>
                   </xml>";
        $postData = sprintf($xmlTpl, $appid, $attach, $body, $mch_id, $nonceStr, $notify_url, $out_trade_no, $spbill_create_ip,
            $time_start, $time_expire, $total_fee, $trade_type, $sign, $openId);
        $returnData = null;
        try {
            $returnData = self::post($url, $postData);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        libxml_disable_entity_loader(true);
        $data = simplexml_load_string($returnData, 'SimpleXMLElement', LIBXML_NOCDATA);

        // 重新签名
        $timeStamp = time();
        $nonceStr = self::getNonceStr();
        $package = 'prepay_id=' . $data->prepay_id;
        $signType = 'MD5';

        $stringA = "appId=$appid&nonceStr=$nonceStr&package=$package&signType=$signType&timeStamp=$timeStamp";
        $stringSignTemp = $stringA . '&key=' . Config::get('weixin.KEY');//注：key为商户平台设置的密钥key
        $sign = strtoupper(md5($stringSignTemp));//注：MD5签名方式

        $wxPayConfig = new WXPayConfig();
        $wxPayConfig->package = $package;
        $wxPayConfig->nonceStr = $nonceStr;
        $wxPayConfig->timeStamp = $timeStamp;
        $wxPayConfig->signType = $signType;
        $wxPayConfig->paySign = $sign;
        return $wxPayConfig->toJson();
    }

    public static function getPrepayId()
    {

    }

    /**
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return string
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取access_token 接口调用凭证
     * @return mixed
     * @throws \Exception
     */
    public static function getAccessToken()
    {
        $accessToken = Cache::get('access_token', '');
        if ($accessToken != '') {
            return $accessToken;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . Config::get('weixin.APPID') . '&secret=' . Config::get('weixin.APPSECRET');
        $data = self::get($url);
        $json = json_decode($data);
        Cache::set('access_token', $json->access_token, 7200);
        return $json->access_token;
    }

    public static function getJsApiTicket($accessToken)
    {
        $jsApiTicket = Cache::get('jsapi_ticket', '');
        if ($jsApiTicket != '') {
            return $jsApiTicket;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $accessToken . '&type=jsapi';
        $data = self::get($url);
        $json = json_decode($data);
        Cache::set('jsapi_ticket', $json->ticket, 7200);
        return $json->ticket;
    }

    public static function signature($jsApiTicket, $nonceStr, $timestamp, $url)
    {
        $string = 'jsapi_ticket=' . $jsApiTicket . '&noncestr=' . $nonceStr . '&timestamp=' . $timestamp . '&url=' . $url;
        return sha1($string);
    }

    /**
     *
     * 通过跳转获取用户的openid，跳转流程如下：
     * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
     * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
     *
     * @return openid
     * @throws \Exception
     */
    public static function getOpenidAndAcessToken()
    {
        //通过code获得openid
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            $baseUrl = urlencode('http://' . $_SERVER['HTTP_HOST'] . '/helper/back');
            $url = self::createOauthUrlForCode($baseUrl);
            header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $res = self::getOpenidFromMp($code);
            $data = json_decode($res);
            return $data;
        }
    }

    /**
     * curl get 请求
     * @param $url
     * @param int $second
     * @return mixed
     * @throws \Exception
     */
    public static function get($url, $second = 10)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_URL, $url);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //是否直接输出到屏幕
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //https请求 不验证证书 其实只用这个就可以了
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //https请求 不验证HOST

        curl_setopt($ch, CURLOPT_POST, false);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new \Exception("curl出错，错误码:$error");
        }
    }

    /**
     * curl post 请求
     * @param $url
     * @param null $data
     * @param int $second
     * @param bool $useCert
     * @return mixed|null
     * @throws \Exception
     */
    public static function post($url, $data = null, $second = 10, $useCert = false)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($useCert == true) {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
        }
    }

    /**
     * @param $params
     * @return string
     * @throws \Exception
     */
    public static function ToXml($params)
    {
        if (!is_array($params)
            || count($params) <= 0) {
            throw new \Exception("数组数据异常！");
        }

        $xml = "<xml>";
        foreach ($params as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 通过code从工作平台获取openid机器access_token
     * @param $code
     * @return mixed
     * @throws \Exception
     */
    public static function GetOpenidFromMp($code)
    {
        $url = self::createOauthUrlForOpenid($code);
        $res = self::get($url);
        //取出openid
        return $res;
    }

    /**
     * 构造获取code的url连接
     * @param $redirectUrl
     * @return string
     */
    private static function createOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = Config::get('weixin.APPID');
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = "STATE" . "#wechat_redirect";
        $bizString = self::ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }

    /**
     * 构造获取openid和access_toke的url地址
     * @param $code
     * @return string
     */
    private static function createOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = Config::get('weixin.APPID');
        $urlObj["secret"] = Config::get('weixin.APPSECRET');
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = self::ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?" . $bizString;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private static function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    public static function getUserIdByOpenid($openid)
    {
        if (!$openid){
            return 0;
        }
        $userid = Session::get('user_id',0);
        if ($userid){
            return $userid;
        }
        $userObj = new User();
        $user = $userObj->where(['openid' => $openid])->field('id')->find();
        if ($user){
            Session::set('user_id',$user->id);
            return $user->id;
        }
        return 0;
    }

    public static function createOrderNo($type)
    {
        if (!in_array($type,[self::ORDER_ACT,self::ORDER_DONATE,self::ORDER_PRODUCT])){
            return '';
        }
        $orderPre = '';
        switch ($type) {
            case self::ORDER_ACT:
                $orderPre = 'act_';
                break;
            case self::ORDER_DONATE:
                $orderPre = 'don_';
                break;
            default:
                $orderPre = 'pro_';
                break;
        }
        return $orderPre.date('YmdHis').(string)rand(1000,9999);
    }
}