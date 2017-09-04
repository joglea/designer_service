<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 角色管理
 * Enter description here ...
 * @author jogle
 * @created on 20170804
 */
class Role extends Admin{
	/**
	 * 角色列表
	 * @param number $in 是否内部调用 0不是  1是
     * @return string
     */
	public function index($view='index'){
        //var_dump(V('curisadmin'));exit;
        if($this->isAdmin==2){
            $where=['a.delflag'=>0];
        }
        else{
            $where=['isadmin'=>array('lt',$this->isAdmin),
                         'parentroleid'=>$this->curRoleId,'a.delflag'=>0];
        }

		$rolelist=model('adminrole')->alias('a')
            ->join(config('prefix').'adminrole b'.'',' a.parentroleid=b.roleid','LEFT')
            ->where($where)
            ->order('a.isadmin desc,a.rolesort,a.roleid')
            ->field('a.*,b.rolename as parentrolename')
            ->select();
        //var_dump(model('adminrole')->getLastSql());exit;
        $this->assign('rolelist',$rolelist);
		//var_dump($this->rolelist);exit;
		$this->setPageHeaderRightButton(
            array(
                array(
                    'class'=>'btn btn-fit-height grey-salt',
                    'onclick'=>"onclick='addrole(0)'",
                    'icon'=>"<i class='fa fa-plus'></i>",
                    'text'=>'添加角色')
            )
		);

        $fetchContent = $this->fetch($view);

        return $fetchContent;
	}

	/**
	 * 添加角色
	 */
	public function addrole(){
		if($_POST){
			$roleinfo = array();
			$roleinfo['rolename'] = input('post.rolename','');
            $roleinfo['isadmin']=input('post.isadmin');
            $roleinfo['parentroleid']=$this->curRoleId;
			$roleinfo['roledescription'] = input('post.roledescription','');
			$roleinfo['rolesort'] = input('post.rolesort',1);
			
            if(''==$roleinfo['rolename']){
                $code=-1;
                $msg='角色名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if(model('adminrole')->where(['rolename'=>$roleinfo['rolename'],'delflag'=>0])->value('roleid')>0){
                $code=-2;
                $msg='角色名称已经存在';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if($roleinfo['isadmin']>2||$roleinfo['isadmin']<0){
                $code=-3;
                $msg='角色类型参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if($this->isAdmin!=2&&$roleinfo['isadmin']>=$this->isAdmin){
                $code=-4;
                $msg='角色类型不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                $roleinfo['delflag'] = 0;
                $roleid = model('adminrole')->insertGetId($roleinfo);
                if($roleid>0){
                    $modulelist=model('adminmodule')->where(['delflag'=>0])->select();
                    $adddatalist=array();
                    foreach ($modulelist as $module){

                        $moudlerightvaluearr = isset($_POST['module_right_'.$module['moduleid']])?
                            $_POST['module_right_'.$module['moduleid']]:[];
                        $rightvalue = array_sum($moudlerightvaluearr);
                        $adddata=array('role_id' => $roleid,
                            'module_id' => $module['moduleid'],
                            'rightvalue' => $rightvalue,
                            'delflag'=>0
                        );
                        $adddatalist[]=$adddata;
                    }
                    if($adddatalist){
                        model('adminrolemodule')->startTrans();
                        //var_dump(3);exit;
                        $ret=model('adminrolemodule')->insertAll($adddatalist);
                        if($ret){
                            model('adminrolemodule')->commit();
                            $code=0;
                            $msg='角色权限添加成功';
                            $msgtype=MSG_TYPE_SUCCESS;
                        }
                        else{
                            model('adminrolemodule')->rollback();
                            $code=-3;
                            $msg='角色添加成功，权限添加失败';
                            $msgtype=MSG_TYPE_WARNING;
                        }
                    }
                    else{
                        $code=-4;
                        $msg='角色添加成功，因为未选择任何权限，所以未添加任何权限';
                        $msgtype=MSG_TYPE_WARNING;
                    }
                }
                else{
                    $code=-5;
                    $msg='角色添加失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            $ret=array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);
			if(0==$code){
				$ret['html']=$this->index('index_body');
			}
			echo json_encode($ret);exit;
		}
		else{
            $modulegrouplist=model('adminmodulegroup')->where(['delflag'=>0])->select();
            $module=model('adminmodule')->where(['delflag'=>0])->select();

            $moduleright=model('adminmoduleright')->where(['delflag'=>0])->select();
            $currolemodule=model('adminrolemodule')->where(['role_id'=>$this->curRoleId,'delflag'=>0])->select();

            $temp=array();
            foreach($module as $v0){
                $temp[$v0['module_group_id']][]=$v0;
            }
            $module=$temp;
            //var_dump($module);exit;
            $temp=array();
            foreach($moduleright as $v0){
                $temp[$v0['module_id']][]=$v0;
            }
            $moduleright=$temp;
            
            $temp=array();
            foreach($currolemodule as $v0){
                $temp[$v0['module_id']]=$v0;
            }
            $currolemodule=$temp;

            $modulegrouptree=array();

            //var_dump($rolemodule);exit;
            foreach($modulegrouplist as $k1=>$v1){
                $tempModuleList = [];
                if(isset($module[$v1['modulegroupid']])){
                    foreach($module[$v1['modulegroupid']] as $k2=>$v2){
                        $tempModuleRightList = [];
                        if(isset($moduleright[$v2['moduleid']])){
                            foreach($moduleright[$v2['moduleid']] as $k3=>$v3){
                                if($v3['module_id']==$v2['moduleid']){
                                    /*if(intval($v3['right_value'])&intval($rolemodule[$v3['module_id']]['rightvalue'])){
                                        $v3['has_right']=1;
                                    }
                                    else{
                                        $v3['has_right']=0;
                                    }*/
                                    //是否有修改的权限
                                    if(intval($v3['rightvalue']) & intval($currolemodule[$v3['module_id']]['rightvalue'])){
                                        $moduleright[$v2['moduleid']][$k3]['has_edit_right']=1;
                                    }
                                    else{
                                        $moduleright[$v2['moduleid']][$k3]['has_edit_right']=0;
                                    }
                                    if($this->isAdmin==2){
                                        $moduleright[$v2['moduleid']][$k3]['has_edit_right']=1;
                                    }
                                    //var_dump($module[$v1['modulegroupid']][$k2],$moduleright[$v2['moduleid']][$k3]);
                                    $tempModuleRightList[]=$moduleright[$v2['moduleid']][$k3];
                                }
                            }
                            $module[$v1['modulegroupid']][$k2]['modulerightlist'] = $tempModuleRightList;
                            if($v2['module_group_id']==$v1['modulegroupid']){
                                $tempModuleList[]=$module[$v1['modulegroupid']][$k2];
                            }
                        }
                    }
                    $modulegrouplist[$k1]['modulelist'] = $tempModuleList;
                    $modulegrouptree[$v1['modulegroupid']]=$modulegrouplist[$k1];
                }
            }
            $modulegrouptree=$modulegrouptree;
            $defaultsort=model('adminrole')->where(['delflag'=>0])->max('rolesort');
            $this->assign('modulegrouptree',$modulegrouptree);
            $this->assign('defaultsort',$defaultsort+1);
			echo $this->fetch();exit;
		}
	}

	/**
	 * 修改角色
	 */
	public function editrole(){
		if($_POST){
			$roleinfo = array();
			$roleinfo['roleid'] = input('post.roleid',0);
			$roleinfo['rolename'] = input('post.rolename','');
            $roleinfo['isadmin']=input('post.isadmin');
            //$roleinfo['parentroleid']=V('curisadmin')==2?0:V('curroleid');
			$roleinfo['roledescription'] = input('post.roledescription','');
			$roleinfo['rolesort'] = input('post.rolesort',1);
            if(''==$roleinfo['rolename']){
                $code=-1;
                $msg='角色名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if(model('adminrole')->where(
                    ['rolename'=>$roleinfo['rolename'],'delflag'=>0,'roleid'=>['neq',$roleinfo['roleid']]])->value('roleid')>0){
                $code=-2;
                $msg='角色名称已经存在';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if(!($roleinfo['roleid']>0)){
                $code=-3;
                $msg='参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if($roleinfo['isadmin']>2||$roleinfo['isadmin']<0){
                $code=-4;
                $msg='角色类型参数错误';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if($this->isAdmin!=2&&$roleinfo['isadmin']>=$this->isAdmin){
                $code=-5;
                $msg='角色类型不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                $ret = model('adminrole')->where(['roleid'=>$roleinfo['roleid']])->update($roleinfo);
                if($ret>0||0===$ret){
                    $modulelist=model('adminmodule')->where(['delflag'=>0])->select();
                    //$adddatalist=array();
                    $isSuccess=1;
                    model('adminrolemodule')->startTrans();
                    foreach ($modulelist as $module){
                        $moudlerightvaluearr = isset($_POST['module_right_'.$module['moduleid']])?
                            $_POST['module_right_'.$module['moduleid']]:[];

                        $rightvalue = array_sum($moudlerightvaluearr);
                        $ret=model('adminrolemodule')->where(
                            ['role_id'=>$roleinfo['roleid'],'module_id'=>$module['moduleid'],'delflag'=>0])
                            ->find();
                        //var_dump($roleinfo['roleid'],$module['moduleid'],$ret);exit;
                        if($ret){

                            $ret1=model('adminrolemodule')
                                ->where([
                                    'role_id' => $roleinfo['roleid'],
                                    'module_id' => $module['moduleid']])
                                ->update(['rightvalue' => $rightvalue]);
                            //var_dump(model('adminrolemodule')->getlastsql());
                            if(!($ret1>0||0===$ret1)){
                                $isSuccess=0;
                                break;
                            }
                        }
                        else{
                            $adddata=array('role_id' => $roleinfo['roleid'],
                                'module_id' => $module['moduleid'],
                                'rightvalue' => $rightvalue,
                                'createtime'=>date('Y-m-d H:i:s'),
                                'delflag'=>0
                            );
                            $ret1=model('adminrolemodule')->insertGetId($adddata);
                            if(!($ret1>0||0===$ret1)){
                                $isSuccess=0;
                                break;
                            }
                        }
                    }
                    if($isSuccess){
                        model('adminrolemodule')->commit();
                        $code=0;
                        $msg='角色权限修改成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        model('adminrolemodule')->rollback();
                        $code=-4;
                        $msg='角色权限修改失败';
                        $msgtype=MSG_TYPE_WARNING;
                    }
                }
                else{
                    $code=-5;
                    $msg='角色信息修改失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            $ret=array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);
			if(0==$code){
				$ret['html']=$this->index('index_body');
			}
			echo json_encode($ret);exit;
		}
		else{
			$roleid = input('get.roleid',0);

            $this->assign('roleid',$roleid);
            //var_dump($roleid);
			if(!($roleid>0)){
				echo '参数错误';exit;
			}
			$roleinfo=model('adminrole')->where(['roleid'=>$roleid,'delflag'=>0])->find();

            $this->assign('roleinfo',$roleinfo);
            if(!$roleinfo){
				echo '角色已被删除或不存在';exit;
			}
			$defaultsort=model('adminrole')->where(['delflag'=>0])->max('rolesort');

            $this->assign('defaultsort',$defaultsort+1);
            $modulegrouplist=model('adminmodulegroup')->where(['delflag'=>0])->select();
            $module=model('adminmodule')->where(['delflag'=>0])->select();

            $moduleright=model('adminmoduleright')->where(['delflag'=>0])->select();
            $currolemodule=model('adminrolemodule')->where(['role_id'=>$this->curRoleId,'delflag'=>0])->select();


            $rolemodule=model('adminrolemodule')->where(['role_id'=>$roleid,'delflag'=>0])->select();

            $temp=array();
            foreach($module as $v0){
                $temp[$v0['module_group_id']][]=$v0;
            }
            $module=$temp;

            $temp=array();
            foreach($moduleright as $v0){
                $temp[$v0['module_id']][]=$v0;
            }
            $moduleright=$temp;

            $temp=array();
            foreach($rolemodule as $v0){
                $temp[$v0['module_id']]=$v0;
            }
            $rolemodule=$temp;

            $temp=array();
            foreach($currolemodule as $v0){
                $temp[$v0['module_id']]=$v0;
            }
            $currolemodule=$temp;

            $modulegrouptree=array();
            //var_dump($rolemodule);exit;
            foreach($modulegrouplist as $k1=>$v1){

                $tempModuleList = [];
                if(isset($module[$v1['modulegroupid']])){
                    foreach($module[$v1['modulegroupid']] as $k2=>$v2){
                        $tempModuleRightList=[];
                        $tempModuleRightList = [];
                        if(isset($moduleright[$v2['moduleid']])){
                            foreach($moduleright[$v2['moduleid']] as $k3=>$v3){
                                if($v3['module_id']==$v2['moduleid']){
                                    //是否有权限
                                    if(isset($rolemodule[$v3['module_id']])&& intval($v3['rightvalue'])&intval($rolemodule[$v3['module_id']]['rightvalue'])){
                                        $moduleright[$v2['moduleid']][$k3]['has_right']=1;
                                    }
                                    else{
                                        $moduleright[$v2['moduleid']][$k3]['has_right']=0;
                                    }//var_dump($v3['rightvalue'],$currolemodule[$v3['module_id']]['rightvalue']);
                                    //是否有修改的权限
                                    if(isset($currolemodule[$v3['module_id']])&&intval($v3['rightvalue'])&intval($currolemodule[$v3['module_id']]['rightvalue'])){
                                        $moduleright[$v2['moduleid']][$k3]['has_edit_right']=1;
                                    }
                                    else{
                                        $moduleright[$v2['moduleid']][$k3]['has_edit_right']=0;
                                    }
                                    if($this->isAdmin==2){
                                        $moduleright[$v2['moduleid']][$k3]['has_edit_right']=1;
                                    }
                                    $tempModuleRightList[]=$moduleright[$v2['moduleid']][$k3];

                                }
                            }
                            $module[$v1['modulegroupid']][$k2]['modulerightlist'] = $tempModuleRightList;
                            if($v2['module_group_id']==$v1['modulegroupid']){
                                $tempModuleList[]=$module[$v1['modulegroupid']][$k2];
                            }
                        }
                    }
                    $modulegrouplist[$k1]['modulelist'] = $tempModuleList;
                    $modulegrouptree[$v1['modulegroupid']]=$modulegrouplist[$k1];
                }
            }

            $this->assign('modulegrouptree',$modulegrouptree);
            //var_dump($modulegrouptree);exit;
			//var_dump($this->modulegrouptree);
			echo $this->fetch();exit;
		}
	}

	/**
	 * 删除角色
	 */
	public function removerole(){
		$roleid = input('post.roleid',0);
        if($roleid>0){
            $roleinfo=model('adminrole')->where(['roleid'=>$roleid,'delflag'=>0])->find();
            //只有级别比自己低的角色才可以删除
            if($roleinfo&&$roleinfo['isadmin']<$this->isAdmin){
                $ret=model('adminrole')->where(['roleid'=>$roleid,'delflag'=>0])
                    ->update(['delflag'=>1,'updatetime'=>$this->curDateTime2]);
                if($ret>0){
                    $code=0;
                    $msg='删除成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-1;
                    $msg='删除失败或已被删除';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            else{
                $code=-2;
                $msg='您要删除的模块分组不存在';
                $msgtype=MSG_TYPE_WARNING;
            }
        }
        else{
            $code=-3;
            $msg='参数错误';
            $msgtype=MSG_TYPE_DANGER;
        }

        $ret=array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);
        if(0==$code){
            $ret['html']=$this->index('index_body');
        }
        echo json_encode($ret);exit;
	}

}
