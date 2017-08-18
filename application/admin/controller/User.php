<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 用户管理
 * Enter description here ...
 * @author jogle
 * @created on 20170804
 */
class User extends Admin{

	/**
	 *  所有用户列表
	 * @param number $in 是否内部调用 0不是  1是
     * @return string
     */
	public function index($view='index'){
        $userlist=model('adminuser')->alias('a')
            ->join(config('prefix').'adminrole b'.'',' a.role_id=b.roleid','LEFT')
            ->where(['a.delflag'=>0])
            ->order('a.userid asc')
            ->field('a.*,b.rolename ')
            ->select();
        if($this->isAdmin==2){
            $userlist=$userlist;
        }
        else{
            $newuserlist=array();
            foreach($userlist as $user){
                if($user['isadmin']<=$this->isAdmin&&($user['userid']==$this->curUserId
                        ||$user['parentroleid']==$this->curRoleId)){
                    $newuserlist[]=$user;
                }
            }
            $userlist=$newuserlist;
        }
        //ar_dump($userlist);exit;
        $this->assign('userlist',$userlist);
        //var_dump($this->rolelist);exit;
        $this->setPageHeaderRightButton(
            array(
                array(
                    'class'=>'btn btn-fit-height grey-salt',
                    'onclick'=>"onclick='adduser()'",
                    'icon'=>"<i class='fa fa-plus'></i>",
                    'text'=>'添加用户')
            )
        );



        $fetchContent = $this->fetch($view);
        return $fetchContent;
	}

	/**
	 * 检查用户是否存在
	 */
	public function checkuserbyusername(){
	    if(IS_POST&&''!=input('post.username','')){
            $username = input('post.username','');
	        $ret=model('adminuser')->where(['username'=>$username,'delflag'=>0])->find();
	        if($ret){
	           $valid=false;
	           $msg="用户已存在";
	        }
	        else{
	           $valid=true;
	           $msg="";
	        }
	    }
	    else{
	        $valid=false;
	        $msg="参数错误，验证失败";
	    }
	    echo json_encode(array('valid'=>$valid,'message'=>$msg));exit;
	}
	
	/**
	 * 添加用户
	 */
	public function adduser(){
		if(IS_POST){
            $userinfo = array();
            $userinfo["username"] = input("post.username",'');
            $userinfo["realname"] = input("post.realname",'');
            $userinfo["passwd"] = input("post.passwd",'');
            $userinfo["confirmpasswd"] = input("post.confirmpasswd",'');
            $userinfo["role_id"] = input("post.role_id",0,"intval");
            $userinfo["nickname"] = input("post.nickname",'');
            $userinfo["status"] = input("post.status",0,"intval");
            $userinfo["sort"] = input("post.sort",0,"intval");
            $userinfo["gender"] = input("post.gender",1,"intval");
            $userinfo["birthday"] = input("post.birthday",'');
            if($userinfo["birthday"]!=''){
                $userinfo["birthday"]=date("Y/m/d",strtotime($userinfo["birthday"]));
            }
            $userinfo["phone"] = input("post.phone",'');
            $userinfo["cellphone"] = input("post.cellphone",'');
            $userinfo["email"] = input("post.email",'');
            $userinfo["qq"] = input("post.qq",'');
            
            if(''==$userinfo['username']){
                $code=-1;
                $msg='用户名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(strpos($userinfo['username'], ',')!==false){
                $code=-2;
                $msg='用户名称中不能含有字符","';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$userinfo['realname']){
                $code=-3;
                $msg='真名不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$userinfo['passwd']){
                $code=-4;
                $msg='密码不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$userinfo['confirmpasswd']){
                $code=-5;
                $msg='确认密码不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif($userinfo['passwd']!=$userinfo['confirmpasswd']){
                $code=-6;
                $msg='密码和确认密码要一致';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($userinfo['role_id']>0)){
                $code=-7;
                $msg='角色参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($userinfo['status']==0 || $userinfo['status']==1)){
                $code=-8;
                $msg='状态参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($userinfo['gender']>=0 && $userinfo['gender']<=2)){
                $code=-9;
                $msg='性别参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!$this->validateDate($userinfo['birthday'],'Y/m/d')){
                $code=-10;
                $msg='生日参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                $userinfo["delflag"] = 0;
                $userinfo['passwd']=$this->passwdmd5($userinfo['passwd']);
                unset($userinfo['confirmpasswd']);
                $isExist=model('adminuser')->where(['username'=>$userinfo["username"],'delflag'=>0])->find();
                
                if($isExist){
                    $code=-11;
                    $msg='用户已经存在';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{
                    $userid = model('adminuser')->insertGetId($userinfo);
                    if($userid>0){
                        $code=0;
                        $msg='用户添加成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        $code=-12;
                        $msg='用户保存失败';
                        $msgtype=MSG_TYPE_DANGER;
                    }
                }
            }
            $ret=array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);
            if(0==$code){
                $ret['html']=$this->index('index_body');
            }
            echo json_encode($ret);exit;
		}
		else{
			$rolelist=model('adminrole')->where(['delflag'=>0])->select();
            if($this->isAdmin==2){
                $newrolelist=$rolelist;
            }
            else{
                $newrolelist=array();
                foreach($rolelist as $role){
                    if($role['isadmin']<=$this->isAdmin&&($role['roleid']==$this->curRoleId
                            ||$role['parentroleid']==$this->curRoleId)){
                        $newrolelist[]=$role;
                    }
                }
            }
            $this->assign('rolelist',$newrolelist);

			$defaultsort=model('adminuser')->where(['delflag'=>0])->max('sort');

            $this->assign('defaultsort',$defaultsort+1);
			echo $this->fetch();exit;
		}
	}
	
	/**
	 * 修改用户
	 */
	public function edituser(){
		if(IS_POST){
			$userinfo = array();
			$userinfo["userid"] = input("post.userid",0);
			$userinfo["realname"] = input("post.realname",'');
			$userinfo["passwd"] = input("post.passwd",'');
			$userinfo["confirmpasswd"] = input("post.confirmpasswd",'');
			$userinfo["role_id"] = input("post.role_id",0,"intval");
			$userinfo["nickname"] = input("post.nickname",'');
            $userinfo["status"] = input("post.status",0,"intval");
			$userinfo["sort"] = input("post.sort",0,"intval");
			$userinfo["gender"] = input("post.gender",1,"intval");
			$userinfo["birthday"] = input("post.birthday",'');
			if($userinfo["birthday"]!=''){
				$userinfo["birthday"]=date("Y/m/d",strtotime($userinfo["birthday"]));
			}
			$userinfo["phone"] = input("post.phone",'');
			$userinfo["cellphone"] = input("post.cellphone",'');
			$userinfo["email"] = input("post.email",'');
			$userinfo["qq"] = input("post.qq",'');
            $userinfo["updatetime"] = $this->curDateTime2;
            if(!($userinfo['userid']>0)){
                $code=-1;
                $msg='用户id不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$userinfo['realname']){
                $code=-2;
                $msg='真名不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif($userinfo['passwd']!=$userinfo['confirmpasswd']){
                $code=-3;
                $msg='密码和确认密码要一致';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($userinfo['role_id']>0)){
                $code=-4;
                $msg='角色参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($userinfo['status']==0||$userinfo['status']==1)){
                $code=-5;
                $msg='状态参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($userinfo['gender']>=0&&$userinfo['gender']<=2)){
                $code=-6;
                $msg='性别参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!$this->validateDate($userinfo['birthday'],'Y/m/d')){
                $code=-7;
                $msg='生日参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                unset($userinfo['confirmpasswd']);
                if(''==$userinfo['passwd']){
                    unset($userinfo['passwd']);
                }
                else{
                    $userinfo['passwd']=$this->passwdmd5($userinfo['passwd']);
                }
                $isExist=model('adminuser')
                    ->where(['userid'=>$userinfo["userid"],'delflag'=>0])->find();
                if(!$isExist){
                    $code=-8;
                    $msg='用户不存在';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{
                    $ret = model('adminuser')
                        ->where(['userid'=>$userinfo['userid']])->update($userinfo);
                    if($ret>0||0===$ret){
                        $code=0;
                        $msg='用户保存成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        $code=-9;
                        $msg='用户保存失败';
                        $msgtype=MSG_TYPE_DANGER;
                    }
                }
            }
            $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);
			if(0==$code){
                $ret['html']=$this->index('index_body');
			}
			echo json_encode($ret);exit;
		}
		else{
			$userid = input("get.userid",0);
			if(!($userid>0)){
				echo '用户参数错误';exit;
			}
			$userinfo=model('adminuser')->where(['userid'=>$userid,'delflag'=>0])->find();
            if(!$userinfo){
                echo '用户已被删除或不存在';exit;
            }
            if(!$userinfo['birthday']){
                $userinfo['birthday']='1990/01/01';
            }
            $this->assign('userinfo',$userinfo);
            //var_dump($this->userinfo);exit;
			$rolelist=model('adminrole')->where(['delflag'=>0])->select();
            if($this->isAdmin==2){
                $newrolelist=$rolelist;
            }
            else{
                $newrolelist=array();
                foreach($rolelist as $role){
                    if($role['isadmin']<=$this->isAdmin&&($role['roleid']==$this->curRoleId
                            ||$role['parentroleid']==$this->curRoleId)){
                        $newrolelist[]=$role;
                    }
                }
            }

            $this->assign('rolelist',$newrolelist);
			echo $this->fetch();exit;
		}
	}

	/**
	 * 删除用户
	 */
	public function removeuser(){
		$userid = input("post.userid",0);
        if($userid>0){
            $isExist=model('adminuser')->where(['userid'=>$userid,'delflag'=>0])->find();
            if($isExist&&$userid!=$this->curUserId){
                $ret=model('adminuser')->where(['userid'=>$userid])
                    ->update(['delflag'=>1,'updatetime'=>$this->curDateTime2]);
                if($ret>0){
                    $code=0;
                    $msg='删除成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-1;
                    $msg='删除失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            else{
                $code=-2;
                $msg='您要删除的用户不存在或已被删除';
                $msgtype=MSG_TYPE_WARNING;
            }
        }
        else{
            $code=-3;
            $msg='参数错误';
            $msgtype=MSG_TYPE_DANGER;
        }
        $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);
		echo json_encode($ret);exit;
	}
}