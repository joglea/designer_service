<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\model;

use think\View;
use think\Loader;
use think\Config;
/**
* 用户模型
*/
class Adminrolemodule extends Base{

	protected $name = "Adminrolemodule";

	/**
	 * 通过roleId查找角色模块权限信息
	 * @param  int $roleid  角色id
	 * @return array 角色模块权限信息
	 */
	public function getrolemodulerightbyroleid($roleid){
		$databaseconfig = Config::get('database');

		$rolemodulename = $databaseconfig['prefix'].'adminrolemodule';
		$modulerightname = $databaseconfig['prefix'].'adminmoduleright';
		$modulename = $databaseconfig['prefix'].'adminmodule';
		$modulegroupname = $databaseconfig['prefix'].'adminmodulegroup';

		$ret = $this->join($modulerightname,$rolemodulename.'.module_id='.$modulerightname.'.module_id and '.
			$rolemodulename.'.rightvalue&'.$modulerightname.'.rightvalue>0 ','LEFT')
			->join($modulename,$rolemodulename.'.module_id='.$modulename.'.moduleid ','LEFT')
			->join($modulegroupname,$modulename.'.module_group_id='.$modulegroupname.'.modulegroupid ','LEFT')
			->where(array( $rolemodulename.'.role_id'=>$roleid,$rolemodulename.'.delflag'=>0,
							$modulerightname.'.delflag'=>0,
						   $modulename.'.delflag'=>0,$modulegroupname.'.delflag'=>0))
			->order($modulegroupname.'.modulegroupsort,'.$modulename.'.modulesort,'.$rolemodulename.'.module_id,'.
				$modulerightname.'.rightsort,'.$modulerightname.'.rightvalue')
			->field($rolemodulename.'.module_id,'.$modulerightname.'.modulerightid,'.$modulerightname.
				'.rightname,'.$modulerightname.'.rightename,'.$modulerightname.'.rightvalue,
			    			'.$modulerightname.'.rightsort,'.$modulename.'.modulename,
			    			'.$modulename.'.identityname,'.$modulename.'.url,'.$modulename.'.description,
			    			'.$modulename.'.module_group_id,'.$modulename.'.modulesort,
			    			'.$modulename.'.modulestate,'.$modulename.'.moduleicon,'.$modulegroupname.'.modulegroupname,
			    			'.$modulegroupname.'.modulegroupsort,'.$modulegroupname.'.modulegroupicon')

			->select();
		//var_dump( $this->getlastsql());exit;
		//return $this->getlastsql();exit;
		return $ret;
	}



}