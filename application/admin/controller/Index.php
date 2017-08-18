<?php
namespace app\admin\controller;

use app\common\controller\Admin;
use app\common\controller\Message;
use anerg\OAuth2\OAuth;
use anerg\helper\Exception;

class Index extends Admin
{

    /**
     * 首页
     */
    public function index(){

        return $this->fetch();
    }

    /**
     * 修改密码
     */
    public function changepwd(){
        if(IS_POST){
            $oldpasswd = I("post.oldpasswd",'');
            $newpasswd = I("post.newpasswd",'');
            $confirmpasswd = I("post.confirmpasswd",'');
            if(''==$oldpasswd){
                $code=-1;
                $msg='原密码不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if(''==$newpasswd){
                $code=-2;
                $msg='新密码不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if(''==$confirmpasswd){
                $code=-3;
                $msg='重复密码不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if(!($newpasswd==$confirmpasswd)){
                $code=-4;
                $msg='新密码要和重复密码相同';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if(!(strlen($newpasswd)>=6&&strlen($newpasswd)<=32)){
                $code=-5;
                $msg='新密码长度需要再6~32之间';
                $msgtype=MSG_TYPE_WARNING;
            }
            else {
                $check=D('Read/Userread')->checkpassword(V('curuserid'),$oldpasswd);
                if($check){
                    $ret=D('Write/Userwrite')->changepassword(V('curuserid'),$oldpasswd,$newpasswd);
                    if($ret){
                        $code=0;
                        $msg='修改密码成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        $code=-6;
                        $msg='修改密码失败';
                        $msgtype=MSG_TYPE_WARNING;
                    }
                }
                else{
                    $code=-7;
                    $msg='原密码错误';
                    $msgtype=MSG_TYPE_WARNING;
                }
            }
            echo json_encode(array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype));exit;
        }
        else{
            echo $this->fetch();exit;
        }
    }








}
