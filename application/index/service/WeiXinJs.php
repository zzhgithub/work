<?php
/**
 * ,__,
 * (oo)_____
 * (__)     )\
 * ````||---|| *
 * Class WeiXinJs   * @package app\index\service  <br>
 * @author mutou <br>
 * @version 1.0.0
 * @description todo <br>
 * @date 2017/12/24 <br>
 */


namespace app\index\service;


class WeiXinJs
{
    public $appId; // 必填，公众号的唯一标识
    public $timestamp; // 必填，生成签名的时间戳
    public $nonceStr; // 必填，生成签名的随机串
    public $signature;// 必填，签名，见附录1

    public function toJson()
    {
        return json_encode($this, JSON_UNESCAPED_UNICODE);
    }
}