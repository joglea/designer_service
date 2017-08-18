<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\controller;


class Admin extends Base {


    public $curUserInfo = [];

    public $curUserId = 0;
    public $curUserName = '';
    public $curRoleName = '';
    public $curRoleId = 0;
    public $isAdmin = 0;
    public $parentRoleId = 0;
    public $staticUrl = '';
    public $curDate = '';
    public $curDateTime1 = '';
    public $curDateTime2 = '';

    public $curaction = '';
    public $curfunction = '';
    public $curmoduleid = 0;
    public $curmodulerightid = 0;
    public $curmodulename = '';
    public $curmoduleurl = '';
    public $curmodulegroupid = 0;
    public $curmodulegroupname = '';


    public $curTime = 0;

    public $allControl = [];
    public $allServiceType = [];

	public function _initialize() {
		parent::_initialize();
        header('Content-type: text/html; charset=utf-8');

        header("Access-Control-Allow-Origin: *");

        $lifetime=7200;//保存120分钟
        session_start();
        setcookie(session_name(),session_id(),time()+$lifetime,"/");

        $this->assign(['title'=>'xxx']);


        $this->assign(['curusername'=>'xxx']);
        $this->assign(['currolename'=>'aaa']);


        $this->checklogin();//检查是否登录

        $this->allControl = $this->getAllControl();
        //var_dump($this->allControl);exit;
        $this->checkright();//检查是否有权限

        $this->syslog([]);    //记录系统日志

        //var_dump($this->currolename);exit;
        //静态文件Url的host
        $this->staticUrl='//'.config('STATIC_HOST').'/';

        $this->curTime = time();
        //当前日期 年/月/日
        $this->curDate=date('Y/m/d',$this->curTime);
        //当前时间 年/月/日 时:分:秒
        $this->curDateTime1=date('Y/m/d H:i:s',$this->curTime);
        //当前时间 年-月-日 时:分:秒
        $this->curDateTime2=date('Y-m-d H:i:s',$this->curTime);


	}

    public function getAllControl(){
        if(!$this->allControl){
            //获取控制参数
            $controls = model('control')->all();
            foreach($controls as $v){
                $this->allControl[$v['controlk']] = $v['controlv'];
            }
        }
        return $this->allControl;
    }


    /**
     * 检查是否登录
     */
    public function checklogin(){
        if(session('userinfo')){
            $userinfo = session('userinfo');
            //var_dump($userinfo['userid']);exit;
            $this->curUserId=$userinfo['userid'];
            $this->curUserName=$userinfo['username'];
            $this->assign('curUserName',$this->curUserName);
            //var_dump($userinfo['adminrole']);exit;
            $this->curRoleId=$userinfo['adminrole']['roleid'];
            $this->curRoleName=$userinfo['adminrole']['rolename'];
            $this->assign('curRoleName',$this->curRoleName);
            $this->isAdmin=$userinfo['adminrole']['isadmin'];
            $this->assign('isAdmin',$this->isAdmin);
            //var_dump( $this->isAdmin);exit;
            $this->parentRoleId=$userinfo['adminrole']['parentroleid'];
            //var_dump($this->currolename);exit;
            //静态文件Url的host
            $this->staticUrl='//'.config('STATIC_HOST').'/';
            $this->assign('staticUrl',$this->staticUrl);
            //当前日期 年/月/日
            $this->curDate=date('Y/m/d',time());
            $this->assign('curDate',$this->curDate);
            //当前时间 年/月/日 时:分:秒
            $this->curDateTime=date('Y/m/d H:i:s',time());
            $this->assign('curDateTime',$this->curDateTime);
            //当前时间 年-月-日 时:分:秒
            $this->curDateTime1=date('Y-m-d H:i:s',time());
            $this->assign('curDateTime1',$this->curDateTime1);


        }
        else{
            //return true;var_dump(ACTION_NAME,CONTROLLER_NAME,MODULE_NAME);exit;
            //重新跳转到的登录页地址
            $loginUrl = '//'.config('MAIN_HOST').'/admin/Login/index?sess_is_expire=1';
            if(IS_AJAX){
                if(IS_POST){
                    echo json_encode(array('code'=>-1,'msg'=>'SESSION过期，<a href="'.$loginUrl.'">点此</a>重新登录','msg_type'=>MSG_TYPE_DANGER,'html'=>'','data'=>''));
                }
                if(IS_GET){
                    echo 'SESSION过期，<a href="'.$loginUrl.'">点此</a>重新登录';
                }
                exit;
            }
            else{
                header('location:'.$loginUrl);
                exit;
            }

        }
    }

    /**
     * 检查是否有权限
     */
    public function checkright(){

        //账号有的所有权限
        $moduleright = session("userinfo")["modulerightinfo"];
        //var_dump($moduleright);exit;
        //是否有权限
        $hasright = false;
        if($this->isAdmin==2){
            $hasright=true;
        }

        $modulegroupidsarr=array(0);
        //账号所有有权限的模块权限数组  用于各个action操作时  是否禁用相关按钮
        $modulerightarr=array();
        //var_dump(MODULE_NAME,ACTION_NAME);exit;
        $menumodulegrouplist=array();
        //当前控制器的名称
        $this->curaction=CONTROLLER_NAME;
        //当前action的名称
        $this->curfunction=ACTION_NAME;
        $veriyIdentityName = CONTROLLER_NAME;

        foreach ($moduleright as $k=>$right){
            $modulerightarr[$right["identityname"]][$right["rightename"]]=1;
            //判断是否有当前控制器和Action的权限
            if(strtolower($right["identityname"])==strtolower($veriyIdentityName) &&
                strtolower($right["rightename"])==strtolower(ACTION_NAME)){
                $hasright = true;
                $this->curmodulerightid=$right["modulerightid"];
            }
//var_dump($right);exit;
            if($this->curmoduleid == 0 && strtolower($right["identityname"]) == strtolower($veriyIdentityName)){
                $this->curmoduleid = $right['module_id'];
                $this->curmodulename = $right['modulename'];
                $this->curmoduleurl = $right['url'];
                $this->curmodulegroupid = $right['module_group_id'];
                $this->curmodulegroupname = $right['modulegroupname'];
            }

            if(!in_array($right['module_group_id'],$modulegroupidsarr )){
                $modulegroupidsarr[]=$right['module_group_id'];
                $menumodulegrouplist[$right['module_group_id']]['modulegroupname'] = $right['modulegroupname'];
                $menumodulegrouplist[$right['module_group_id']]['modulegroupicon'] = $right['modulegroupicon'];
                $menumodulegrouplist[$right['module_group_id']]['url'] =
                    '/index.php/admin/'.$right['identityname'].'/'.$right['rightename'];
            }
            if(!isset($menumodulegrouplist[$right['module_group_id']]['modulelist'][$right['module_id']])){
                $menumodulegrouplist[$right['module_group_id']]['modulelist'][$right['module_id']]=array(
                    "modulename" => $right['modulename'],
                    "moduleicon" => $right['moduleicon'],
                    "url" => '/index.php/admin/'.$right['identityname'].'/'.$right['rightename']
                );
                $menumodulegrouplist[$right['module_group_id']]['modulecount']=
                    count($menumodulegrouplist[$right['module_group_id']]['modulelist']);
            }


        }
        //var_dump($modulegroupidsarr,$moduleright,$modulerightarr,$menumodulegrouplist);
        //var_dump($menumodulegrouplist);exit;
        //var_dump($this->curmoduleid);
        //var_dump($menumodulegrouplist);exit;
        $this->menumodulegrouplist=$menumodulegrouplist;
        //账号所有有权限的模块权限数组  用于各个action操作时  是否禁用相关按钮
        $this->modulerightarr=$modulerightarr;
        //页面标题  默认为当前模块名称
        $this->title = $this->curmodulename;
        $this->assign('menumodulegrouplist',$menumodulegrouplist);
        $this->assign('modulerightarr',$modulerightarr);
        $this->assign('title',$this->curmodulename);

        $this->assign('curmoduleid',$this->curmoduleid);
        $this->assign('curmodulename',$this->curmodulename);
        $this->assign('curmoduleurl',$this->curmoduleurl);
        $this->assign('curmodulegroupid',$this->curmodulegroupid);
        $this->assign('curmodulegroupname',$this->curmodulegroupname);
        $this->setPageHeaderRightButton([]);

        //$this->assign("header",$this->fetch("Common:header"));
        //$this->assign("pageHeaderFile",$this->fetch("Common:pageheader",'',''));
        //$this->assign("contentFile",$this->fetch($templateFile,'',''));
        //$this->assign("content",$this->fetch("Common:content",'',''));

        //$this->assign("menu",$this->fetch("Common:menu",'',''));

        //$this->assign("footer",$this->fetch("Common:footer",'',''));
        //$this->setNavi();
        //$hasright = true;
        if(!$hasright){
            if(IS_AJAX && !IS_GET){
                echo json_encode(array('code'=>-2,'msg'=>'您没有权限做此项操作','msg_type'=>MSG_TYPE_DANGER,'html'=>'','data'=>''));
                exit;
            }
            else{
                echo "您没有权限做此项操作";exit;
                exit;
            }
        }
    }



    /**
     *页面主体部分中的顶部右侧按钮
     * @param array $pageHeaderRightButton
     * $naviRightButton 示例：
     * array(
    array(
    "text"=>"添加",
    "href"=>"javascript:;",//可以是链接
    "class"=>"btn btn-primary btn-sm",
    "onclick"=>"onclick='addModuleRight(".$this->moduleId.")'",
    "icon"=>"<i class='fa fa-plus'></i>"
    )
    )
     */
    protected function setPageHeaderRightButton($pageHeaderRightButton=array()){
        $newpageHeaderRightButton='';
        foreach($pageHeaderRightButton as $v){
            if(!isset($v['class'])){
                $v['class']="btn btn-primary btn-sm";
            }
            if(!isset($v['href'])){
                $v['href']="javascript:;";
            }
            if(!isset($v['onclick'])){
                $v['onclick']="";
            }
            if(!isset($v['icon'])){
                $v['icon']="";
            }
            $id = "";
            if(isset($v['id'])){
                $id = " id=\"".$v['id']."\" ";
            }
            $newpageHeaderRightButton .= "&nbsp;&nbsp;<a ".$id." class='".$v['class']."' href='".$v['href']."' ".$v['onclick']." >".$v['icon'].$v['text']."</a>";
        }
        $this->assign('pageHeaderRightButton',$newpageHeaderRightButton);
    }





    /**
     * 记录系统操作日志
     */
    public function sysLog($res){
        $loginfo = [
            'creatorid'=>$this->curUserId,
            'version'=>1,
            'description'=>json_encode($_GET).json_encode($_POST),
            'return'=>json_encode($res),
            'ip'=>get_client_ip(),
            'moduleidentity'=>CONTROLLER_NAME,
            'rightname'=>ACTION_NAME,
            'createtime'=>time(),

        ];
        model('syslog')->insertGetId($loginfo);
    }



    //生成sid
    public function makesid(){

        return md5(uniqid().rand(100000,1000000).time());
    }

    public function logResult($word='word',$file='') {
        $fp = $file==''?fopen("/tmp/qiniulog.txt","a"):fopen($file,"a");
        flock($fp, LOCK_EX) ;
        fwrite($fp,"time:".date("YmdHis", time())."\n".$word."\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }



}
