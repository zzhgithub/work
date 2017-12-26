<?php

namespace app\index\model;

/**
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/12/11
 * Time: 上午2:48
 */
use think\Model;

class OrderItem extends Model
{
    protected $updateTime = false;
    protected $createTime = false;
    public function getOneByOrder($orderNo)
    {
        return $this->where(['order_no' => $orderNo])->find();
    }
}