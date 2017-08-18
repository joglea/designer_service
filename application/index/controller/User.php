<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class User
 *
 * @classdesc 用户接口类
 * @package app\index\controller
 */
class User extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    用户信息修改接口
     * @url     /user/userSave
     * @method  POST
     * @version 1000
     * @params  nickname '' STRING 昵称 NO
     * @params  brief '' STRING 简介 NO
     * @params  brief_service '' STRING 服务简介 NO
     * @params  brief_exp '' STRING 经验简介 NO
     * @params  avatar '' STRING 头像 NO
     * @params  sex '1' STRING 性别1男2女 NO
     * @params  birthday '1990-03-01' STRING 生日 NO
     * @params  measurements '90,90,90' STRING 三围 NO
     * @params  height '0' STRING 三围 NO
     * @params  weight '0' STRING 三围 NO
     * @params  cityid '0' STRING 城市id NO
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function userSave(){

        //返回结果
        $data = [];

        //获取接口参数
        $nickName = input('request.nickname');
        $brief = input('request.brief');
        $briefService = input('request.brief_service');
        $briefExp = input('request.brief_exp');
        $avatar = input('request.avatar');
        $sex = input('request.sex');
        $birthday = input('request.birthday');
        $measurements = input('request.measurements');
        $height = input('request.height');
        $weight = input('request.weight');
        $cityid = input('request.cityid');


        //验证用户名是否为空


        try{

            $userinfo = [];
            if($nickName!=''){
                if(model('userinfo')->checkNickName($nickName)){
                    $userinfo['nickname'] = $nickName;
                    $this->curUserInfo['nickname']= $userinfo['nickname'];
                }
            }
            if($brief!=''){
                $userinfo['brief'] = mb_substr($brief,0,500,'utf-8');
                $this->curUserInfo['brief']= $userinfo['brief'];
            }
            if($briefService!=''){
                $userinfo['brief_service'] = mb_substr($briefService,0,500,'utf-8');
                $this->curUserInfo['brief_service']= $userinfo['brief_service'];
            }
            if($briefExp!=''){
                $userinfo['brief_exp'] = mb_substr($briefExp,0,500,'utf-8');
                $this->curUserInfo['brief_exp']= $userinfo['brief_exp'];
            }
            if($avatar!=''){
                $allControls = $this->getAllControl();
                $userinfo['avatar'] = $this->checkPictureUrl($allControls['avatar_url'],$avatar);
                $this->curUserInfo['avatar']= $userinfo['avatar'];
            }

            if(in_array($sex,[1,2])){
                $userinfo['sex'] = $sex;
                $this->curUserInfo['sex']= $userinfo['sex'];
            }
            if($birthday!=''){
                $userinfo['birthday'] = $birthday;
                $this->curUserInfo['birthday']= $userinfo['birthday'];
            }
            if($measurements!=''){
                $userinfo['measurements'] = $measurements;
                $this->curUserInfo['measurements']= $userinfo['measurements'];
            }
            if($height>0){
                $userinfo['height'] = $height;
                $this->curUserInfo['height']= $userinfo['height'];
            }
            if($weight>0){
                $userinfo['weight'] = $weight;
                $this->curUserInfo['weight']= $userinfo['weight'];
            }
            if($cityid>=0&&$cityid!==''){

                $city = model('city')->where(['id'=>$cityid])->find();
                if($city){
                    $userinfo['cityid'] = $cityid;
                    $this->curUserInfo['city']= $city;
                }
            }
            if(!$userinfo){
                $this->returndata( 14001,  'params error', $this->curTime, $data);
            }

            $userinfo['updatetime'] = $this->curTime;

            model('userinfo')->where(['userid'=>$this->curUserInfo['userid']])->update($userinfo);

            //生成返回结果
            $this->jzToken = $this->curUserInfo['token'];
            cache($this->jzToken,$this->curUserInfo,["expire"=>config('login_cache_expire')]);

            $this->returndata(10000, 'save success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    用户信息查看
     * @url     /user/userView
     * @method  GET
     * @version 1000
     * @params  userid '0' STRING 用户id为0表示当前用户自己 NO
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function userView(){

        //返回结果
        $data = [];

        //获取接口参数
        $userId = input('request.userid');

        if($userId<0){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            if($userId == 0){

                $data['user'] = array(
                    "userid"            => $this->curUserInfo['userid'],
                    //"country_code"      => $this->curUserInfo['country_code'],
                    //"tel"               => $this->curUserInfo['tel'],
                    'jointime'          => $this->curUserInfo['jointime'],
                    "brief"             => $this->curUserInfo['brief'],
                    "brief_service"     => $this->curUserInfo['brief_service'],
                    "brief_exp"         => $this->curUserInfo['brief_exp'],
                    "nickname"          => $this->curUserInfo['nickname'],
                    "avatar"            => $this->curUserInfo['avatar'],
                    "sex"               => $this->curUserInfo['sex'],
                    "birthday"          => $this->curUserInfo['birthday'],
                    "measurements"      => $this->curUserInfo['measurements'],
                    "height"            => $this->curUserInfo['height'],
                    "weight"            => $this->curUserInfo['weight'],
                    "city"              => $this->curUserInfo['city'],
                    "verify_state"      => $this->curUserInfo['verify_state'],
                    "zhima_code"        => $this->curUserInfo['zhima_code'],
                    "identitys"         => $this->curUserInfo['identitys'],
                    "blog_counter"      => $this->curUserInfo['blog_counter'],
                    "follow_counter"    => $this->curUserInfo['follow_counter'],
                    "fans_counter"      => $this->curUserInfo['fans_counter'],
                    'hxid'              => $this->curUserInfo['hxid'],           //环信id
                    'hxpa'              => $this->curUserInfo['hxpa']          //环信密码
                );
            }
            else{


                $userBase = model('userbase')->where(['userid'=>$userId])->find();
                if(!$userBase){

                    $this->returndata( 14002, 'account not exist1', $this->curTime, []);
                }

                $userInfo = model('userinfo')->where(['userid'=>$userBase['userid']])->find();
                if(!$userInfo){
                    $this->returndata( 14003, 'account not exist2', $this->curTime, []);
                }
                $userServiceTypes = model('userservicetype')->where(['userid'=>$userBase['userid']])->select();
                $serviceTypes = [];
                $this->getAllServiceType();
                foreach($userServiceTypes as $oneServicetype){
                    $serviceTypes[]=[
                        'servicetypeid'=>$oneServicetype['servicetypeid'],
                        'name'=>$this->allServiceType[$oneServicetype['servicetypeid']],
                        'exp'=>$oneServicetype['exp'],
                    ];
                }


                $city = model('city')->where(['id'=>$userInfo['cityid']])->find();

                $userData = model('userdata')->where(['userid'=>$userBase['userid']])->find();
                if(!$userData){
                    $this->returndata( 14004, 'account not exist3', $this->curTime, []);
                }

                $userHx=model('userhx')->where(['userid'=>$userBase['userid']])->find();


                $allControl = $this->getAllControl();

                $data['user'] = array(
                    "userid"            => $userBase['userid'],
                    //"country_code"      => $userBase['country_code'],
                    //"tel"               => $userBase['tel'],
                    'jointime'          => $userBase['createtime'],
                    "brief"             => $userInfo['brief'],
                    "brief_service"     => $userInfo['brief_service'],
                    "brief_exp"         => $userInfo['brief_exp'],
                    "nickname"          => $userInfo['nickname'],
                    "avatar"            => $this->checkpictureurl($allControl['avatar_url'],$userInfo['avatar']),
                    "sex"               => $userInfo['sex'],
                    "birthday"          => $userInfo['birthday'],
                    "measurements"      => $userInfo['measurements'],
                    "height"            => $userInfo['height'],
                    "weight"            => $userInfo['weight'],
                    "city"              => $city,
                    "verify_state"      => $userInfo['verify_state'],
                    "zhima_code"        => model('userinfo')->getZhimaState($userInfo['zhima_code']),
                    "identitys"         => $serviceTypes,
                    "blog_counter"      => $userData['blog_counter'],
                    "follow_counter"    => $userData['follow_counter'],
                    "fans_counter"      => $userData['fans_counter'],
                    'hxid'              => $userHx['hx_id'],           //环信id
                    'hxpa'              => $userHx['hx_pass']          //环信密码
                );
            }


            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }



    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    获取用户点赞条信息接口(暂时废弃)
     * @url     /user/getUserFavourBar
     * @method  GET
     * @version 1000
     *
     */
    public function getUserFavourBar(){

        //获取接口参数

        //返回结果
        $data = array();

        try{

            $userFavourBarWhere = [
                'userid'=>$this->curUserInfo['userid']
            ];
            $userFavourBar = model('userfavourbar')->where($userFavourBarWhere)->find();

            $favourLimitLength = config('favour_limit_length');
            if(!$userFavourBar){

                //生成返回结果
                $userFavourBar = array(

                    "userid"            =>  $this->curUserInfo['userid'],
                    "limit_length"      =>  $favourLimitLength,
                    "remain_length"     =>  $favourLimitLength,
                    "last_restore_time" =>  $this->curTime,
                    'total_count'       =>  0,
                    "createtime"        =>  $this->curTime,
                    "updatetime"        =>  $this->curTime
                );
                model('userfavourbar')->insert($userFavourBar);
                $remainLenght = $userFavourBar['limit_length'];
                $nextRestoreTime = 0;
            }
            else{
                //点赞条是否满格
                if($userFavourBar['remain_length']<$userFavourBar['limit_length']){
                    $diffTime = $this->curTime-$userFavourBar['last_restore_time'];
                    $favourRestoreTime = config('favour_restore_time');
                    if($diffTime >= $favourRestoreTime){
                        $restoreFavours = floor(bcdiv($diffTime,$favourRestoreTime));
                        if($userFavourBar['remain_length']+$restoreFavours>=$favourLimitLength){
                            $remainLenght = $favourLimitLength;
                            $restoreTime = $this->curTime;
                            $nextRestoreTime = 0;
                        }
                        else{
                            $remainLenght = $userFavourBar['remain_length']+$restoreFavours;
                            $restoreTime = $userFavourBar['last_restore_time']+bcmul($restoreFavours,$favourRestoreTime);
                            $nextRestoreTime = $favourRestoreTime - $diffTime % $favourRestoreTime;
                        }

                        $updateData = [
                            "remain_length"     =>  $remainLenght,
                            "last_restore_time" =>  $restoreTime,
                            'total_count'       =>  0,
                            "updatetime"        =>  $this->curTime
                        ];
                        model('userfavourbar')->where(['userid'=>$this->curUserInfo['userid']])->update($updateData);
                    }
                    else{
                        $nextRestoreTime = $favourRestoreTime - $diffTime;
                        $remainLenght = $userFavourBar['remain_length'];
                    }
                }
                else{
                    $remainLenght = $userFavourBar['limit_length'];
                    $nextRestoreTime = 0;
                }
            }


            $data['favourBarInfo']=array(   
                'limit_length'      => $userFavourBar['limit_length'],  //点赞条长度上限
                'remain_length'     => $remainLenght,                   //剩余的点赞条长度
                'next_restore_time' => $nextRestoreTime                 //离下一次恢复一格点赞条需要的时间 单位s
            );

            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    用户消耗点赞条的接口(暂时废弃)
     * @url     /user/consumeUserFavourBar
     * @method  POST
     * @version 1000
     * @params  objectid 1 INT 被点赞的对象id YES
     * @params  objecttype 0 INT 对象类型 YES
     *
     */
    public function consumeUserFavourBar(){

        //获取接口参数
        $objectId = input('objectid');
        $objectType = input('objecttype');

        //返回结果
        $data = array();
        if($objectId <= 0||$objectType <= 0){
            $this->returndata(14001, '参数错误', $this->curTime, $data);
        }

        try{

            //点赞条是否为空 0非空 1空
            $favourBarIsEmpty = 0;
            $userFavourBarWhere = [
                'userid'=>$this->curUserInfo['userid']
            ];
            $userFavourBar = model('userfavourbar')->where($userFavourBarWhere)->find();

            $favourLimitLength = config('favour_limit_length');
            $favourRestoreTime = config('favour_restore_time');

            if(!$userFavourBar){
                //生成返回结果
                $userFavourBar = array(
                    "userid"            =>  $this->curUserInfo['userid'],
                    "limit_length"      =>  $favourLimitLength,
                    "remain_length"     =>  $favourLimitLength-1,
                    "last_restore_time" =>  $this->curTime,
                    'total_count'       =>  1,
                    "createtime"        =>  $this->curTime,
                    "updatetime"        =>  $this->curTime
                );
                $favourBarId = model('userfavourbar')->insert($userFavourBar);
                $remainLenght = $userFavourBar['remain_length'];
                $nextRestoreTime = $favourRestoreTime;
            }
            else{
                $favourBarId = $userFavourBar['favourbarid'];

                //点赞条是否满格
                if($userFavourBar['remain_length']>=$userFavourBar['limit_length']){
                    $remainLenght = $userFavourBar['limit_length']-1;
                    $restoreTime = $this->curTime;
                    $nextRestoreTime = $favourRestoreTime;
                }

                else{
                    $diffTime = $this->curTime - $userFavourBar['last_restore_time'];
                    if($diffTime >= $favourRestoreTime){
                        $restoreFavours = floor(bcdiv($diffTime,$favourRestoreTime));
                        if($userFavourBar['remain_length']+$restoreFavours>=$favourLimitLength){
                            $remainLenght = $favourLimitLength-1;
                            $restoreTime = $this->curTime;
                            $nextRestoreTime = $favourRestoreTime;
                        }
                        else{
                            $remainLenght = $userFavourBar['remain_length']+$restoreFavours-1;
                            $restoreTime = $userFavourBar['last_restore_time']+bcmul($restoreFavours,$favourRestoreTime);
                            $nextRestoreTime = $favourRestoreTime - $diffTime % $favourRestoreTime;
                        }
                    }
                    else{
                        if($userFavourBar['remain_length'] < 1){
                            $remainLenght = 0;
                            $favourBarIsEmpty = 1;
                        }
                        else{
                            $remainLenght = $userFavourBar['remain_length']-1;
                        }

                        $restoreTime = $userFavourBar['last_restore_time'];
                        $nextRestoreTime = $favourRestoreTime - $diffTime;
                    }
                }
                $updateData = [
                    "remain_length"     =>  $remainLenght,
                    "last_restore_time" =>  $restoreTime,
                    'total_count'       =>  $userFavourBar['total_count']+1,
                    "updatetime"        =>  $this->curTime
                ];
                //更新点赞条
                model('userfavourbar')->where(['favourbarid'=>$favourBarId])->update($updateData);
            }

            $data['favourBarInfo']=array(
                'limit_length'      => $userFavourBar['limit_length'],  //点赞条长度上限
                'remain_length'     => $remainLenght,                   //剩余的点赞条长度
                'next_restore_time' => $nextRestoreTime                 //离下一次恢复一格点赞条需要的时间 单位s
            );

            //点赞条非空 才能消耗点赞成功
            if(!$favourBarIsEmpty){
                $userFavourData = [
                    'userid'    => $this->curUserInfo['userid'],
                    'objectid'  => $objectId,
                    'objecttype'=> $objectType,
                    'createtime'=> $this->curTime,
                    'delflag'   => 0
                ];
                //插入点赞记录
                model('userfavour')->insert($userFavourData);

                $this->returndata(10000, '获取成功', $this->curTime, $data);
            }
            else{
                $this->returndata(14002, '点赞失败', $this->curTime, $data);
            }

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    用户地址列表接口
     * @url     /user/addressList
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function addressList(){
        //返回结果
        $data = [];

        try{
            $addressList = model('address')->where(['userid'=>$this->curUserInfo['userid'],'delflag'=>0])
                ->order('is_default desc')->select();

            $newAddressList = [];
            foreach($addressList as $oneAddress){
                $newAddressList[] = [
                    'addressid' => $oneAddress['addressid'],
                    'userid'    => $oneAddress['userid'],
                    'address'   => $oneAddress['address'],
                    'phone'     => $oneAddress['phone'],
                    'name'      => $oneAddress['name'],
                    'is_default'=> $oneAddress['is_default'],
                    'createtime'=> $oneAddress['createtime'],
                ];
            }

            $data['addressList'] = $newAddressList;
            $this->returndata(10000, 'get success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建用户地址接口
     * @url     /user/addressCreate
     * @method  POST
     * @version 1000
     * @params  address '' STRING 地址 YES
     * @params  phone '' STRING 手机号 YES
     * @params  name 'xxx' STRING 名称 YES
     * @params  is_default 0 INT 是否设为默认地址0否1是 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function addressCreate(){

        //返回结果
        $data = [];

        //获取接口参数
        $address = input('request.address','');
        $phone = input('request.phone','');
        $name = input('request.name','');
        $isDefault = input('request.is_default',0);

        //验证参数是否为空
        if($address=='' || $phone==''||$name==''||!in_array($isDefault,[0,1])){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            if($isDefault == 1){
                model('address')->where(['userid'=>$this->curUserInfo['userid']])
                    ->update(['is_default'=>0,'updatetime'=>$this->curTime]);
            }
            //创建地址信息
            $newAddress = [
                'userid'        => $this->curUserInfo['userid'],
                'address'       => $address,
                'phone'         => $phone,
                'name'          => $name,
                'is_default'    => $isDefault,
                'createtime'    => $this->curTime,
                'updatetime'    => $this->curTime,
                'delflag'       => 0

            ];
            $addressId = model('address')->insertGetId($newAddress);

            $data = array(
                'addressid'=> $addressId
            );
            $this->returndata(10000, 'create success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    修改用户地址接口
     * @url     /user/addressEdit
     * @method  POST
     * @version 1000
     * @params  addressid 0 INT 地址id YES
     * @params  address '' STRING 地址 YES
     * @params  phone '' STRING 手机号 YES
     * @params  name 'xxx' STRING 名称 YES
     * @params  is_default 0 INT 是否设为默认地址0否1是 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function addressEdit(){

        //返回结果
        $data = [];

        //获取接口参数
        $addressId = input('request.addressid',0);
        $address = input('request.address','');
        $phone = input('request.phone','');
        $name = input('request.name','');
        $isDefault = input('request.is_default',0);

        //验证参数是否为空
        if($addressId<=0){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $saveData = [];


            if($address!=''){
                $saveData['address'] = $address;
            }
            if($phone!=''){
                $saveData['phone'] = $phone;
            }
            if($name!=''){
                $saveData['name'] = $name;
            }
            if($isDefault == 1){
                model('address')->where(['userid'=>$this->curUserInfo['userid']])
                    ->update(['is_default'=>0,'updatetime'=>$this->curTime]);
                $saveData['is_default'] = 1;
            }

            if(!$saveData){
                $this->returndata(10000, 'no change', $this->curTime, $data);
            }
            $saveData['updatetime'] = $this->curTime;

            model('address')->where(['addressid'=>$addressId,'userid'=>$this->curUserInfo['userid']])
                ->update($saveData);

            $this->returndata(10000, 'edit success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    删除用户地址接口
     * @url     /user/addressDel
     * @method  POST
     * @version 1000
     * @params  addressid 0 INT 地址id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function addressDel(){

        //返回结果
        $data = [];

        //获取接口参数
        $addressId = input('request.addressid',0);

        //验证参数是否为空
        if($addressId<=0){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $saveData['delflag']=1;
            $saveData['updatetime'] = $this->curTime;
            model('address')->where(['addressid'=>$addressId,'userid'=>$this->curUserInfo['userid']])
                ->update($saveData);
            $this->returndata(10000, 'del success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    用户搜索接口
     * @url     /user/userSearch
     * @method  GET
     * @version 1000
     * @params  type 1 INT 搜索对象类型1所有用户2关注用户3所有有身份的用户 YES
     * @params  nickname '' STRING 昵称 NO
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function userSearch(){
        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数
        $type = input('request.type',1,'intval');
        $nickname = input('request.nickname','');

        $page = input('request.page',1,'intval');

        //验证参数是否为空
        if(!in_array($type,[1,2,3]) ||$page<1 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            if($nickname == ''){
                $userInfoWhere = ['delflag'=>0];
            }
            else{
                $userInfoWhere = ['nickname'=>['like','%'.$nickname.'%'],'delflag'=>0];
            }

            $limit = ($page-1)*$pageSize.','.$pageSize;

            $group = 'jz_userinfo.userid';
            switch ($type){
                case 1:

                    //创建搜索日志 搜索类型  2用户  3服务 4活动  5专辑 6 动态
                    model('searchlog')->addSearchLog(2,$nickname);

                    $order = '';
                    $userInfoList = model('userinfo')->joinUserServiceTypeByWhere($userInfoWhere,$field='*',$order,$group,$limit,$jointype='LEFT');
                    break;
                case 2:
                    $followList = model('follow')->where(['userid'=>$this->curUserInfo['userid']])->select();
                    $followUserIds = [];
                    foreach($followList as $oneFollow){
                        $followUserIds[]=$oneFollow['followedid'];
                    }
                    if(!$followUserIds){
                        $followUserIds = '';
                    }
                    $userInfoWhere['jz_userinfo.userid']=['in',$followUserIds];
                    $order = '';
                    $userInfoList = model('userinfo')->joinUserServiceTypeByWhere($userInfoWhere,$field='*',$order,$group,$limit,$jointype='LEFT');
                    break;
                case 3:
                    $userInfoWhere['is_default'] = 1;
                    $order='';
                    $userInfoList = model('userinfo')->joinUserServiceTypeByWhere($userInfoWhere,$field='*',$order,$group,$limit,$jointype='INNER');

                    break;
            }



            if(!$userInfoList){
                $this->returndata( 10000, 'list is empty', $this->curTime, $data);
            }


            $this->getAllServiceType();

            $this->getAllControl();
            $userList = [];
            foreach($userInfoList as $oneUserInfo){
                $userList[] = [
                    'userid'=>$oneUserInfo['userid'],
                    'avatar'=>$this->checkPictureUrl($this->allControl['avatar_url'],$oneUserInfo['avatar']),
                    'nickname'=>$oneUserInfo['nickname'],
                    'brief'=>$oneUserInfo['brief'],
                    'tasktypename'=>$oneUserInfo['servicetypeid']>0?
                        $this->allServiceType[$oneUserInfo['servicetypeid']]:'',
                    'exp'=>$oneUserInfo['exp']
                ];
            }

            $data['userList'] = $userList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    用户列表接口
     * @url     /user/userList
     * @method  GET
     * @version 1000
     * @params  type 1 INT 用户列表类型1关注列表2关注有身份列表3粉丝列表 YES
     * @params  userid 0 INT 用户Id，0表示当前用户 YES
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function userList(){
        //返回结果
        $data = [];
        $pageSize = config('page_size');
        //获取接口参数
        $type = input('request.type',1,'intval');
        $userId = input('request.userid',0,'intval');

        $page = input('request.page',1,'intval');

        //验证参数是否为空
        if(!in_array($type,[1,2,3]) ||$userId<0 || $page<1 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $curUserId = $this->curUserInfo['userid'];
            if($userId == 0){
                $userId = $curUserId;
            }

            $limit = ($page-1)*$pageSize.','.$pageSize;

            switch ($type){
                case 1:
                    $followWhere=['jz_follow.userid'=>$userId];
                    $order = '';
                    $group = 'jz_follow.followedid';
                    $userList = model('follow')->joinUserServiceTypeByWhere("jz_userservicetype.userid = jz_follow.followedid",
                        $followWhere,$field='*',$order,$group,$limit,$jointype='LEFT');
                    $userIds = [];
                    $newUserList = [];
                    foreach($userList as $oneUser){
                        $userIds[]=$oneUser['followedid'];
                        $newUserList[$oneUser['followedid']]=$oneUser;
                    }
                    break;
                case 2:

                    $followWhere=['jz_follow.userid'=>$userId];
                    $userInfoWhere['is_default'] = 1;
                    $order = '';
                    $group = 'jz_follow.followedid';
                    $userList = model('follow')->joinUserServiceTypeByWhere("jz_userservicetype.userid = jz_follow.followedid",
                        $followWhere,$field='*',$order,$group,$limit,$jointype='INNER');
                    $userIds = [];
                    $newUserList = [];
                    foreach($userList as $oneUser){
                        $userIds[]=$oneUser['followedid'];
                        $newUserList[$oneUser['followedid']]=$oneUser;
                    }
                    break;
                case 3:
                    $followWhere=['followedid'=>$userId];
                    $order = '';
                    $group = 'jz_follow.userid';
                    $userList = model('follow')->joinUserServiceTypeByWhere("jz_userservicetype.userid = jz_follow.userid",
                        $followWhere,$field='*',$order,$group,$limit,$jointype='LEFT');
                    $userIds = [];
                    $newUserList = [];
                    foreach($userList as $oneUser){
                        $userIds[]=$oneUser['userid'];
                        $newUserList[$oneUser['userid']]=$oneUser;
                    }


                    break;
            }

            if(!$userList){
                $this->returndata( 10000, 'list is empty', $this->curTime, $data);
            }

            if(!$userIds){
                $userIds='';
            }
            $userInfoList = model('userinfo')->where(['userid'=>['in',$userIds]])->select();

            $followList = model('follow')->where(['userid'=>$curUserId,'followedid'=>['in',$userIds]])->select();
            $followUserIds = [];
            foreach($followList as $oneFollow){
                $followUserIds[]=$oneFollow['followedid'];
            }

            //var_dump($userIds,model('follow')->getLastSql());exit;
            $this->getAllServiceType();
            $this->getAllControl();
            $userList = [];
            foreach($userInfoList as $oneUserInfo){
                $userList[] = [
                    'userid'=>$oneUserInfo['userid'],
                    'avatar'=>$this->checkPictureUrl($this->allControl['avatar_url'],$oneUserInfo['avatar']),
                    'nickname'=>$oneUserInfo['nickname'],
                    'tasktypename'=>$newUserList[$oneUserInfo['userid']]['servicetypeid']>0?
                        $this->allServiceType[$newUserList[$oneUserInfo['userid']]['servicetypeid']]:'',
                    'exp'=>$newUserList[$oneUserInfo['userid']]['exp'],
                    'isfollow'=>$oneUserInfo['userid']==$curUserId?2:(in_array($oneUserInfo['userid'],$followUserIds)?1:0)    //2是自己1已关注0未关注
                ];
            }

            $data['userList'] = $userList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    关注/取消关注接口
     * @url     /user/userFavour
     * @method  POST
     * @version 1000
     * @params  followid 0 INT 要关注/取消关注的用户id YES
     * @params  type 1 INT 关注的类型1关注2取消关注 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function userFavour(){
        //返回结果
        $data = [];

        //获取接口参数
        $followId = input('request.followid',0);
        $type = input('request.type',1);

        //验证参数是否为空
        if($followId<=0 || !in_array($type,[1,2])){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $follow = model('follow')->where(['userid'=>$this->curUserInfo['userid'],'followedid'=>$followId])
                ->find();

            if($type==1){
                if($follow){
                    $this->returndata( 14001,  '已经关注', $this->curTime, $data);
                }
                else{
                    $followData = [
                        'userid'=>$this->curUserInfo['userid'],
                        'followedid'=>$followId,
                        'createtime'=>$this->curTime
                    ];
                    model('follow')->insertGetId($followData);
                }
            }
            elseif($type == 2){
                if($follow){
                    $followWhere = [
                        'userid'=>$this->curUserInfo['userid'],
                        'followedid'=>$followId
                    ];
                    model('follow')->where($followWhere)->delete();
                }
                else{
                    $this->returndata( 14001,  '未关注', $this->curTime, $data);

                }
            }

            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }


}
