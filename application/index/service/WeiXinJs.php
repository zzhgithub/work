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
    public $appId;
    public $timeStamp;
    public $nonceStr;
    public $package;
    public $signType;
    public $paySign;

    public function toJson()
    {
        return json_encode($this,JSON_UNESCAPED_UNICODE);
    }
}