<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 模块管理
 * Enter description here ...
 * @author jogle
 * @created on 20170802
 */
class Module extends Admin{

	/**
	 * 模块分组列表
	 * @param number $in 是否内部调用 0不是  1是
     * @return string
     */
	public function index($view='index'){
		$modulegrouplist=model('adminmodulegroup')->where(['delflag'=>0])->select();
		$modulelist=model('adminmodule')->where(['delflag'=>0])->select();
		//var_dump($moduleList);exit;
		$this->assign('modulegrouplist',$modulegrouplist);
        $this->assign('modulelist',$modulelist);
        //var_dump($this->modulelist);exit;
        $this->setPageHeaderRightButton(
            array(
                array(
                    'class'=>'btn btn-fit-height grey-salt',
                    'onclick'=>"onclick='addmodulegroup()'",
                    'icon'=>"<i class='fa fa-plus'></i>",
                    'text'=>'添加权限组')
            )
        );

        $fetchContent = $this->fetch($view);

        return $fetchContent;

	}


	/**
	 * 添加模块分组
	 */
	public function addmodulegroup(){
		if(IS_POST){
			$modulegroupinfo = array();
			$modulegroupinfo['modulegroupname'] = input('post.modulegroupname','');
			$modulegroupinfo['modulegroupsort'] = input('post.modulegroupsort',0,'intval');
            if(''===$modulegroupinfo['modulegroupname']){
                $code=-1;
                $msg='模块分组名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''===$modulegroupinfo['modulegroupsort']){
                $code=-2;
                $msg='模块分组排序不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                $modulegroupinfo['delflag'] = 0;
                //$maxSort= D('ModuleGroup')->getMaxModuleGroupSort($modulegroupinfo['modulegrouppid']);
                $modulegroupid = model('adminmodulegroup')->insertGetId($modulegroupinfo);
                if($modulegroupid>0){
                    $code=0;
                    $msg='模块分组添加成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-3;
                    $msg='模块分组添加失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            $ret=array('code'=>$code,'msg'=>$msg,'msgtype'=>$msgtype);
			if(isset($ret['code'])&&$ret['code']==0){

				$ret['html']=$this->index('index_body');
			}
			echo json_encode($ret);exit;
		}
		else{
			$maxmodulegroupsort=model('adminmodulegroup')->where(['delflag'=>0])
                ->order('modulegroupsort desc')
                ->limit(1)->value('modulegroupsort');
			$this->assign('maxmodulegroupsort',$maxmodulegroupsort+1);
			echo $this->fetch();exit;
		}
	}
	
	/**
	 * 修改模块分组
	 */
	public function editmodulegroup(){
		if(IS_POST){
			$modulegroupinfo = array();
			$modulegroupinfo['modulegroupid'] = input('post.modulegroupid','');
			$modulegroupinfo['modulegroupname'] = input('post.modulegroupname','');
			$modulegroupinfo['modulegroupsort'] = input('post.modulegroupsort',0);
            if(!($modulegroupinfo['modulegroupid']>0)){
                $code=-1;
                $msg='模块分组id不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''===$modulegroupinfo['modulegroupname']){
                $code=-2;
                $msg='模块分组名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($modulegroupinfo['modulegroupsort']>0)){
                $code=-3;
                $msg='模块分组排序值不洗大于0';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                $ret = model('adminmodulegroup')
                    ->where(['modulegroupid'=>$modulegroupinfo['modulegroupid']])
                    ->update($modulegroupinfo);
                if($ret===0){
                    $code=0;
                    $msg='模块分组未发生修改';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else if($ret>0){
                    $code=0;
                    $msg='模块分组修改成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-4;
                    $msg='模块分组修改失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            $html='';
			if(0==$code){
				$html=$this->index('index_body');
			}
			echo json_encode(array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype,'html'=>$html));exit;
		}
		else{
			$modulegroupid = input('get.modulegroupid',0);

            $this->assign('modulegroupid',$modulegroupid);
			if($modulegroupid>0){
				$modulegroupinfo=model('adminmodulegroup')
                    ->where(['modulegroupid'=>$modulegroupid,'delflag'=>0])
                    ->find();
                $this->assign('modulegroupinfo',$modulegroupinfo);
				echo $this->fetch();exit;
			}
			else{
				echo '参数错误';exit;
			}
		}
	}
	
	/**
	 * 删除模块分组
	 */
	public function removemodulegroup(){
		$modulegroupid = input('post.modulegroupid',0);
        if($modulegroupid>0){
            $modulegroupinfo=model('adminmodulegroup')
                ->where(['modulegroupid'=>$modulegroupid,'delflag'=>0])->find();
            if($modulegroupinfo){
                $ret=model('adminmodulegroup')
                    ->where(['modulegroupid'=>$modulegroupid,'delflag'=>0])
                    ->update(['updatetime'=>time(),'delflag'=>1]);//删除模块分组
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
                $msg='您要删除的模块分组不存在或已被删除';
                $msgtype=MSG_TYPE_WARNING;
            }
        }
        else{
            $code=-3;
            $msg='参数错误';
            $msgtype=MSG_TYPE_DANGER;
        }
		echo json_encode(array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype));exit;
	}
	
	
	/**
	 * 添加模块
	 */
	public function addmodule(){
		if(IS_POST){
			$moduleinfo = array();
			$moduleinfo['modulename'] = input('post.modulename','');
			$moduleinfo['identityname'] = input('post.identityname','');
			$moduleinfo['url'] = input('post.url','');
			$moduleinfo['module_group_id'] =
                ''!=input('post.modulegroupid','')&&input('post.modulegroupid','')>=0
			    ?input('post.modulegroupid',''):'';
			$moduleinfo['modulesort'] = input('post.modulesort',1);
			$moduleinfo['modulestate'] = input('post.modulestate',1);
			$moduleinfo['description'] = input('post.description','');
            if(''==$moduleinfo['modulename']){
                $code=-1;
                $msg='模块名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$moduleinfo['identityname']){
                $code=-2;
                $msg='模块唯一标识不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''===$moduleinfo['url']){
                $code=-3;
                $msg='模块URL不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($moduleinfo['module_group_id']>0)){
                $code=-4;
                $msg='模块分组归属参数不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($moduleinfo['modulesort']>0)){
                $code=-5;
                $msg='模块排序值不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif($moduleinfo['modulestate']!=0&&$moduleinfo['modulestate']!=1){
                $code=-6;
                $msg='模块状态不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                $moduleinfo['delflag'] = 0;
                $moduleid = model('adminmodule')->insertGetId($moduleinfo);
                if($moduleid>0){
                    $code=0;
                    $msg='模块添加成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-7;
                    $msg='模块添加失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            $html='';
            if(0==$code){
                $html=$this->index('index_body');
            }
            echo json_encode(array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype,'html'=>$html));exit;
		}
		else{
			$modulegroupid = input('get.modulegroupid',0);
            $this->assign('modulegroupid',$modulegroupid);
			if(!($modulegroupid>0)){
				echo '参数错误';exit;
			}
			else{
				$modulegroupinfo=model('adminmodulegroup')
                    ->where(['modulegroupid'=>$modulegroupid,'delflag'=>0])->find();
                $this->assign('modulegroupinfo',$modulegroupinfo);
                $maxmodulesort=model('adminmodule')->where(['module_group_id'=>$modulegroupid,'delflag'=>0])
                    ->order('modulesort desc')->limit(1)->value('modulesort');

                $this->assign('maxmodulesort',$maxmodulesort+1);
				echo $this->fetch();exit;
			}
		}
	}
	
	/**
	 * 修改模块
	 */
	public function editmodule(){
		if(IS_POST){
			$moduleinfo = array();
			$moduleinfo['moduleid'] = input('post.moduleid','');
			$moduleinfo['modulename'] = input('post.modulename','');
			$moduleinfo['identityname'] = input('post.identityname','');
			$moduleinfo['url'] = input('post.url','');
			//$moduleinfo['modulegrouppid'] = ''!=input('post.modulegroupid','')&&input('post.modulegroupid','')>=0
			//?input('post.modulegroupid',''):'';
			$moduleinfo['modulesort'] = input('post.modulesort',1);
			$moduleinfo['modulestate'] = input('post.modulestate',1);
			$moduleinfo['description'] = input('post.description','');
            $moduleinfo['updatetime'] = date('Y-m-d H:i:s');
            if(''==$moduleinfo['modulename']){
                $code=-1;
                $msg='模块名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$moduleinfo['identityname']){
                $code=-2;
                $msg='模块唯一标识不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''===$moduleinfo['url']){
                $code=-3;
                $msg='模块URL不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            //elseif(!($moduleinfo['module_group_id']>0))
            //{
            //	$code=-4;
            //	$msg='模块分组归属参数不合法';
            //	$msgtype=MSG_TYPE_WARNING;
            //}
            elseif(!($moduleinfo['modulesort']>0)){
                $code=-5;
                $msg='模块排序值不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif($moduleinfo['modulestate']!=0&&$moduleinfo['modulestate']!=1){
                $code=-6;
                $msg='模块状态不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                $ret = model('adminmodule')
                    ->where(['moduleid'=>$moduleinfo['moduleid']])->update($moduleinfo);
                // 		var_dump($moduleid);exit;
                if($ret>0||$ret===0){
                    $code=0;
                    $msg='模块修改成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-7;
                    $msg='模块修改失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            $html='';
            if(0==$code){
                $html=$this->index('index_body');
            }
            echo json_encode(array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype,'html'=>$html));exit;
		}
		else{
			$moduleid = input('get.moduleid',0);
			if($moduleid>0){
				$moduleinfo=model('adminmodule')->alias('a')
                    ->join(config('prefix').'adminmodulegroup b'.'',' a.module_group_id=b.modulegroupid','LEFT')
                    ->where(['a.moduleid'=>$moduleid,'a.delflag'=>0])->find();
                $this->assign('moduleid',$moduleid);
                $this->assign('moduleinfo',$moduleinfo);
				//var_dump($this->moduleid,$this->moduleinfo);exit;
				echo $this->fetch();exit;
			}
			else{
				echo '参数错误';exit;
			}
			
		}
		
		
	}
	
	/**
	 * 删除模块
	 */
	function removemodule(){
		$moduleid = input('post.moduleid',0);
        if($moduleid>0){
            $moduleinfo=model('adminmodule')->where(['moduleid'=>$moduleid,'delflag'=>0])->find();
            if($moduleinfo){
                $ret=model('adminmodule')->where(['moduleid'=>$moduleid])
                    ->update(['delflag'=>1,'updatetime'=>date('Y-m-d H:i:s')]);//删除模块
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
                $msg='您要删除的模块分组不存在或已被删除';
                $msgtype=MSG_TYPE_WARNING;
            }
        }
        else{
            $code=-3;
            $msg='参数错误';
            $msgtype=MSG_TYPE_DANGER;
        }
		echo json_encode(array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype));exit;
	}
	
    
	/**
	 * 模块权限列表
     * @param number $in 是否内部调用 0不是  1是
	 */
	public function modulerightlist($in=0){
		if($in==1){
			$moduleid = input('post.moduleid',0);
		}
		else{
            $moduleid = input('get.moduleid',0);
		}
		if(!($moduleid>0)){
			echo '参数错误';exit;
		}
		$moduleinfo = model('adminmodule')
            ->where(['moduleid'=>$moduleid,'delflag'=>0])->find();
        $this->assign('moduleinfo',$moduleinfo);
		if(!$moduleinfo){
			echo '模块不存在';exit;
		}
		$modulerightlist=model('adminmoduleright')
            ->where(['module_id'=>$moduleid,'delflag'=>0])->select();
        $this->assign('modulerightlist',$modulerightlist);
		//var_dump($this->modulerightlist);exit;
		$this->assign('moduleid',$moduleid);
        $this->setPageHeaderRightButton(
			array(
				array(
					'onclick'=>"onclick='addmoduleright(".$moduleid.")'",
					'icon'=>"<i class='fa fa-plus'></i>",
					'text'=>'添加权限')
				)
			);
		if($in==1){
			return $this->fetch('modulerightlist');
		}
		else{
	    	echo $this->fetch('modulerightlist');exit;
		}
	}
	
	/**
	 * 检查权限值是否存在
	 */
	public function checkmodulerightvalue(){
	    $rightvalue=input('post.rightvalue','','intval');
	    $moduleid=input('post.moduleid','','intval');
	    if(IS_POST && $rightvalue!='' && $moduleid!=''){
	        $modulerightid=input('post.modulerightid',0,'intval');
	        $ret=model('adminmoduleright')
                ->where(['rightvalue'=>$rightvalue,'module_id'=>$moduleid,
                'modulerightid'=>['neq',$modulerightid],'delflag'=>0])->find();
	        //var_dump($rightvalue,$moduleid,$modulerightid,$ret);exit;
	        if($ret){
	            $valid=false;
	            $msg='权限值已存在';
	        }
	        else{
	            $valid=true;
	            $msg='';
	        }
	    }
	    else{
	        $valid=false;
	        $msg='参数错误，验证失败';
	    }
	    echo json_encode(array('valid'=>$valid,'message'=>$msg));exit;
	}

	/**
	 * 添加模块权限
	 */
	public function addmoduleright(){
		if(IS_POST){
			$modulerightinfo = array();
			$modulerightinfo['module_id'] = input('post.moduleid',0);
			$modulerightinfo['rightname'] = input('post.rightname','');
			$modulerightinfo['rightename'] = input('post.rightename','');
			$modulerightinfo['rightvalue'] = input('post.rightvalue',0);
			$modulerightinfo['rightsort'] = input('post.rightsort',1);

            if(!($modulerightinfo['module_id']>0)){
                $code=-1;
                $msg='所属模块不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$modulerightinfo['rightname']){
                $code=-2;
                $msg='模块权限名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$modulerightinfo['rightename']){
                $code=-3;
                $msg='模块权限英文名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif($modulerightinfo['rightvalue']==''||$modulerightinfo['rightvalue']==0
                ||(intval($modulerightinfo['rightvalue'])&(intval($modulerightinfo['rightvalue'])-1))){
                $code=-4;
                $msg='模块权限值必须为2的n次方(n为非负整数)';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($modulerightinfo['rightsort']>0)){
                $code=-5;
                $msg='模块权限排序值不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                $isExist = model("adminmoduleright")
                    ->where(['rightvalue'=>$modulerightinfo['rightvalue'],
                             'module_id'=>$modulerightinfo['module_id'],
                             'delflag'=>0])
                    ->find();
                //var_dump(D("moduleright")->getlastsql());exit;
                if($isExist){
                    $code=-6;
                    $msg='模块权限值已经存在 请替换';
                    $msgtype=MSG_TYPE_WARNING;
                }
                else{
                    $modulerightinfo["delflag"] = 0;
                    $modulerightid = model('adminmoduleright')
                        ->insertGetId($modulerightinfo);
                    if($modulerightid>0){
                        $code=0;
                        $msg='模块权限添加成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        $code=-7;
                        $msg='模块权限添加失败';
                        $msgtype=MSG_TYPE_DANGER;
                    }
                }
            }
            $html='';
            if(0==$code){
                $html=$this->modulerightlist(1);
            }
            echo json_encode(array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype,'html'=>$html));exit;
		}
		else{
			$moduleid = input('get.moduleid',0);
			if(!($moduleid>0)){
				echo '参数错误';exit;
			}
			else{
				$moduleinfo=model('adminmodule')->where(
                    ['moduleid'=>$moduleid,'delflag'=>0])->find();
				if(!$moduleinfo){
					echo '模块不存在';exit;
				}
				$maxmodulerightsort=model('adminmoduleright')
                    ->where(['module_id'=>$moduleid,'delflag'=>0])->max('rightsort');
				$maxmodulerightsort+=1;
				$maxmodulerightvalue=model('adminmoduleright')
                    ->where(['module_id'=>$moduleid,'delflag'=>0])->max('rightvalue');
				if($maxmodulerightvalue){
				    $maxmodulerightvalue*=2;
				}
				else{
				    $maxmodulerightvalue=1;
				}
                $this->assign('moduleid',$moduleid);
                $this->assign('moduleinfo',$moduleinfo);
                $this->assign('maxmodulerightsort',$maxmodulerightsort);
                $this->assign('maxmodulerightvalue',$maxmodulerightvalue);
                $this->assign('moduleid',$moduleid);
				echo $this->fetch();exit;
			}
		}
	}
	
	/**
	 * 修改模块权限
	 */
	public function editmoduleright(){
		if(IS_POST){
			$modulerightinfo = array();
			$modulerightinfo['modulerightid'] = input('post.modulerightid',0);
			$modulerightinfo['module_id'] = input('post.moduleid',0);
			$modulerightinfo['rightname'] = input('post.rightname','');
			$modulerightinfo['rightename'] = input('post.rightename','');
			$modulerightinfo['rightvalue'] = input('post.rightvalue',0);
			$modulerightinfo['rightsort'] = input('post.rightsort',1);
            $modulerightinfo['updatetime'] = date('Y-m-d H:i:s');

            if(!($modulerightinfo['modulerightid']>0)){
                $code=-1;
                $msg='模块权限参数不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($modulerightinfo['module_id']>0)){
                $code=-2;
                $msg='模块参数不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$modulerightinfo['rightname']){
                $code=-3;
                $msg='模块权限名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$modulerightinfo['rightename']){
                $code=-4;
                $msg='模块权限英文名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif($modulerightinfo['rightvalue']==''||$modulerightinfo['rightvalue']==0
                ||(intval($modulerightinfo['rightvalue'])&(intval($modulerightinfo['rightvalue'])-1))){
                $code=-5;
                $msg='模块权限值必须为2的n次方(n为非负整数)';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!($modulerightinfo['rightsort']>0)){
                $code=-6;
                $msg='模块权限排序值不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                $isExist = model("adminmoduleright")
                    ->where(['rightvalue'=>$modulerightinfo['rightvalue'],
                             'module_id'=>$modulerightinfo['module_id'],
                             'delflag'=>0,'modulerightid'=>['neq',$modulerightinfo['modulerightid']]])
                    ->find();

                //var_dump(D("moduleright")->getlastsql());exit;
                if($isExist){
                    $code=-7;
                    $msg='模块权限值已经被使用 请替换';
                    $msgtype=MSG_TYPE_WARNING;
                }
                else{
                    $ret = model("adminmoduleright")->where(['modulerightid'=>$modulerightinfo['modulerightid']])
                        ->update($modulerightinfo);
                    //var_Dump($ret);exit;
                    if($ret===0){
                        $code=0;
                        $msg='模块权限未发生修改';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else if($ret>0){
                        $code=0;
                        $msg='模块权限修改成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        $code=-8;
                        $msg='模块权限修改失败';
                        $msgtype=MSG_TYPE_DANGER;
                    }
                }
            }
            $html='';
            if(0==$code){
                $html=$this->modulerightlist(1);
            }
            echo json_encode(array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype,'html'=>$html));exit;
		}
		else{
			$modulerightid = input('get.modulerightid',0);
			if(!($modulerightid>0)){
				echo '参数错误';exit;
			}
			else{
				$modulerightinfo=model('adminmoduleright')->alias('a')
                    ->join(config('prefix').'adminmodule b'.'',' a.module_id=b.moduleid','LEFT')
                    ->where(['a.modulerightid'=>$modulerightid,'a.delflag'=>0])->find();
				if(!$modulerightinfo){
					echo '模块权限不存在';exit;
				}
                $this->assign('modulerightid',$modulerightid);
                $this->assign('modulerightinfo',$modulerightinfo);
				echo $this->fetch();exit;
			}
		}
	}
	
	/**
	 * 删除模块权限
	 */
	public function removemoduleright(){
		$modulerightid = input('post.modulerightid',0);
        if($modulerightid>0){
            $modulerightinfo=model('adminmoduleright')
                ->where(['modulerightid'=>$modulerightid,'delflag'=>0])->find();
            if($modulerightinfo){
                $ret=model('adminmoduleright')->where(['modulerightid'=>$modulerightid])
                    ->update(['updatetime'=>date('Y-m-d H:i:s'),'delflag'=>1]);
                if($ret>0){
                    $code=0;
                    $msg='删除权限成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-2;
                    $msg='删除权限失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            else{
                $code=-3;
                $msg='您要删除的模块权限不存在或已被删除';
                $msgtype=MSG_TYPE_WARNING;
            }
        }
        else{
            $code=-4;
            $msg='参数错误';
            $msgtype=MSG_TYPE_DANGER;
        }
		echo json_encode(array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype));exit;

	}
}