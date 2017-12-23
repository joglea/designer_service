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
     * @params  avatar '' STRING 头像 NO
     * @params  nickname '' STRING 昵称 NO
     * @params  tel '' STRING 手机号 NO
     * @params  email '' STRING 邮箱 NO
     * @params  sex '1' STRING 0未知性别1男2女 NO
     * @params  birthday '1990-03-01' STRING 生日 NO
     * @params  cityid '0' STRING 城市id NO
     * @params  personlink '90,90,90' STRING link NO
     * @params  brief '0' STRING 简介 NO
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function userSave(){

        //返回结果
        $data = [];

        //获取接口参数
        $avatar = input('request.avatar');
        $nickName = input('request.nickname');
        $tel = input('request.tel');
        $email = input('request.email');
        $sex = input('request.sex');
        $birthday = input('request.birthday');
        $cityid = input('request.cityid');
        $personlink = input('request.personlink');
        $brief = input('request.brief');


        //验证用户名是否为空


        try{

            $userinfo = [];
            if($avatar!=''){
                $allControls = $this->getAllControl();
                $userinfo['avatar'] = $this->checkPictureUrl($allControls['avatar_url'],$avatar);
                $this->curUserInfo['avatar']= $userinfo['avatar'];
            }
            if($nickName!=''){
                $isExist=model('userinfo')
                    ->where(['nickname'=>$nickName,
                             'userid'=>['<>',$this->curUserInfo['userid']]])->find();
                if(!$isExist){
                    $userinfo['nickname'] = $nickName;
                    $this->curUserInfo['nickname']= $userinfo['nickname'];
                }
                else{
                    $this->returndata( 14001,  'nickname exist ', $this->curTime, $data);
                }
            }

            if($tel!=''){
                $userinfo['tel'] = $tel;
                $this->curUserInfo['tel']= $userinfo['tel'];
            }
            if($email!=''){
                $userinfo['email'] = $email;
                $this->curUserInfo['email']= $userinfo['email'];
            }

            if(in_array($sex,[1,2])){
                $userinfo['sex'] = $sex;
                $this->curUserInfo['sex']= $userinfo['sex'];
            }
            if($birthday!=''){
                $userinfo['birthday'] = $birthday;
                $this->curUserInfo['birthday']= $userinfo['birthday'];
            }
            if($cityid>=0&&$cityid!==''){

                $city = model('city')->where(['id'=>$cityid])->find();
                if($city){
                    $userinfo['cityid'] = $cityid;
                    $this->curUserInfo['city']= $city;
                }
            }
            if($personlink!=''){
                $userinfo['personlink'] = $personlink;
                $this->curUserInfo['personlink']= $userinfo['personlink'];
            }
            if($brief!=''){
                $userinfo['brief'] = mb_substr($brief,0,500,'utf-8');
                $this->curUserInfo['brief']= $userinfo['brief'];
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
                    "country_code"      => $this->curUserInfo['country_code'],
                    "tel"               => $this->curUserInfo['tel'],
                    'jointime'          => $this->curUserInfo['jointime'],
                    "token"             => $this->curUserInfo['token'],
                    "nickname"          => $this->curUserInfo['nickname'],
                    "avatar"            => $this->curUserInfo['avatar'],
                    //""               => $userInfo['sex'],
                    "sex"               => $this->curUserInfo['sex'],
                    "birthday"          => $this->curUserInfo['birthday'],
                    "city"              => $this->curUserInfo['city'],
                    "personlink"      => $this->curUserInfo['personlink'],
                    "brief"      => $this->curUserInfo['brief'],
                    "verify_state"      => $this->curUserInfo['verify_state'],
                    "verifyid"          => $this->curUserInfo['verifyid'],
                    "status"          => $this->curUserInfo['status'],        //环信密码
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
                $verifyCompany = model('verifycompany')->where(['userid'=>$userBase['userid'],'delflag'=>0])->find();
                $verifyDesigner = model('verifydesigner')->where(['userid'=>$userBase['userid'],'delflag'=>0])->find();

                $city = model('city')->where(['cityid'=>$userInfo['cityid']])->find();

                $userData = model('userdata')->where(['userid'=>$userBase['userid']])->find();
                if(!$userData){
                    $this->returndata( 14004, 'account not exist3', $this->curTime, []);
                }

                $allControl = $this->getAllControl();

                $data['user'] = array(

                    "userid"            => $userBase['userid'],
                    "country_code"      => $userBase['country_code'],
                    'jointime'          => $userBase['createtime'],
                    "nickname"          => $userInfo['nickname'],
                    "avatar"            => $this->checkpictureurl($allControl['avatar_url'],$userInfo['avatar']),
                    //""               => $userInfo['sex'],
                    "sex"               => $userInfo['sex'],
                    "birthday"          => $userInfo['birthday'],
                    "city"              => $city,
                    "personlink"      => $userInfo['personlink'],
                    "brief"      => $userInfo['brief'],
                    "verify_state"      => $userInfo['verify_state'],
                    "verifyid"          => $userInfo['verifyid'],
                    "status"          => $userInfo['status'],

                );
            }


            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    公司认证接口
     * @url     /user/verifyCompany
     * @method  POST
     * @version 1000
     * @params  companyname '' STRING 头像 NO
     * @params  businesslicense '' STRING 昵称 NO
     * @params  license_pic '' STRING 手机号 NO
     * @params  truename '' STRING 邮箱 NO
     * @params  idcard '1' STRING 0未知性别1男2女 NO
     * @params  idcard_pic '1990-03-01' STRING 生日 NO
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function verifyCompany(){

        //返回结果
        $data = [];

        //获取接口参数
        $companyname = input('request.companyname','');
        $businesslicense = input('request.businesslicense','');
        $license_pic = input('request.license_pic','');
        $truename = input('request.truename','');
        $idcard = input('request.idcard','');
        $idcard_pic = input('request.idcard_pic','');

        //验证参数是否为空
        if($companyname==''||$businesslicense==''||$license_pic==''||
            $truename==''||$idcard==''||$idcard_pic==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $userinfo = model('userinfo')->where(['userid'=>$this->curUserInfo['userid']])
                ->find();
            if($userinfo['verify_state']!=0){
                $this->returndata( 14001,  'alread verify ', $this->curTime, $data);
            }
            $verifyCompany = model('verifycompany')->where(
                ['userid'=>$this->curUserInfo['userid'],'delflag'=>0
                ]
            )->find();
            if($verifyCompany){
                $this->returndata( 14001,  'alread exist verify ', $this->curTime, $data);
            }
            $newVerifyCompany = [

                'userid'=>$this->curUserInfo['userid'],
                'companyname'=>$companyname,
                'businesslicense'=>$businesslicense,
                'license_pic'=>$license_pic,
                'idcard'=>$idcard,
                'idcard_pic'=>$idcard_pic,
                'state'=>1,
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0,
            ];
            $companyid = model('verifycompany')->insertGetId($newVerifyCompany);

            if(!$companyid){
                $this->returndata( 14002, 'verify add fail', $this->curTime, $data);
            }

            $data['companyid']=$companyid;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }

    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    公司认证接口
     * @url     /user/verifyDesigner
     * @method  POST
     * @version 1000
     * @params  truename '' STRING 邮箱 NO
     * @params  idcard '1' STRING 0未知性别1男2女 NO
     * @params  idcard_pic '1990-03-01' STRING 生日 NO
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function verifyDesigner(){

        //返回结果
        $data = [];

        //获取接口参数

        $truename = input('request.truename','');
        $idcard = input('request.idcard','');
        $idcard_pic = input('request.idcard_pic','');

        //验证参数是否为空
        if($truename==''||$idcard==''||$idcard_pic==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $userinfo = model('userinfo')->where(['userid'=>$this->curUserInfo['userid']])
                ->find();
            if($userinfo['verify_state']!=0){
                $this->returndata( 14001,  'alread verify ', $this->curTime, $data);
            }

            $verifyDesigner = model('verifydesigner')->where(
                ['userid'=>$this->curUserInfo['userid'],'delflag'=>0
                ]
            )->find();
            if($verifyDesigner){
                $this->returndata( 14001,  'alread exist verify ', $this->curTime, $data);
            }
            $newVerifyDesigner = [
                'userid'=>$this->curUserInfo['userid'],
                'truename'=>$truename,
                'idcard'=>$idcard,
                'idcard_pic'=>$idcard_pic,
                'state'=>1,
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0,
            ];
            $designerid = model('verifydesigner')->insertGetId($newVerifyDesigner);

            if(!$designerid){
                $this->returndata( 14002, 'verify add fail', $this->curTime, $data);
            }

            $data['designerid']=$designerid;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }

    }




    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    用户简历接口
     * @url     /User/ResumeList
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
    "code":10000,
    "message":"获取成功",
    "time":1492413087,
    "data":{
    "WorkexpList":[
    {
    "expid":3,
    "begindate":"2016-07-08",
    "enddate":"2016-12-31",
    "companyname":"zzz",
    "desc":"zzz"
    }
    ],
    "EducationexpList":[
    {
    "expid":1,
    "begindate":"2017-01-01",
    "enddate":"2017-07-07",
    "schoolname":"xxx",
    "desc":"xxx"
    }
    ],
    "DesignworksList":[
    {
    "designworksid":0,
    "title":"ssss",
    "pic":"http://www.ds.com/statics/Image/designworks/a.jpg",
    "link":"http://www.baidu.com"
    }
    ]
    }
    }
     *
     */
    public function ResumeList(){
        //返回结果
        $data = [];
        //验证参数是否为空

        try{
            $userId = $this->curUserInfo['userid'];
            $this->getAllControl();
            $WorkexpWhere = ['userid'=>$userId,'delflag'=>0];
            $order = 'begindate desc,expid desc';

            $WorkexpList = model('Workexp')->where($WorkexpWhere)->order($order)
                ->select();

            $newWorkexpList = [];
            if($WorkexpList){
                foreach($WorkexpList as $oneWorkexp){
                    $newWorkexpList[]=[
                        'expid'=>$oneWorkexp['expid'],
                        'begindate'=>$oneWorkexp['begindate'],
                        'enddate'=>$oneWorkexp['enddate'],
                        'companyname'=>$oneWorkexp['companyname'],
                        'desc'=>$oneWorkexp['desc'],
                    ];
                }
            }

            $EducationexpWhere = ['userid'=>$userId,'delflag'=>0];
            $order = 'begindate desc,expid desc';

            $EducationexpList = model('Educationexp')->where($EducationexpWhere)->order($order)
                ->select();
            //var_dump($EducationexpList);exit;

            $newEducationexpList = [];
            if($EducationexpList){
                foreach($EducationexpList as $oneEducationexp){
                    $newEducationexpList[]=[
                        'expid'=>$oneEducationexp['expid'],
                        'begindate'=>$oneEducationexp['begindate'],
                        'enddate'=>$oneEducationexp['enddate'],
                        'schoolname'=>$oneEducationexp['schoolname'],
                        'desc'=>$oneEducationexp['desc'],
                    ];
                }
            }


            $DesignworksWhere = ['userid'=>$userId,'delflag'=>0];
            $order = 'designworksid desc';

            $DesignworksList = model('Designworks')->where($DesignworksWhere)->order($order)
                ->select();


            $newDesignworksList = [];
            if($DesignworksList){

                foreach($DesignworksList as $oneDesignworks){
                    $piclist = json_decode($oneDesignworks['pic'],true);

                    $new_pic_list = [];
                    foreach($piclist as $onepic){
                        $new_pic_list[]=$this->checkPictureUrl($this->allControl['design_works_pic_url'],$onepic);
                    }
                    $newDesignworksList[]=[
                        'designworksid'=>$oneDesignworks['designworksid'],
                        'title'=>$oneDesignworks['title'],
                        'pic'=>$new_pic_list,
                        'link'=>$oneDesignworks['link']
                    ];
                }
            }


            $data['WorkexpList'] = $newWorkexpList;
            $data['EducationexpList'] = $newEducationexpList;
            $data['DesignworksList'] = $newDesignworksList;

            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }

}
