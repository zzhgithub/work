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
use think\Exception;


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

    //登录测试
    public function sendTest(){
        try{
            $appid = "wxfcc662fea0340227";
            $callbackurl = "http://www.cqlaojie.com/index.php?s=helper/back";//暂时定位测试地址
            $scope = "snsapi\_userinfo";

            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid="
                .$appid
                ."&redirect_uri="
                . urlencode($callbackurl)
                ."&response_type=code&scope="
                .$scope
                ."&state=STATE#wechat_redirect";
            header("Location:".$url);
        }catch (Exception $e){
            var_dump($e->getMessage());
        }
    }

    public function weixinBack(){
        try{
            $appid = "wxfcc662fea0340227";
            $secret = "923658e6d9f6beee3eeacce5b940d9c1";
            $code = $_GET["code"];
            $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
            $json_obj = json_decode(file_get_contents($get_token_url));
            $access_token = $json_obj->access_token;
            $openid = $json_obj->openid;
            $get_user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
            $user_obj = json_decode(file_get_contents($get_user_info_url));
            var_dump($user_obj);
        }catch (Exception $e){
            var_dump($e);
        }
    }
}