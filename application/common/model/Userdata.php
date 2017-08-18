<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\model;

use app\common\validate\Userdatav;
use think\View;
use think\Loader;
/**
* 用户模型
*/
class Userdata extends Base{

	protected $name = "Userdata";
	protected $createTime = 'createtime';

	protected $type = array(
		'userid'  => 'integer',
	);





}