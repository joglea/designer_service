<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\model;

use app\common\validate\Userhxv;
use think\View;
use think\Loader;
/**
* 用户模型
*/
class Userhx extends Base{

	protected $name = "Userhx";
	protected $createTime = 'createtime';

	protected $type = array(
		'userid'  => 'integer',
	);




    /**
     * 生成hxid
     */
	public function setHxId($userid){
        return md5($userid.'hxid');
    }

	/**
	 * 生成hxpass
	 */
	public function setHxPass($token){
		return md5($token.'hxpass');
	}

}