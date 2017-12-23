<?php

namespace app\index\model;

use think\Model;

class Admin extends Model
{
    protected $dateFormat = 'Y-m-d H:i';
    protected $autoWriteTimestamp = 'datetime';
}