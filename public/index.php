<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
define("BS_URL","cqlaojie.com");
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
try{
    require __DIR__ . '/../thinkphp/start.php';
}catch (Exception $e){
    if (strpos($_SERVER['PATH_INFO'],'boss')){
        header('Location:/boss/index');
    }else{
        header('Location:/');
    }
}
