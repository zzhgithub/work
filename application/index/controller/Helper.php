<?php
namespace app\index\controller;
/**
 * 帮助类
 * Created by PhpStorm.
 * User: zhouzihao
 * Date: 2017/11/19
 * Time: 下午6:06
 */

use think\Controller;


class Helper extends Controller
{
    public function Upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
    // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');
      if($info){
          // 成功上传后 获取上传信息
          // 输出 jpg
          //echo $info->getExtension();
          // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg echo $info->getSaveName();
          // 输出 42a79759f284b767dfcb2a0197904287.jpg
          echo $info->getSaveName();
          //echo $info->getFilename();
          //echo $info->getSaveName();
    }else{
          //上传失败
          echo $file->getError();
      }
    }
}