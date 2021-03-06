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
/**
* 用户关注模型
*/
class Follow extends Base{

	protected $name = "Follow";
	protected $createTime = 'createtime';




	public function joinUserServiceTypeByWhere($on='',$where=[],$field='*',$order='',$group='',$limit='0,20',$jointype='INNER'){


		$userList = $this->join("jz_userservicetype",$on,$jointype)
			->where($where)->field($field)->order($order)->limit($limit)->group($group)->select();
		return $userList;
	}

}