<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\model;

use app\common\validate\Userinfov;
use think\View;
use think\Loader;
/**
* 用户模型
*/
class Userinfo extends Base{

	protected $name = "Userinfo";
	protected $createTime = 'createtime';

	protected $type = array(
		'userid'  => 'integer',
	);




    /**
     * 随机默认头像
     */
	public function generateAvatar(){

        return "avatar/default".str_pad(rand(1,18),2,'0',STR_PAD_LEFT);
    }

	public function checkNickName($nickName){

		$nickNameMinLength = 6;
		$nickNameMaxLength = 30;
		//昵称长度范围6~30
		$preg='/^[0-9a-zA-Z\_\x{4e00}-\x{9fa5}]{3,'.$nickNameMaxLength.'}$/u';
		//var_dump($nickName,preg_match($preg,$nickName));
		if(preg_match($preg,$nickName)){
			if(check_string_length($nickName,$nickNameMinLength,$nickNameMaxLength)){
				return true;
			}
			return false;
		}
		else{
			return false;
		}
	}

	public function getZhimaState($zhimaCode){
		if($zhimaCode>800){
			return '极好';
		}
		elseif($zhimaCode>700&&$zhimaCode<=800){
			return '优秀';
		}
		elseif($zhimaCode>600&&$zhimaCode<=700){
			return '良好';
		}
		elseif($zhimaCode>500&&$zhimaCode<=600){
			return '一般';
		}
		elseif($zhimaCode>400&&$zhimaCode<=500){
			return '很差';
		}
		elseif($zhimaCode>0&&$zhimaCode<=400){
			return '极差';
		}
		else{
			return '未绑定';
		}
	}


	public function joinUserServiceTypeByWhere($where=[],$field='*',$order='',$group='',$limit='0,20',$jointype='INNER'){


		$userList = $this->join("jz_userservicetype","jz_userservicetype.userid = jz_userinfo.userid",$jointype)
			->where($where)->field($field)->order($order)->limit($limit)->group($group)->select();
		return $userList;
	}


}