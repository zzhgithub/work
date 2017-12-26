<?php

namespace app\index\model;

/**
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/12/11
 * Time: 上午2:48
 */
use think\Model;

class Order extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $autoWriteTimestamp = 'datetime';

    public function getOneByOrder($orderNo)
    {
        return $this->where(['order_no' => $orderNo])->find();
    }
}