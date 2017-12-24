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

use think\Config;

class WeiXin
{
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

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); //是否直接输出到屏幕
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //https请求 不验证证书 其实只用这个就可以了
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //https请求 不验证HOST

        curl_setopt($ch,CURLOPT_POST,false);
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
            throw new \Exception("curl出错，错误码:$error");
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
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString =self::ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
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
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
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
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
}