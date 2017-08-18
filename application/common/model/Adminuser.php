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
* 用户模型
*/
class Adminuser extends Base{

	protected $name = "Adminuser";
	protected $createTime = 'reg_time';
	protected $updateTime = 'last_login_time';

	protected $type = array(
		'uid'  => 'integer',
	);
	protected $insert = array('salt', 'password', 'status', 'reg_time');
	protected $update = array();

	public $editfield = array(
		array('name'=>'uid','type'=>'hidden'),
		array('name'=>'username','title'=>'用户名','type'=>'readonly','help'=>''),
		array('name'=>'nickname','title'=>'昵称','type'=>'text','help'=>''),
		array('name'=>'password','title'=>'密码','type'=>'password','help'=>'为空时则不修改'),
		array('name'=>'sex','title'=>'性别','type'=>'select','option'=>array('0'=>'保密','1'=>'男','2'=>'女'),'help'=>''),
		array('name'=>'email','title'=>'邮箱','type'=>'text','help'=>'用户邮箱，用于找回密码等安全操作'),
		array('name'=>'qq','title'=>'QQ','type'=>'text','help'=>''),
		array('name'=>'score','title'=>'用户积分','type'=>'text','help'=>''),
		array('name'=>'signature','title'=>'用户签名','type'=>'textarea','help'=>''),
		array('name'=>'status','title'=>'状态','type'=>'select','option'=>array('0'=>'禁用','1'=>'启用'),'help'=>''),
	);

	public $addfield = array(
		array('name'=>'username','title'=>'用户名','type'=>'text','help'=>'用户名会作为默认的昵称'),
		array('name'=>'password','title'=>'密码','type'=>'password','help'=>'用户密码不能少于6位'),
		array('name'=>'repassword','title'=>'确认密码','type'=>'password','help'=>'确认密码'),
		array('name'=>'email','title'=>'邮箱','type'=>'text','help'=>'用户邮箱，用于找回密码等安全操作'),
	);
    
	public $useredit = array(
		array('name'=>'uid','type'=>'hidden'),
		array('name'=>'nickname','title'=>'昵称','type'=>'text','help'=>''),
		array('name'=>'sex','title'=>'性别','type'=>'select','option'=>array('0'=>'保密','1'=>'男','2'=>'女'),'help'=>''),
		array('name'=>'email','title'=>'邮箱','type'=>'text','help'=>'用户邮箱，用于找回密码等安全操作'),
		array('name'=>'mobile','title'=>'联系电话','type'=>'text','help'=>''),
		array('name'=>'qq','title'=>'QQ','type'=>'text','help'=>''),
		array('name'=>'signature','title'=>'用户签名','type'=>'textarea','help'=>''),
	);

	public $userextend = array(
		array('name'=>'company','title'=>'单位名称','type'=>'text','help'=>''),
		array('name'=>'company_addr','title'=>'单位地址','type'=>'text','help'=>''),
		array('name'=>'company_contact','title'=>'单位联系人','type'=>'text','help'=>''),
		array('name'=>'company_zip','title'=>'单位邮编','type'=>'text','help'=>''),
		array('name'=>'company_depart','title'=>'所属部门','type'=>'text','help'=>''),
		array('name'=>'company_post','title'=>'所属职务','type'=>'text','help'=>''),
		array('name'=>'company_type','title'=>'单位类型','type'=>'select', 'option'=>'', 'help'=>''),
	);

	protected function setStatusAttr($value){
		return 1;
	}

	protected function setPasswordAttr($value, $data){
		return md5($value.$data['salt']);
	}

	function checklogin() {
		$user = session('user_auth');

		if (empty($user)) {


			return [];
		} else {
			$userModel = model("Adminuser")->where('uid',$user['uid'])->find();
			if($userModel->status == 0){
				session('user_auth',null);
				return [];
			}else{
				return session('user_auth_sign') == data_auth_sign($user) ? $user : [];
			}
		}
	}

	function logincheckcookie(){
		$username = cookie('remember_username');
		$cookieauthsign = cookie('remember_auth_sign');

		$type = 1;
		if (\think\Validate::is($username,'email')) {
			$type = 2;
			$where = ['email'=>$username];
		}elseif (preg_match("/^1[34578]{1}\d{9}$/",$username)) {
			$type = 3;
			$where = ['mobile'=>$username];
		}
		if($type != 1){
			$user = $this->where($where)->find();
			if($this->checkauthsign($user['password'],$cookieauthsign)){
				$this->autoLogin($user,1);
				header('Location:'.url('index/index/index'));
				exit;
			}
		}
	}
	/**
	* 用户登录模型
	*/
	public function login($username = '', $password = '', $type = 1, $isremember = 0){
		$map = array();
		if (\think\Validate::is($username,'email')) {
			$type = 2;
		}elseif (preg_match("/^1[34578]{1}\d{9}$/",$username)) {
			$type = 3;
		}
		switch ($type) {
			case 1:
				$map['username'] = $username;
				break;
			case 2:
				$map['email'] = $username;
				break;
			case 3:
				$map['mobile'] = $username;
				break;
			case 4:
				$map['uid'] = $username;
				break;
			case 5:
				$map['uid'] = $username;
				break;
			default:
				return 0; //参数错误
		}
		$userinfo = $this->db()->where($map)->find();
		if($userinfo){
			$user = $userinfo->toArray();
		}
		else{
			$user = null;
		}
		//$user = $this->db()->where($map)->find()->toArray();
		if(is_array($user) && $user['status']){
			/* 验证用户密码 */
			if(md5($password.$user['salt']) === $user['password']){
				$this->autoLogin($user,$isremember); //更新用户登录信息
				return $user['uid']; //登录成功，返回用户ID
			} else {
				return -2; //密码错误
			}
		} else {
			return -1; //用户不存在或被禁用
		}
	}

	/**
	 *	第三方用户直接完成授权就登录
	 */
	public function thirdlogin($uid){

		$map['uid'] = $uid;

		$userinfo = $this->db()->where($map)->find();
		if($userinfo){
			$user = $userinfo->toArray();
		}
		else{
			$user = null;
		}
		//$user = $this->db()->where($map)->find()->toArray();
		if(is_array($user) && $user['status']){
			$this->autoLogin($user); //更新用户登录信息
			return $user['uid']; //登录成功，返回用户ID
		} else {
			return -1; //用户不存在或被禁用
		}
	}

	/**
	 * 用户注册
	 * @param  integer $user 用户信息数组
	 */
	function register($username, $password, $isautologin = true){
		$map = array();
		$type = 1;
		if (\think\Validate::is($username,'email')) {
			$type = 2;
		}elseif (preg_match("/^1[34578]{1}\d{9}$/",$username)) {
			$type = 3;
		}
		switch ($type) {
			case 1:
				$map['username'] = $username;
				$data['username'] = $username;
				break;
			case 2:
				$map['email'] = $username;
				$data['email'] = $username;
				break;
			case 3:
				$map['mobile'] = $username;
				$data['mobile'] = $username;
				break;
			default:
				return 0; //参数错误
		}

		$user = $this->db()->where($map)->find();
		//var_dump($user->toArray());exit;
		if($user && is_array($user->toArray()) ){
			return -1; //用户已经存在
		}

		$data['nickname'] = $this->generatenick(rand(6,20));
		$data['avatar'] = $this->generateavatar();
		$data['salt'] = rand_string(6);
		$data['password'] = $password;

		//$validate = new Member();
		//$result = $validate->scene('register')->check($data);
		//var_dump($data,$result,$this->getError());exit;
		//$result = $this->validate(true)->save($data);

		$result = $this->validate('member.register')->save($data);
		//var_dump($result,$this->getError(),$data,$this->getLastSql());exit;
		if ($result) {
			$data['uid'] = $this->data['uid'];
			$this->extend()->save($data);
			$defaultrole = config('register_user_role');
			model('AuthGroupAccess')->save([
				'uid'		=> $data['uid'],
				'group_id'	=> $defaultrole
			]);

			if ($isautologin) {
				$this->autoLogin($this->data);
			}
			//var_dump($data);exit;
			return $data['uid'];
		}else{
			if ($this->getError()) {
				return -2;
			}
			else{

				$this->error = "注册失败！";
			}
			return false;
		}
	}

	/**
	 * 三方用户注册
	 * @param  integer $user 用户信息数组
	 */
	function thirdregister($username, $password, $nickname,$avatar,  $isautologin = true){



		$map = array();
		$type = 1;
		if (\think\Validate::is($username,'email')) {
			$type = 2;
		}elseif (preg_match("/^1[34578]{1}\d{9}$/",$username)) {
			$type = 3;
		}
		switch ($type) {
			case 1:
				$map['username'] = $username;
				$data['username'] = $username;
				break;
			case 2:
				$map['email'] = $username;
				$data['email'] = $username;
				break;
			case 3:
				$map['mobile'] = $username;
				$data['mobile'] = $username;
				break;
			default:
				return 0; //参数错误
		}

		$user = $this->db()->where($map)->find();
		//var_dump($user->toArray());exit;
		if($user && is_array($user->toArray()) ){
			return -1; //用户已经存在
		}

		$data['nickname'] = $nickname;
		$data['avatar'] = $avatar;
		$data['salt'] = rand_string(6);
		$data['password'] = $password;

		//$validate = new Member();
		//$result = $validate->scene('register')->check($data);
		//var_dump($data,$result,$this->getError());exit;
		//$result = $this->validate(true)->save($data);

		$result = $this->validate('member.register')->save($data);
		//var_dump($result,$this->getError(),$data,$this->getLastSql());exit;
		if ($result) {
			$data['uid'] = $this->data['uid'];
			$this->extend()->save($data);
			$defaultrole = config('register_user_role');
			model('AuthGroupAccess')->save([
				'uid'		=> $data['uid'],
				'group_id'	=> $defaultrole
			]);
			//var_dump($this->data);exit;
			if ($isautologin) {
				$this->autoLogin($this->data);
			}
			//var_dump($this->data);
			return $data['uid'];
		}else{
			if ($this->getError()) {
				return -2;
			}
			else{

				$this->error = "注册失败！";
			}
			return false;
		}
	}


	/**
	 * 自动登录用户
	 * @param  integer $user 用户信息数组
	 * @param  integer $isremember 是否记住登录
	 */
	private function autoLogin($user,$isremember = 0){
		/* 更新登录信息 */
		$data = array(
			'uid'             => $user['uid'],
			'login'           => array('exp', '`login`+1'),
			'last_login_time' => time(),
			'last_login_ip'   => get_client_ip(1),
		);
		$this->where(array('uid'=>$user['uid']))->update($data);
		$user = $this->where(array('uid'=>$user['uid']))->find();

		$username =  $this->generateshowname($user);
		$password = $user['password'];
		/* 记录登录SESSION和COOKIES */
		$auth = array(
			'uid'             => $user['uid'],
			'username'        => $username,
			'last_login_time' => $user['last_login_time'],
		);
		$cookieauthsign = $this->generateauthsign($password);
		//记住密码
		if($isremember == 1){
			cookie('remember_username',$username,7*86400);
			cookie('remember_auth_sign',$cookieauthsign,7*86400);
		}
		session('user_auth', $auth);
		session('user_auth_sign', data_auth_sign($auth));
	}

	public function logout(){

		cookie('remember_username',null);
		cookie('remember_auth_sign',null);


		session('user_auth', null);
		session('user_auth_sign', null);
	}

	public function getInfo($uid){
		$data = $this->where(array('uid'=>$uid))
			->field('uid,username,nickname,email,email_status,mobile,mobile_status,avatar,birthday,qq,status')
			->find();
		if ($data) {
			return $data->toArray();
		}else{
			return false;
		}
	}

	/**
	 * 修改用户资料
	 */
	public function editUser($data, $ischangepwd = false){
		if ($data['uid']) {
			if (!$ischangepwd || ($ischangepwd && $data['password'] == '')) {
				unset($data['salt']);
				unset($data['password']);
			}else{
				$data['salt'] = rand_string(6);
			}
			$result = $this->validate('member.edit')->save($data, array('uid'=>$data['uid']));
			if ($result) {
				return $this->extend->save($data, array('uid'=>$data['uid']));
			}else{
				return false;
			}
		}else{
			$this->error = "非法操作！";
			return false;
		}
	}

	/**
	 * 修改用户资料
	 */
	public function editprofile($data){
		if ($data['uid']) {

			$result = $this->save($data, array('uid'=>$data['uid']));
			return $result;
		}else{
			$this->error = "非法操作！";
			return false;
		}
	}

	public function editpw($data, $is_reset = false){
		$uid = $is_reset ? $data['uid'] : session('user_auth.uid');
		if (!$is_reset) {
			//后台修改用户时可修改用户密码时设置为true
			$this->checkPassword($uid,$data['oldpassword']);

			$validate = $this->validate('member.password');
			if (false === $validate) {
				return false;
			}
		}

		$data['salt'] = rand_string(6);

		return $this->save($data, array('uid'=>$uid));
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

	public function extend(){
		return $this->hasOne('MemberExtend', 'uid');
	}

	public function generateauthsign($password){
		return md5(config('cookie_pwd_prefix').$password.config('cookie_pwd_suffix'));
	}

	public function checkauthsign($password,$authsign){
		if(md5(config('cookie_pwd_prefix').$password.config('cookie_pwd_suffix'))==$authsign){
			return true;
		}
		else{
			return false;
		}
	}

	//根据昵称，邮件，手机号生成用于显示的用户名
	public function generateshowname($userinfo){
		return $userinfo['nickname'];

		if($userinfo['nickname']!=''){
			return $userinfo['nickname'];
		}
		else if($userinfo['email']!=''){
			return $userinfo['email'];
		}
		else if($userinfo['mobile']!=''){
			return $userinfo['mobile'];
		}
		else{
			return $userinfo['username'];
		}
	}

	/**
	 * 生成验证码
	 */
	public function generatenick($len = 6){
		$s = [0,2,3,4,5,6,7,8,'a','b','c','d','e','f','g','h','j','k',
			'l','m','n','p','q','r','s','t','u','v','w','x','y','z'];
		$code = '';
		for($i=0;$i<$len;$i++){
			$code.=$s[rand(0,31)];
		}
		return $code;

	}

    /**
     * 随机默认头像
     */
    private function generateavatar(){
        return "avatar/default".str_pad(rand(1,18),2,'0',STR_PAD_LEFT);
    }

}