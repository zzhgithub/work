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
        return $this->fetch('volunteer/register');

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
        return $this->fetch('volunteer/inspect');
    }

    /**
     * 文物保护培训列表页
     * @param $query
     */
    public function trainList(){
        //todo
        return $this->fetch('volunteer/train_list');

    }

    /**
     * 培训详情页
     * @param $id
     */
    public function trainDetail(){
        //todo
        return $this->fetch('volunteer/train_detail');

    }

    /**
     * 巡查反馈列表页
     * @param $query
     */
    public function inspectBackList(){
        //todo
        return $this->fetch('volunteer/inspect_back_list');

    }

    /**
     * 巡查反馈详情页
     * @param $id
     */
    public function inspectBackDetail(){
        //todo
        return $this->fetch('volunteer/inspect_back_detail');

    }

    public function certificate()
    {
        return $this->fetch('volunteer/certificate');
    }
}