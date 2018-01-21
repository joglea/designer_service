<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\controller;


class Front extends Base {


    public $curUserInfo = [];

    public $curTime = 0;

    //项目每次登录后生成的logintoken 如果未登录的由客户端生成
    public $dsToken = '';

    //客户端生成版本号 安卓:a1000 ios:i1000
    public $dsVersion = 'a1000';
    public $dsVersionId = 1000;
    //1安卓 2ios
    public $dsVersionType = 1;

    public $allControl = [];
    public $allServiceType = [];

	public function _initialize() {
		parent::_initialize();

		header("Content-type: text/html; charset=utf-8");
		header("Access-Control-Allow-Origin: *");

        $this->curTime = time();
        $unitTime = 3;
        $frequency = 50;

        //请求检测合法
        $checkRequestRet = $this->checkRequest();
        if(0 != $checkRequestRet){
            $this->returndata( 11001, 'illegal request'.$checkRequestRet, time(), []);
        }

        if(strtolower(CONTROLLER_NAME)!='docsdebug'){
            $this->limitAccessFrequency($unitTime,$frequency);
        }


        $notloginurls = \think\Config::get('not_login_urls');

        if (!in_array($this->url, $notloginurls) ) {

            if(!$this->checkLogin()){
                $this->returndata( 11002, 'no login', time(), []);
            }
        }
	}

	/**
	 * 请求检测合法
     * 检测规则 每个接口请求都要包含ct:时间戳,sid:混淆串,由客户端自动生成
     * 每个请求的header中要有 ds-token:账号token 已登录的由服务端返回  未登录的由客户端自动生成
     *                      ds-version:客户端app版本号 安卓:a1000 ios:i1000
     *                      ds-verify:验证串 由MD5(dstoken+'_'+dsversion+'_'+sid+'_'+ct)生成
     *
     * @return string/boolean
	 * @author xx
	 */
	final protected function checkRequest() {

        if(strtolower(CONTROLLER_NAME) == 'docsdebug' && in_array(strtolower(ACTION_NAME),['detail','index']) ){
            return 0;
        }
        //return true;
        //每个请求唯一串
        $sid = input('sid');
        //每个请求客户端生成的时间戳
        $ct = input('ct');

        //检查客户端时间戳是否合法
        //if($this->curTime-$ct>config('api_timeout')||$this->curTime<$ct){
        //    return 1;
        //}

        //MD5(dstoken+dsversion+sid+ct)
        $dsVerify = '';
        $allHeaders = $this->getAllHeaders();
        foreach($allHeaders as $key => $value)
        {
            $nkey = strtolower($key);
            if($nkey=='ds-token')
            {
                $this->dsToken=$value;
            }
            elseif($nkey=='ds-version')
            {
                $this->dsVersion=$value;
            }
            elseif($nkey=='ds-verify')
            {
                $dsVerify=$value;
            }
        }
        if(!$this->dsToken){
            $this->dsToken = 'test_token';
            $dsVerify = md5($this->dsToken.'_'.$this->dsVersion.'_'.$sid.'_'.$ct);
        }

        $dsVersionType = substr($this->dsVersion,0,1);
        if($dsVersionType != 'a' && $dsVersionType != 'i'){
            return 2;
        }
        elseif($dsVersionType == 'a'){
            $this->dsVersionType = 1;
        }
        elseif($dsVersionType == 'i'){
            $this->dsVersionType = 2;
        }
        $this->dsVersionId = substr($this->dsVersion,1,strlen($this->dsVersion)-1);

        if(!$this->dsToken||!$this->dsVersion||!$dsVerify||
            !$this->checkJzVerify($this->dsToken,$this->dsVersion,$sid,$ct,$dsVerify)){
            return 3;
        }

        return 0;
	}


    protected function checkJzVerify($dsToken,$dsVersion,$sid,$ct,$dsVerify){
        //var_dump($dsToken.'_'.$dsVersion.'_'.$sid.'_'.$ct,
        //md5($dsToken.'_'.$dsVersion.'_'.$sid.'_'.$ct),$dsVerify);exit;
        if(md5($dsToken.'_'.$dsVersion.'_'.$sid.'_'.$ct)==$dsVerify){
            return true;
        }
        return false;
    }

    protected function checkParam(){

    }


    public function checkLogin(){

        $this->curUserInfo = cache($this->dsToken);
//var_dump($this->dsToken,$this->curUserInfo);exit;
        if(!$this->curUserInfo){
            $userLogin = model('userlogin')->where(['last_login_token'=>$this->dsToken,'delflag'=>0])->find();
            if($userLogin){

                $userBase = model('userbase')->where(['userid'=>$userLogin['userid']])->find();
                if($userBase && $userBase['confinedtime'] >= $this->curTime){
                    $this->returndata(11003, 'account forbidden', $this->curTime, []);
                }

                $this->doLogin($userBase,$this->dsToken);
            }
            else{
                return false;
            }
        }

        return true;
    }

    public function doLogin($userBase,$lastLoginToken=''){

        $userInfo = model('userinfo')->where(['userid'=>$userBase['userid']])->find();
        if(!$userInfo){
            $this->returndata( 14011, 'account not exist1', $this->curTime, []);
        }



        $city = model('city')->where(['cityid'=>$userInfo['cityid']])->find();

        $userData = model('userdata')->where(['userid'=>$userBase['userid']])->find();
        if(!$userData){
            $this->returndata( 14012, 'account not exist2', $this->curTime, []);
        }

        if($lastLoginToken==''){
            model('userlogin')->where(['userid'=>$userBase['userid'],'delflag'=>0])->update(['delflag'=>1]);

            $salt = rand_string(6);
            $lastLoginToken = model('userlogin')->setLastLoginToken($userBase['token'],$userBase['userid'],
                $this->curTime,$this->dsVersion,$salt);
            $userLogin = [
                'userid'=>$userBase['userid'],
                'last_login_time'=>$this->curTime,
                'last_login_version'=>$this->dsVersion,
                'last_login_device_id'=>$userBase['userid'],
                'salt'=>$salt,
                'last_login_token'=>$lastLoginToken,
                'last_login_ip'=>get_client_ip(),
                'last_login_lat'=>input('lat'),
                'last_login_lon'=>input('lon'),
                'delflag'=>0,
            ];

            model('userlogin')->insertGetId($userLogin);
        }


        $allControl = $this->getAllControl();
        //生成返回结果
        $this->curUserInfo = array(
            "userid"            => $userBase['userid'],
            "country_code"      => $userBase['country_code'],
            "tel"               => $userBase['tel'],
            'jointime'          => $userBase['createtime'],
            "token"             => $lastLoginToken,
            "brief"             => $userInfo['brief'],
            "nickname"          => $userInfo['nickname'],
            "email"             => $userInfo['email'],
            "avatar"            => $this->checkpictureurl($allControl['avatar_url'],$userInfo['avatar']),
            //""               => $userInfo['sex'],
            "sex"               => $userInfo['sex'],
            "birthday"          => $userInfo['birthday'],
            "city"              => $city,
            "verify_state"      => $userInfo['verify_state'],
            "verifyid"          => $userInfo['verifyid'],
            "personlink"        => $userInfo['personlink'],
            "status"            => $userInfo['status'],



        );

        $this->dsToken = $lastLoginToken;
        cache($this->dsToken,$this->curUserInfo,["expire"=>config('login_cache_expire')]);

        //方便docsdebug调试 保存本地登录信息到session
        if(config('ENV_VALUE')==1){
            session('logintoken',$this->dsToken);
        }
    }

    public function doRegister($countryCode,$tel,$pass,$code,$nickName,$avatar){
        $data = [];


        //判断昵称是否存在
        //$userInfo = model('userinfo')->where(['nickname'=>$nickName])->find();
        //if ($userInfo) {
        //    $this->returndata(11009, '昵称重复', $this->curTime, $data);
        //}

        //创建userbase
        $newUserBase = array();
        $newUserBase['country_code'] = $countryCode;
        $newUserBase['tel'] = $tel;
        $newUserBase['salt'] = rand_string(6);
        $newUserBase['pass'] = model('userbase')->setPass($pass,$newUserBase['salt']);
        $newUserBase['token'] = model('userbase')->setToken($tel,$newUserBase['pass']);
        $newUserBase['confinedtime'] = 0;
        $newUserBase['createtime'] = $this->curTime;
        $newUserBase['updatetime'] = $this->curTime;
        $userId = model('userbase')->insertGetId($newUserBase);

        if(!$userId){
            $this->returndata(14012, 'register fail', $this->curTime, $data);
        }
        //创建userinfo
        $newUserInfo['userid'] = $userId;
        $newUserInfo['brief'] = '';
        $newUserInfo['nickname'] = $nickName;
        $newUserInfo['email'] = '';
        $newUserInfo['avatar'] = $avatar;
        $newUserInfo['sex'] = 0;
        $newUserInfo['birthday'] = '';
        $newUserInfo['cityid'] = 0;
        $newUserInfo['verify_state'] = 0;
        $newUserInfo['verifyid'] = 0;
        $newUserInfo['personlink'] = '';
        $newUserInfo['status'] = 1;
        $newUserInfo['createtime'] = $this->curTime;
        $newUserInfo['updatetime'] = $this->curTime;
        model('userinfo')->insert($newUserInfo);

        $city = model('city')->where(['cityid'=>$newUserInfo['cityid']])->find();

        //创建userlogin
        $userLoginSalt = rand_string(6);
        $lastLoginToken = model('userlogin')->setLastLoginToken($newUserBase['token'],$userId,
            $this->curTime,$this->dsVersion,$userLoginSalt);
        $newUserLogin['userid'] = $userId;
        $newUserLogin['last_login_time'] = $this->curTime;
        $newUserLogin['last_login_version'] = $this->dsVersion;
        $newUserLogin['last_login_device_id'] = 0;
        $newUserLogin['salt'] = $userLoginSalt;
        $newUserLogin['last_login_token'] = $lastLoginToken;
        $newUserLogin['last_login_ip'] = get_client_ip();
        $newUserLogin['last_login_lat'] = input('lat');
        $newUserLogin['last_login_lon'] = input('lon');
        $newUserLogin['delflag'] = 0;
        model('userlogin')->insert($newUserLogin);

        //创建userdata
        $newUserData['userid'] = $userId;
        $newUserData['createtime'] = $this->curTime;
        $newUserData['updatetime'] = $this->curTime;
        model('userdata')->insert($newUserData);


        $allControl = $this->getAllControl();
        //生成返回结果
        $this->curUserInfo = array(

            "userid"            => $userId,
            "country_code"      => $newUserBase['country_code'],
            "tel"               => $newUserBase['tel'],
            'jointime'          => $newUserBase['createtime'],
            "token"             => $lastLoginToken,
            "brief"             => $newUserInfo['brief'],
            "nickname"          => $newUserInfo['nickname'],
            "email"             => $newUserInfo['email'],
            "avatar"            => $this->checkpictureurl($allControl['avatar_url'],$newUserInfo['avatar']),
            //""               => $userInfo['sex'],
            "sex"               => $newUserInfo['sex'],
            "birthday"          => $newUserInfo['birthday'],
            "city"              => $city,
            "verify_state"      => $newUserInfo['verify_state'],
            "verifyid"          => $newUserInfo['verifyid'],
            "personlink"        => $newUserInfo['personlink'],
            "status"            => $newUserInfo['status'],
        );

        $this->dsToken = $lastLoginToken;

        cache($this->dsToken,$this->curUserInfo,["expire"=>config('login_cache_expire')]);
        //方便docsdebug调试 保存本地登录信息到session
        if(config('ENV_VALUE')==1){
            session('logintoken',$this->dsToken);
        }

    }

    public function doLogout(){
        //删除用户缓存信息
        cache($this->dsToken,null,["expire"=>1]);

        //删除用户上次登录信息
        model('userlogin')
            ->where(['last_login_token'=>$this->dsToken,'delflag'=>0])
            ->update(['delflag'=>1,'updatetime'=>$this->curTime]);
    }


    public function checkCode($code,$type,$tel){

        $codeKey = md5(config('code_key_prefix').$type.$tel);
        $codeInfo = cache($codeKey);
        if(!$codeInfo){
            return false;
        }

        if($code !=$codeInfo['code']||$codeInfo['tel']!=$tel||$codeInfo['status']==1){
            return false;
        }
        else{
            $newCodeInfo = [
                'code' =>$code,
                'tel'=>$tel,
                //是否已经使用过 0未使用 1已使用
                'status'=>1
            ];
            cache($codeKey,$newCodeInfo,1);
        }
        return true;
    }

    /**
     * 限制单个ip在单位时间内的访问次数
     * @param $unitTime     指定单位时间
     * @param $frequency    限制最大访问次数
     */
    public function limitAccessFrequency($unitTime,$frequency){

        /////////////////////////////////////////////限制同一个ip使用次数（start）////////////////////////////////////////////////////////

        $clientAccessKey = 'access_count_' . $this->dsToken;
        //读取cache
        $clientAccessValue = cache($clientAccessKey);

        $clientAccessInfo = json_decode($clientAccessValue, true);
        //超过100次
        if ($clientAccessInfo && ($this->curTime - $clientAccessInfo['time'] < $unitTime)) {
            if ($clientAccessInfo['access_count'] >= $frequency) {

                $this->returndata(11006, 'request frequency is too fast', time(), []);
            } else {
                $newClientAccessInfo = array(
                    'access_count' => $clientAccessInfo['access_count'] + 1,
                    'time' => $clientAccessInfo['time']
                );

                cache($clientAccessKey,json_encode($newClientAccessInfo),["expire"=>$unitTime]);
            }
        } else {
            $newClientAccessInfo = array(
                'access_count' => 1,
                'time' => $this->curTime
            );
            cache($clientAccessKey,json_encode($newClientAccessInfo),["expire"=>$unitTime]);
        }
    }

    //接口返回json
    public function returndata($code='10000',$message='success',$time=0,$data = array()){

        if(empty($data)){
            $data = new \ArrayObject();
        }
        if(!$message){
            $message='';
        }
        if(!$time){
            $time=time();
        }

        //$data = $this->recursionEmptyArrayToObject($data);

        $res = array(
            'code'=>$code,
            'message'=>$message,
            'time'=>$time,
            'data'=>$data,
            //'sid'=>$this->makesid()
        );
        $this->sysLog($res);


        exit(json_encode($res));

    }

    public function recursionEmptyArrayToObject($data){
        if(is_array($data)){
            if(empty($data)){
                return new \ArrayObject();
            }
            else{
                foreach($data as $k=>&$v){
                    $v = $this->recursionEmptyArrayToObject($v);
                }
            }
        }
        return $data;

    }



    public function sysLog($res){
        $loginfo = [
            'userid'=>$this->curUserInfo?$this->curUserInfo['userid']:0,
            'version'=>$this->dsVersion,
            'param'=>json_encode($_GET).json_encode($_POST),
            'return'=>json_encode($res),
            'ip'=>get_client_ip(),
            'controllername'=>CONTROLLER_NAME,
            'actionname'=>ACTION_NAME,
            'createtime'=>time(),

        ];
        model('syslog')->insertGetId($loginfo);
    }



    /**
     * @param $prefixurl      图片前缀
     * @param $sourcepicture  图片地址 string或array
     */
    public function checkPictureUrl($prefixurl,$sourcepicture){
        if($sourcepicture==''){
            return '';
        }
        elseif(is_array($sourcepicture)){
            $newPictures = [];
            foreach($sourcepicture as $onePic){
                if(substr($onePic,0,4)=='http'){
                    $newPictures[]=$onePic;
                }
                else{
                    $newPictures[]=$prefixurl.$onePic;
                }
            }
            return $newPictures;
        }
        elseif(substr($sourcepicture,0,4)=='http'){
            return $sourcepicture;
        }
        else{
            return $prefixurl.$sourcepicture;
        }
    }

    public function getAllControl(){
        if(!$this->allControl){
            //获取控制参数
            $controls = model('control')->all();
            foreach($controls as $v){
                $this->allControl[$v['controlk']] = $v['controlv'];
            }
        }
        return $this->allControl;
    }

    public function getAllServiceType(){
        if(!$this->allServiceType){
            //获取控制参数
            $serviceTypes = model('servicetype')->all();
            foreach($serviceTypes as $v){
                $this->allServiceType[$v['servicetypeid']] = $v['name'];
            }
        }
        return $this->allServiceType;
    }

    //生成sid
    public function makesid(){

        return md5(uniqid().rand(100000,1000000).time());
    }

    public function logResult($word='word',$file='') {
        $fp = $file==''?fopen("/tmp/qiniulog.txt","a"):fopen($file,"a");
        flock($fp, LOCK_EX) ;
        fwrite($fp,"time:".date("YmdHis", time())."\n".$word."\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }


}
