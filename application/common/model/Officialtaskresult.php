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
* 模型
*/
class Officialtaskresult extends Base{

	protected $name = "Officialtaskresult";
	protected $createTime = 'createtime';


	public function joinOfficialTaskByWhere($on='',$where=[],$field='*',$order='',$group='',$limit='0,20',$jointype='INNER'){

		$databaseconfig = Config::get('database');

		$list = $this->table($databaseconfig['prefix'].'officialtaskresult')
			->join($databaseconfig['prefix']."officialtask",$on,$jointype)
			->where($where)->field($field)->order($order)->limit($limit)->group($group)->select();
		return $list;
	}

}