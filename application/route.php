<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
return [
    'home'=>'index/index/index',//首页入口
    'point/list' => 'index/show/historyStruct',//历史建筑 (文物点)
    'point/d etail/:id' => 'index/show/ponitDetail',//文物点 详情
    'route/list' => 'index/show/pathList',//推荐路线列表
    'route/detail/:id' => 'index/show/pathDetail',//推荐路线详情
    'act/list' => 'index/activity/activityList',//活动列表
    'act/detail/:id' => 'index/activity/activityDetail', //活动详情
    'act/join/:id' => 'index/activity/joinActivity', //活动报名
    'act/dojoin' => 'index/activity/doJoin', //活动报名

    /**
     * boss 后台相关功能
     */
    'boss/banner/list' => 'index/boss/bannerList',//banner 列表
    'boss/banner/add'  => 'index/boss/bannerAddOrModify',//banner添加页
    'boss/banner/mod/:id'  => 'index/boss/bannerAddOrModify',//banner修改页
    'boss/banner/save' => 'index/boss/bannerSave',//banner的保存和更新
    'boss/banner/dell/:id' => 'index/boss/bannerDel',//banner删除

    'boss/point/list' => 'index/boss/pointList',//文物点列表页
    'boss/point/add' => 'index/boss/pointAddOrModify',//添加文物点
    'boss/point/mod/:id' => 'index/boss/pointAddOrModify',//修改文物点
    'boss/point/save' => 'index/boss/pointSave',//修改 添加 文物点
    'boss/point/del/:id' => 'index/boss/pointDel', //删除文物点

    //  文点详情
    'boss/point/detail/:id' => 'index/boss/pagePointDetail',// 添加 或者修改文物点详情
    'boss/point/detail/save' => 'index/boss/pointDetailSave',// 文物点详情 保存 或者 添加
    'boss/point/banner/list/:id' => 'index/boss/pointBannerList', // 文物点banner详情
    'boss/point/banner/add/:pid' => 'index/boss/pointBannerAddOrSave', // 文物点banner 添加
    'boss/point/banner/mod/:id' => 'index/boss/pointBannerAddOrSave',  // 文物点banner 修改
    'boss/point/banner/save' => 'index/boss/pointBannerSave',//文物点banner ajax保存
    'boss/point/banner/dell/:id/pid/:pid'=>'index/boss/pointBannerDell', // 文物点banner 删除


    //推荐路线
    'boss/route/list' => 'index/boss/routeList',//推荐路线 列表
    'boss/route/add' => 'index/boss/routeAddOrSave',//添加 推荐路线
    'boss/route/mod/:id' => 'index/boss/routeAddOrSave',// 修改推荐路线
    'boss/route/save' => 'index/boss/routeSave', //保存 添加的ajax
    'boss/route/dell/:id' => 'index/boss/routeDell',//删除推荐路线

    'boss/route/point/list/:rid' => 'index/boss/routePointList',//推荐路线 文物点列表
    'boss/route/point/add/:rid' => 'index/boss/routePointAddOrSave',//推荐路线 添加文物点
    'boss/route/point/mod/:id' => 'index/boss/routePointAddOrSave',//推荐路线 修改文物点
    'boss/route/point/save' => 'index/boss/routePointSave',//ajax
    'boss/route/point/dell/:id/rid/:rid' => 'index/boss/routepointDell',//删除推荐路线的文物点

    //活动
    'boss/act/list' =>'index/boss/actList',
    'boss/act/add' => 'index/boss/actAddOrSave',//添加
    'boss/act/mod/:id' => 'index/boss/actAddOrSave',//修改
    'boss/act/save' => 'index/boss/actSave',//ajax 保存添加
    'boss/act/del/:id' => 'index/boss/delSave',//删除活动

    /**
     * 帮助功能
     */
    'helper/upload' => 'index/helper/Upload',//上传图片
    'helper/test' => 'index/helper/sendTest',//获取微信测试地址
    'helper/back' => 'index/helper/weixinBack',//微信测试回调地址
];
