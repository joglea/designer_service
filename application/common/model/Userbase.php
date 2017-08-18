<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\model;

use app\common\validate\Userbasev;
use think\View;
use think\Loader;
/**
* 用户模型
*/
class Userbase extends Base{

	protected $name = "Userbase";
	protected $createTime = 'createtime';

	protected $type = array(
		'userid'  => 'integer',
	);


	public function setPass($pass,$salt){
		return md5($pass.$salt);
	}

	public function setToken($tel,$newpass){
		return md5($tel.$newpass);
	}


	protected function checkPassword($uid,$password){
		if (!$uid || !$password) {
			$this->error = '原始用户UID和密码不能为空';
			return false;
		}

		$user = $this->where(array('uid'=>$uid))->find();
		if (md5($password.$user['salt']) === $user['password']) {
			return true;
		}else{
			$this->error = '原始密码错误！';
			return false;
		}
	}



}