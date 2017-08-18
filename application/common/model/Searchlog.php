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
* 话题数据模型
*/
class Searchlog extends Base{

	protected $name = "Searchlog";
	protected $createTime = 'createtime';


	public function addSearchLog($type,$searchValue){
		$searchLog = model('searchlog')->where(['type'=>$type,'search_value'=>$searchValue])->find();
		if($searchLog){
			model('searchlog')->where(['type'=>$type,'search_value'=>$searchValue])
				->update(['counter'=>['exp','counter+1'],'updatetime'=>time()]);
		}
		else{
			$addLog = [
				'type'=>$type,
				'search_value'=>$searchValue,
				'counter'=>1,
				'createtime'=>time(),
				'updatetime'=>time()
			];
			model('searchlog')->insertGetId($addLog);
		}
	}



}