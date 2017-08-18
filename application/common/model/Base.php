<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\model;

use think\Model;
use think\Request;

/**
* 模型基类
*/

class Base extends Model{

	protected $type = array(
		'id'  => 'integer',
		'cover_id'  => 'integer',
	);

	/**
	 * 数据修改
	 * @return [bool] [是否成功]
	 */
	public function change(){
		$data = Request::instance()->post();
		if (isset($data['id']) && $data['id']) {
			return $this->save($data, array('id'=>$data['id']));
		}else{
			return $this->save($data);
		}
	}
}