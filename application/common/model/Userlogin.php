<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\model;

use app\common\validate\UserLoginv;
use think\View;
use think\Loader;
/**
* 用户模型
*/
class Userlogin extends Base{

	protected $name = "Userlogin";
	protected $createTime = 'last_login_time';

	protected $type = array(
		'loginid'  => 'integer',
	);


	public function setLastLoginToken($token,$userid,$lastLoginTime,$lastLoginVersion,$salt){
		return md5($token.$userid.$lastLoginTime.$lastLoginVersion.$salt);
	}


}