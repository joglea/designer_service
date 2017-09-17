<?php
namespace app\index\controller;

use app\common\controller\Front;
use app\common\controller\Message;
use anerg\OAuth2\OAuth;
use anerg\helper\Exception;

require_once APP_PATH . "/../extend/wxpay/lib/WxPay.Config.php";

/**
 * Class Index
 *
 * @classdesc 登录注册相关接口类
 * @package app\index\controller
 */
class Index extends Front
{
    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    微信登录接口
     * @url     /index/wxLogin
     * @method  POST
     * @version 1000
     * @params  code 'xxx' STRING 微信登录成功返回code NO
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function wxLogin(){
        $data=[];
        $code = input('request.code','');
        if($code==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $get_session_url = 'https://api.weixin.qq.com/sns/jscode2session'.
            '?appid='.\WxPayConfig::APPID.'&secret='.\WxPayConfig::APPSECRET.'&js_code='.
                $code.'&grant_type=authorization_code';
            $curlService = new \common\CurlService();
            $get_session_info = $curlService->curl($get_session_url,[],'');

            //var_dump($get_session_url,$get_session_info);exit;
            /*$get_session_info = [
                'openid'=>1,
                'session_key'=>'333',
                'unionid'=>1,
            ];*/

            if($get_session_info&&isset($get_session_info['openid'])){
                $unionId = '';//$get_session_info['unionid'];
                $openId = $get_session_info['openid'];

                $userthird = model('userthird')->where([
                    'openid'=>$openId,'channel'=>3,'delflag'=>0
                ])->find();

                if($userthird){
                    $isRegister = 0;
                    $userId = $userthird['userid'];
                    $userBase = model('userbase')->where(['userid'=>$userId])->find();
                    $this->doLogin($userBase,'');
                }
                else{

                    $isRegister = 1;
                    //注册
                    $this->doRegister(86,'','111111',$code,'','');
                    $newUserThird = [
                        'userid'    => $this->curUserInfo['userid'],
                        'channel'   => 3,
                        'token'     => $get_session_info['session_key'],
                        'openid'    => $openId,
                        'nickname'  => '',
                        'gender'    => 0,
                        'createtime'=> $this->curTime,
                        'updatetime'=> $this->curTime,
                        'delflag'   => 0
                    ];
                    model('userthird')->insertGetId($newUserThird);
                }

                //返回结果
                $data = array();
                $data['isRegister'] = $isRegister;
                $data['User'] = $this->curUserInfo;
                $this->returndata(10000, 'login success', $this->curTime, $data);

            }
            else{
                $msg = isset($get_session_info['errmsg'])?$get_session_info['errmsg']:'';
                $this->returndata(14015, 'wxlogin fail '.$msg, $this->curTime, $data);
            }
        }catch (Exception $e){
            $this->returndata(11000, 'server error'.$e->getMessage(), $this->curTime, []);
        }

    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    微信登录接口
     * @url     /index/wxSaveUserInfo
     * @method  POST
     * @version 1000
     * @params  nickname 'xxx' STRING 昵称 NO
     * @params  avatar 'xxx' STRING 头像地址 NO
     * @params  gender 2 INT 0未知性别1男2女 NO
     * @params  city 'xxx' STRING 城市 NO
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function wxSaveUserInfo(){
        //返回结果
        $data = [];

        //获取接口参数

        $nickname = input('request.nickname','');
        $avatar = input('request.avatar','');
        $gender = input('request.gender','');
        $city = input('request.city','');

        $cityinfo = model('city')->where(['name'=>$city])->find();

        //验证参数是否为空
        if($nickname==''||$gender<0||$avatar==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            //判断openid是否存在
            $userThird = model('userthird')
                ->where(['channel'=>3,'userid'=>$this->curUserInfo['userid'],'delflag'=>0])->find();
            if ($userThird) {
                model('userthird')
                    ->where(['userid'=>$userThird['userid']])
                    ->update([
                        'nickname'=>$nickname,
                        'gender'=>$gender,
                        'updatetime'=>$this->curTime
                    ]);
                $ret = model('userinfo')
                    ->where(['userid'=>$userThird['userid']])
                    ->update([
                        'nickname'=>$nickname,
                        'avatar'=>$avatar,
                        'sex'=>$gender,
                        'cityid'=>$cityinfo['cityid'],
                        'updatetime'=>$this->curTime
                    ]);
                $this->returndata(10000, 'save success', $this->curTime, $data);
            }
            else{

                $this->returndata(14001, 'third user not exist ', $this->curTime, $data);
            }

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }



    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    打开app需要获取的配置信息接口
     * @url     /index/getControls
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function getControls(){
        try{
            $controls = $this->getAllControl();
            //返回结果
            $data = array();
            $data['controls'] = [
                'avatar_url'=>$controls['avatar_url'],
                'task_image_url'=>$controls['task_image_url'],
            ];

            $this->returndata(10000, 'get success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    用户登录接口
     * @url     /index/login
     * @method  GET
     * @version 1000
     * @params  countrycode 86 INT 国家编码 YES
     * @params  tel 0 BIGINT 手机号 YES
     * @params  pass '' STRING 用户密码md5后的值 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function login(){

        //获取接口参数
        $tel = input('request.tel');
        $pass = input('request.pass');
        $countryCode = intval(input('request.countrycode'))?intval(input('request.countrycode')):86;

        //验证参数是否为空
        if(!$tel||!$pass){
            $this->returndata( 14001,  'param error', $this->curTime, []);
        }
        if(strlen($pass)<32){
            $pass = md5($pass);
        }
        try{

            $userBaseWhere = [
                'country_code'=>$countryCode,
                'tel'=>$tel
            ];
            $userBase = model('userbase')->where($userBaseWhere)->find();

            if(!$userBase){
                $this->returndata( 14002, 'account not exist', $this->curTime, []);
            }
            elseif($userBase && $userBase['confinedtime'] >= $this->curTime){
                $this->returndata( 14003, 'account confined', $this->curTime, []);
            }
            $curpass = model('userbase')->setPass($pass,$userBase['salt']);

            if($curpass != $userBase['pass']){
                $this->returndata( 14004, 'account password error', $this->curTime, []);
            }

            $this->doLogin($userBase,'');


            //返回结果
            $data = array();
            $data['User'] = $this->curUserInfo;



            $this->returndata(10000, 'login success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    用户自动登录接口
     * @url     /index/loginAuto
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function loginAuto(){

        //返回结果
        $data = array();

        try{

            $data['User'] = $this->curUserInfo;

            $this->returndata(10000, 'login success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    第三方登录接口
     * @url     /index/thirdLogin
     * @method  GET
     * @version 1000
     * @params  channel 1 INT 三方类型1qq2微博3微信 YES
     * @params  token '' STRING 三方访问token YES
     * @params  openid '' STRING 三方openid YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function thirdLogin(){

        //接收参数
        $channel = intval(input('request.channel'));
        $token = input('request.token');
        $openId = input('request.openid');


        //返回结果
        $data = [];
        //三方账号是否绑定过 0未绑定  1已绑定
        $data['Bind'] = 0;
        $data['User'] = [];
        //验证是否有空值
        if (!in_array($channel,[1,2,3]) || !$openId || !$token ) {
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{
            $channelname = config('thirdloginchannel')[$channel];
            $channelConfig = config($channelname);

            $OAuth  = OAuth::getInstance($channelConfig, $channelname);
            $OAuth->setDisplay('default'); //此处为可选,若没有设置为mobile,则跳转的授权页面可能不适合手机浏览器访问

            $params = array(
                'access_token'       => $token,
                'openid'             => $openId
            );

            if($channel ==2){
                $params = array(
                    'access_token'       => '2.003aWgxBW35TrB40667a2a79FX3ETB',
                    'openid'             => '1798071694'
                );
            }

            $checkData =$OAuth->checkTokenAndOpenId($params);
            if($checkData){
                //验证三方信息是否存在
                $userthird = model('userthird')->where(['channel'=>$channel,'openid'=>$openId,'delflag'=>0])
                    ->find();
                if($userthird){

                    $data['Bind'] = 1;
                    if($userthird['token']!=$token ){
                        model('userthird')->where(['channel'=>$channel,'openid'=>$openId,'delflag'=>0])
                            ->update(['token'=>$token,'updatetime'=>$this->curTime]);
                    }
                    $userId = $userthird['userid'];
                }
                else{
                    $this->returndata(10000, 'third account is unbind', $this->curTime, $data);
                }
            }
            else{
                $this->returndata(14003, 'third login fail', $this->curTime, $data);
            }

            $userBase = model('userbase')->where(['userid'=>$userId])->find();
            $this->doLogin($userBase,'');


            $data['User'] = $this->curUserInfo;



            $this->returndata(10000, 'login success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error'.$e->getMessage(), $this->curTime, []);
        }
    }





    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    用户注册接口
     * @url     /index/register
     * @method  POST
     * @version 1000
     * @params  countrycode 86 INT 国家编码 YES
     * @params  tel 0 BIGINT 手机号 YES
     * @params  pass '' STRING 用户密码md5后的值 YES
     * @params  code '362541' STRING 验证码 YES
     * @params  nickname '' STRING 昵称 NO
     * @params  avatar '' STRING 头像 NO
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function register(){

        $data = array();

        //接收参数
        $countryCode = intval(input('request.countrycode'))?intval(input('request.countrycode')):86;
        $tel = intval(input('request.tel'));
        $pass = input('pass');
        if(32 != strlen($pass)){
            $pass = md5($pass);
        }
        $code = input('code');

        $nickName = safestr(input('request.nickname'));
        if($nickName==''){
            $nickName = '游客'.rand_string(8);
        }
        $avatar = input('request.avatar');
        if(!$avatar){
            $avatar = model('userinfo')->generateAvatar();
        }

        //验证是否有空值
        if (!preg_match("/^1[34578]{1}\d{9}$/",$tel)) {
            $this->returndata(14001, 'param error', $this->curTime, $data);
        }

        try{

            //注册
            $this->doRegister($countryCode,$tel,$pass,$code,$nickName,$avatar);

            //返回结果
            $data = array();
            $data['User'] = $this->curUserInfo;

            $this->returndata(10000, 'register success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }



    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    第三方注册接口
     * @url     /index/thirdRegister
     * @method  POST
     * @version 1000
     * @params  channel 1 INT 三方类型1qq2微博3微信 YES
     * @params  token '' STRING 三方访问token YES
     * @params  openid '' STRING 三方openid YES
     * @params  countrycode 86 INT 国家编码 YES
     * @params  tel 0 BIGINT 手机号 YES
     * @params  pass '' STRING 用户密码md5后的值 YES
     * @params  code '362541' STRING 验证码 YES
     * @params  nickname '' STRING 昵称 NO
     * @params  avatar '' STRING 头像 NO
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function thirdRegister(){

        $data = array();

        //接收参数
        $channel = intval(input('request.channel'));
        $token = input('request.token');
        $openId = input('request.openid');

        //接收参数
        $countryCode = intval(input('request.countrycode'))?intval(input('request.countrycode')):86;
        $tel = intval(input('request.tel'));
        $pass = input('pass');
        if(32 != strlen($pass)){
            $pass = md5($pass);
        }
        $code = input('code');

        $nickName = safestr(input('request.nickname'));
        $avatar = input('request.avatar');
        if(!$avatar){
            $avatar = model('userinfo')->generateavatar();
        }

        //验证是否有空值
        if (!in_array($channel,[1,2,3]) || !$openId || !$token ) {
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }
        //验证是否有空值
        if (!preg_match("/^1[34578]{1}\d{9}$/",$tel)) {
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }


        try{
            $channelName = config('thirdloginchannel')[$channel];
            $channelConfig = config($channelName);

            $OAuth  = OAuth::getInstance($channelConfig, $channelName);
            $OAuth->setDisplay('default'); //此处为可选,若没有设置为mobile,则跳转的授权页面可能不适合手机浏览器访问

            $params = array(
                'access_token'       => $token,
                'openid'             => $openId
            );

            if($channel ==2){
                $params = array(
                    'access_token'       => '2.003aWgxBW35TrB40667a2a79FX3ETB',
                    'openid'             => '1798071694'
                );
            }

            $checkData =$OAuth->checkTokenAndOpenId($params);
            if($checkData){
                //验证三方信息是否存在
                $userthird = model('userthird')->where(['channel'=>$channel,'openid'=>$openId,'delflag'=>0])
                    ->find();
                if($userthird){
                    if($userthird['token']!=$token ){
                        model('userthird')->where(['channel'=>$channel,'openid'=>$openId,'delflag'=>0])
                            ->update(['token'=>$token,'updatetime'=>$this->curTime]);
                    }
                    $userId = $userthird['userid'];

                    $userBase = model('userbase')->where(['userid'=>$userId])->find();
                    $this->doLogin($userBase,'');

                }
                else{

                    //注册
                    $this->doRegister($countryCode,$tel,$pass,$code,$nickName,$avatar);

                    $newUserThird = [
                        'userid'    => $this->curUserInfo['userid'],
                        'channel'   => $channel,
                        'token'     => $token,
                        'openid'    => $openId,
                        'nickname'  => $nickName,
                        'gender'    => 0,
                        'createtime'=> $this->curTime,
                        'updatetime'=> $this->curTime,
                        'delflag'   => 0
                    ];
                    model('userthird')->insertGetId($newUserThird);
                }
            }
            else{
                $this->returndata(14003, 'third login fail', $this->curTime, $data);
            }

            //返回结果
            $data = array();
            $data['User'] = $this->curUserInfo;

            $this->returndata(10000, 'register success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error'.$e->getMessage(), $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    忘记密码接口
     * @url     /index/forgetPwd
     * @method  POST
     * @version 1000
     * @params  countrycode 86 INT 国家编码 YES
     * @params  tel 0 BIGINT 手机号 YES
     * @params  pass '' STRING 用户密码md5后的值,32位 YES
     * @params  code '362541' STRING 验证码 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function forgetPwd(){
        $data = array();

        //接收参数
        $countryCode = intval(input('request.countrycode'))?intval(input('request.countrycode')):86;
        $tel = intval(input('request.tel'));
        $pass = input('pass');
        $code = input('code');
        //验证参数是否为空
        if(!$tel||!$pass||!$code){
            $this->returndata(14001, 'param error', $this->curTime, $data);
        }

        if(32 != strlen($pass)){
            $pass = md5($pass);
        }
        //验证码发送对象类型  1注册 2找回密码
        $type = 2;
        if(!$this->checkCode($code,$type,$tel)){
            $this->returndata(14010, 'code verify fail', $this->curTime, $data);
        }

        try{

            //更新userbase
            $newUserBase['salt'] = rand_string(6);
            $newUserBase['pass'] = model('userbase')->setPass($pass,$newUserBase['salt']);
            $newUserBase['token'] = model('userbase')->setToken($tel,$newUserBase['pass']);
            $newUserBase['updatetime'] = $this->curTime;
            model('userbase')->where(['country_code'=>$countryCode,'tel'=>$tel])->update($newUserBase);

            //把用户登出
            $this->doLogout();

            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error'.$e->getMessage(), $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    修改密码接口
     * @url     /index/changePwd
     * @method  POST
     * @version 1000
     * @params  pass '' STRING 用户密码md5后的值 YES
     * @params  newpass '' STRING 确认用户密码md5后的值 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function changePwd(){
        $data = array();

        //接收参数
        $pass = input('pass');
        $newpass = input('newpass');
        //验证参数是否为空
        if(!$pass||!$newpass){
            $this->returndata(14001, 'param error', $this->curTime, $data);
        }
        if(32 != strlen($pass)){
            $pass = md5($pass);
        }
        if(32 != strlen($newpass)){
            $newpass = md5($newpass);
        }
        try{
            $userBase = model('userbase')->where(['userid'=>$this->curUserInfo['userid']])->find();
            $tel = $userBase['tel'];
            $oldpass = $userBase['pass'];

            if(model('userbase')->setPass($pass,$userBase['salt']) == $oldpass){
                //更新userbase
                $newUserBase['salt'] = rand_string(6);
                $newUserBase['pass'] = model('userbase')->setPass($newpass,$newUserBase['salt']);
                $newUserBase['token'] = model('userbase')->setToken($tel,$newUserBase['pass']);
                $newUserBase['updatetime'] = $this->curTime;
                model('userbase')->where(['userid'=>$this->curUserInfo['userid']])->update($newUserBase);

                //把用户登出
                $this->doLogout();
            }
            else{
                $this->returndata(14002, 'pass error', $this->curTime, $data);
            }
            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error'.$e->getMessage(), $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    发送用户验证码接口
     * @url     /index/sendCode
     * @method  POST
     * @version 1000
     * @params  tel 0 BIGINT 手机号 YES
     * @params  type 1 INT 验证码发送对象类型,1注册,2忘记密码 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function sendCode(){

        $tel = input('tel',0);
        //验证码发送对象类型  1注册 2找回密码
        $type = input('type',1);

        $data = [];
        try{
            if(!in_array($type,[1,2]) ){
                $this->returndata(14001, 'param error', $this->curTime, $data);
            }
            $data['type'] = $type;

            if (!preg_match("/^1[34578]{1}\d{9}$/",$tel)) {
                $this->returndata(14002, 'tel illegal', $this->curTime, $data);
            }

            $userbase = model('userbase')->where(['tel'=>$tel])->find();
            if($type == 1 && $userbase){
                $this->returndata(14003, 'tel exist', $this->curTime, $data);
            }
            else if($type == 2 && !$userbase){
                $this->returndata(14004, 'tel not exist', $this->curTime, $data);
            }

            $codeKey =  md5(config('code_key_prefix').$type.$tel);
            $oldcodeinfo = cache($codeKey);

            if($oldcodeinfo && $oldcodeinfo['status']==0){
                $code = $oldcodeinfo['code'];
            }
            else{
                $code =generate_code(config('code_length'));
                $newcodeinfo = [
                    'code' =>$code,
                    'tel'=>$tel,
                    //是否已经使用过 0未使用 1已使用
                    'status'=>0,
                ];

                cache($codeKey,$newcodeinfo,config('code_time_limit'));
            }

            //发送验证码到手机
            $text="#code#=".$code;
            $tplid=1036155;

            $message = new Message();
            $result = $message->sendYunpianMsg($text,$tel,$tplid);

            if($result===true){
                if(config('ENV_VALUE')==1){
                    $this->returndata(10000, '验证码'.$code.'已经发送到'.$tel, $this->curTime, $data);
                }
                else{
                    $this->returndata(10000, 'code send success', $this->curTime, $data);
                }
            }else{
                $this->returndata(11000, 'code send fail'.$result, $this->curTime, $data);
            }
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    登出接口
     * @url     /index/logout
     * @method  POST
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function logout(){

        $data = [];
        try{

            $this->doLogout();
            //返回结果
            $this->returndata(10000, 'logout success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }





}
