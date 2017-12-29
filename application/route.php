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
    'point/detail/:id' => 'index/show/ponitDetail',//文物点 详情
    'route/list' => 'index/show/pathList',//推荐路线列表
    'route/detail/:id' => 'index/show/pathDetail',//推荐路线详情
    'act/list' => 'index/activity/activityList',//活动列表
    'act/detail/:id' => 'index/activity/activityDetail', //活动详情
    'act/join/:id' => 'index/activity/joinActivity', //活动报名
    'act/dojoin' => 'index/activity/doJoin', //活动报名
    'act/fail' => 'index/activity/failJoin', //取消活动支付
    'ucenter' => 'index/person/index', //个人中心
    'register' => 'index/volunteer/register', //信息提交
    'train/list' => 'index/volunteer/trainlist', //培训列表
    'train/detail/:id' => 'index/volunteer/trainDetail', //培训详情
    'inspect/back/list' => 'index/volunteer/inspectbacklist', //巡查反馈列表
    'inspect/back/detail/:id' => 'index/volunteer/inspectbackdetail', //巡查反馈详情
    'inspect' => 'index/volunteer/inspect', //文物巡查

    'about' => 'index/about/index', //关于我们
    'certificate' => 'index/volunteer/certificate', //任务点证书


    /**
     * 捐款
     */
    'donate/list' => 'index/donatectl/donateList',
    'donate/detail/:id' => 'index/donatectl/donateDetail',
    'donate/do/:id' => 'index/donatectl/donateDo',
    'donate/save' => 'index/donatectl/donateSave',
    'donate/fail' => 'index/donatectl/donateFail',

    /**
     * 产品
     */
    'product/list' => 'index/productctl/productList',
    'product/detail/:id' => 'index/productctl/productDetail',
    'product/addcart' => 'index/productctl/productModCart',
    'product/cart' => 'index/productctl/productCart',
    'product/order' => 'index/productctl/productCart',
    'product/pay' => 'index/productctl/pay',
    'product/fail' => 'index/productctl/fail',

    /**
     * boss 后台相关功能
     */
    'boss/index' => 'index/boss/index',//banner 列表

    'boss/banner/list' => 'index/boss/bannerList',//banner 列表
    'boss/banner/add'  => 'index/boss/bannerAddOrModify',//banner添加页
    'boss/banner/mod/:id'  => 'index/boss/bannerAddOrModify',//banner修改页
    'boss/banner/save' => 'index/boss/bannerSave',//banner的保存和更新
    'boss/banner/del/:id' => 'index/boss/bannerDel',//banner删除

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
    'boss/point/banner/del/:id/pid/:pid'=>'index/boss/pointBannerDell', // 文物点banner 删除


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
    'boss/route/point/del/:id/rid/:rid' => 'index/boss/routepointDell',//删除推荐路线的文物点

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
    'notify' => 'index/helper/notify',//微信测试回调地址

    /**
     * 捐款
     */
    'boss/donate/list' =>'index/boss/donateList',
    'boss/donate/add' =>'index/boss/donateSave',
    'boss/donate/mod/:id' =>'index/boss/donateSave',
    'boss/donate/state' =>'index/boss/donateState',
    'boss/upload' =>'index/boss/upload',

    /**
     * 文创产品
     */
    'boss/product/list' =>'index/boss/productList',
    'boss/product/add' =>'index/boss/productSave',
    'boss/product/mod/:id' =>'index/boss/productSave',
    'boss/product/state' =>'index/boss/productState',
    'boss/productimg/del' =>'index/boss/productImgDel',

    /**
     * 公告管理
     */
    'boss/news/list' =>'index/boss/newsList',
    'boss/news/add' =>'index/boss/newsSave',
    'boss/news/mod/:id' =>'index/boss/newsSave',
    'boss/news/del' =>'index/boss/newsDel',

    /**
     * 关于我们
     */
    'boss/about' =>'index/boss/about',

    /**
     * 反馈管理
     */
    'boss/inspect/list' => 'index/boss/InspectList',
    'boss/inspect/add' =>'index/boss/inspectSave',
    'boss/inspect/mod/:id' =>'index/boss/inspectSave',
    'boss/inspect/del'=>'index/boss/inspectDel',
    /**
     * 培训管理
     */
    'boss/train/catelist' =>'index/boss/catelist',
    'boss/train/cateadd' =>'index/boss/catesave',
    'boss/train/catemod/:id' =>'index/boss/catesave',
    'boss/train/catedel' =>'index/boss/catedel',

    'boss/train/list' =>'index/boss/trainlist',
    'boss/train/add' =>'index/boss/trainsave',
    'boss/train/mod/:id' =>'index/boss/trainsave',
    'boss/train/del' =>'index/boss/traindel',

    /**
     * 证书管理
     */
    'boss/cert/list' =>'index/boss/certList',
    'boss/cert/add' =>'index/boss/certSave',
    'boss/cert/mod/:id' =>'index/boss/certSave',
    'boss/cert/del' =>'index/boss/certDel',

    /**
     * 附近文物点管理
     */
    'boss/near/list' => 'index/boss/nearList',
    'boss/near/add' => 'index/boss/nearSave',
    'boss/near/mod/:id' => 'index/boss/nearSave',
    'boss/near/del' => 'index/boss/nearDel',
    /**
     * 登录 登出
     */
    'boss/login' =>'index/BossLogin/login',
    'boss/logout' =>'index/BossLogin/logout',
    'boss/verify' =>'index/BossLogin/verify',
    'boss/modify' =>'index/boss/modify',

    /**
     * 订单相关
     */
    'boss/order/product' =>'index/boss/proOrder',
    'boss/order/act' =>'index/boss/actOrder',
    'boss/order/donate' =>'index/boss/donOrder',
    'boss/order/attention' =>'index/boss/addOrderAttention'
];
