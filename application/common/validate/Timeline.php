<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\validate;

/**
* 设置模型
*/
class Timeline extends Base{

	protected $rule = array(
		'title'   => 'require',
		'jump'      => 'require',
	);
	protected $message = array(
		'title.require'    => '标题必须',
		'jump.require'    => '跳转必须',
	);
	protected $scene = array(
		'addtimeline'     => 'title,jump'
	);

}