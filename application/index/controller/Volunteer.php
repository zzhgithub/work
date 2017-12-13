<?php
namespace app\index\controller;
/**
 * 文物志愿者
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午5:24
 */
use think\Controller;

class Volunteer extends Controller
{
    /**
     * @deprecated
     * 文物注册页
     */
    public function register(){

    }

    /**
     * @deprecated
     * post 请求提交注册请求
     */
    public function doRegister(){

    }

    /**
     * 文物巡查页 加载固定模板
     */
    public function inspect(){
        //todo
    }

    /**
     * 文物保护培训列表页
     * @param $query
     */
    public function trainList($query){
        //todo
    }

    /**
     * 培训详情页
     * @param $id
     */
    public function trainDetail($id){
        //todo
    }

    /**
     * 巡查反馈列表页
     * @param $query
     */
    public function inspectBackList($query){
        //todo
    }

    /**
     * 巡查反馈详情页
     * @param $id
     */
    public function inspectBackDetail($id){
        //todo
    }
    //todo 这里的数据哪里来 还有为什么不显示登录状态
}