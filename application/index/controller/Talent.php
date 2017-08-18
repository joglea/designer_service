<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class User
 *
 * @classdesc 达人接口类
 * @package app\index\controller
 */
class Talent extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    所有达人接口
     * @url     /talent/allTalentList
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function allTalentList(){

        //返回结果
        $data = [];

        //单个达人列表的人数 默认为3人
        $oneListNum = 3;

        try{

            $serviceTypeList = model('servicetype')->where(['delflag'=>0])->order('sort desc')->select();

            if(!$serviceTypeList){
                $this->returndata( 14001,  'servicetype error', $this->curTime, $data);
            }
            $newServiceTypeList = [];
            $allServiceTypeIds = [];
            foreach($serviceTypeList as $oneServiceType){
                $newServiceTypeList[$oneServiceType['servicetypeid']]=$oneServiceType;
                $allServiceTypeIds[]=$oneServiceType['servicetypeid'];
            }

            //是否根据用户自身身份优先显示对应的达人列表
            $isAuto = true;
            if($isAuto && isset($this->curUserInfo['userid'])){
                $userId = $this->curUserInfo['userid'];
                $userServiceTypeList = model('userservicetype')
                    ->where(['userid'=>$userId,'delflag'=>0])
                    ->order('exp desc')->select();
                $userServiceTypeIds = [];
                foreach($userServiceTypeList as $oneUserServiceType){
                    $userServiceTypeIds[]=$oneUserServiceType['servicetypeid'];
                }
                $notUserServiceTypeIds = [];
                foreach($allServiceTypeIds as $oneServiceTypeId){
                    if(!in_array($oneServiceTypeId,$userServiceTypeIds)){
                        $notUserServiceTypeIds[]=$oneServiceTypeId;
                    }
                }
                $finalServiceTypeIds = array_merge($userServiceTypeIds,$notUserServiceTypeIds);
                //var_dump($userServiceTypeIds,$notUserServiceTypeIds);
            }
            else{
                $finalServiceTypeIds = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23];
            }



            $databaseConfig = config('database');
            $this->getAllControl();
            $this->getAllServiceType();
            foreach($finalServiceTypeIds as $oneServiceTypeId){

                //人气之星根据被打赏数排
                if($oneServiceTypeId == 1){
                    $querySql = 'select * from '.$databaseConfig['prefix'].'userdata as a ,'
                        .$databaseConfig['prefix'].'userservicetype as b where a.userid = b.userid and b.servicetypeid = '.
                        $oneServiceTypeId.' order by a.be_supported_counter desc limit '.$oneListNum;
                    $userList = model('userdata')->query($querySql);
                }
                else{
                    $querySql = 'select * from '.$databaseConfig['prefix'].'userdata as a ,'
                        .$databaseConfig['prefix'].'userservicetype as b where a.userid = b.userid and b.servicetypeid = '.
                        $oneServiceTypeId.' order by a.service_counter desc limit '.$oneListNum;
                    $userList = model('userdata')->query($querySql);
                }
                $userIds = [];
                foreach($userList as $oneUser){
                    $userIds[]=$oneUser['userid'];
                }
                $newUserInfoList = [];
                $newUserFollowList = [];
                if($userIds){
                    $userInfoList = model('userinfo')->where(['userid'=>['in',$userIds]])->select();
                    foreach($userInfoList as $oneUserInfo){
                        $newUserInfoList[$oneUserInfo['userid']]=$oneUserInfo;
                    }
                    $followList = model('follow')->where(['userid'=>['in',$userIds]])->select();
                    foreach($followList as $oneFollow){
                        $newUserFollowList[$oneFollow['followedid']]=$oneFollow;
                    }
                }

                $talentlist=[];
                foreach($userList as $oneUser){
                    $talentlist[]=[
                        'userid'=>$oneUser['userid'],
                        'avatar'=>$this->checkPictureUrl($this->allControl['avatar_url'],$newUserInfoList[$oneUser['userid']]['avatar']),
                        'nickname'=>$newUserInfoList[$oneUser['userid']]['nickname'],
                        'userservicetypename'=>$newServiceTypeList[$oneUser['servicetypeid']]['name'],
                        'exp'=>$oneUser['exp'],
                        'isfollow'=>isset($this->curUserInfo['userid'])?
                            (isset($newUserFollowList[$oneUser['userid']])?1:
                            ($oneUser['userid']==$this->curUserInfo['userid']?2:0)):0   //2是自己1已关注0未关注
                    ];
                }

                if($this->jzVersionType==1){
                    $serviceTypeBgColor=$newServiceTypeList[$oneServiceTypeId]['bg_color_android'];
                }
                elseif($this->jzVersionType==2){
                    $serviceTypeBgColor=$newServiceTypeList[$oneServiceTypeId]['bg_color_ios'];
                }
                else{
                    $serviceTypeBgColor='';
                }

                $data[]=[
                    'servicetypeid'=>$oneServiceTypeId,
                    'servicetypename'=>$newServiceTypeList[$oneServiceTypeId]['name'],
                    'title_servicetypeimage'=>$this->checkPictureUrl($this->allControl['servicetype_image_url'],
                        'servicetype/title_servicetype'.str_pad($oneServiceTypeId,2,'0',STR_PAD_LEFT).'.png'),
                    'bg_color'=>$serviceTypeBgColor,
                    'userlist'=>$talentlist
                ];

            }


            $this->returndata(10000, 'save success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    所有达人接口
     * @url     /talent/talentList
     * @method  GET
     * @version 1000
     * @params  servicetypeid 1 INT 服务类型id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function talentList(){

        //返回结果
        $data = [];
        //单个详细达人列表的人数 默认为50人
        $oneListNum = 50;

        //获取接口参数
        $serviceTypeId = input('request.servicetypeid',0,'intval');
        //$page = input('request.page',1,'intval');
        //验证参数是否为空
        if($serviceTypeId<=0){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $serviceType = model('servicetype')->where(['servicetypeid'=>$serviceTypeId,'delflag'=>0])->find();

            if(!$serviceType){
                $this->returndata( 14002,  'servicetype error', $this->curTime, $data);
            }

            $databaseConfig = config('database');
            $this->getAllControl();
            $this->getAllServiceType();

            //人气之星根据被打赏数排
            if($serviceTypeId == 1){
                $querySql = 'select * from '.$databaseConfig['prefix'].'userdata as a ,'
                    .$databaseConfig['prefix'].'userservicetype as b where a.userid = b.userid and b.servicetypeid = '.
                    $serviceTypeId.' order by a.be_supported_counter desc limit '.$oneListNum;
                $userList = model('userdata')->query($querySql);
            }
            else{
                $querySql = 'select * from '.$databaseConfig['prefix'].'userdata as a ,'
                    .$databaseConfig['prefix'].'userservicetype as b where a.userid = b.userid and b.servicetypeid = '.
                    $serviceTypeId.' order by a.service_counter desc limit '.$oneListNum;
                $userList = model('userdata')->query($querySql);
            }
            $userIds = [];
            foreach($userList as $oneUser){
                $userIds[]=$oneUser['userid'];
            }
            $newUserInfoList = [];
            $newUserFollowList = [];
            if($userIds){
                $userInfoList = model('userinfo')->where(['userid'=>['in',$userIds]])->select();
                foreach($userInfoList as $oneUserInfo){
                    $newUserInfoList[$oneUserInfo['userid']]=$oneUserInfo;
                }
                $followList = model('follow')->where(['userid'=>['in',$userIds]])->select();
                foreach($followList as $oneFollow){
                    $newUserFollowList[$oneFollow['followedid']]=$oneFollow;
                }
            }

            $talentlist = [];
            foreach($userList as $oneUser){
                $talentlist[]=[
                    'userid'            =>$oneUser['userid'],
                    'avatar'            =>$this->checkPictureUrl($this->allControl['avatar_url'],$newUserInfoList[$oneUser['userid']]['avatar']),
                    'nickname'          =>$newUserInfoList[$oneUser['userid']]['nickname'],
                    'userservicetypename'      =>$this->allServiceType[$oneUser['servicetypeid']],
                    'exp'               =>$oneUser['exp'],
                    "verify_state"      => $newUserInfoList[$oneUser['userid']]['verify_state'],
                    "zhima_code"        => model('userinfo')->getZhimaState($newUserInfoList[$oneUser['userid']]['zhima_code']),
                    'fans_counter'      => $oneUser['fans_counter'],
                    'service_counter'   =>$oneUser['service_counter'],
                    'isfollow'          =>isset($this->curUserInfo['userid'])?(isset($newUserFollowList[$oneUser['userid']])?1:
                        ($oneUser['userid']==$this->curUserInfo['userid']?2:0)):0   //2是自己1已关注0未关注
                ];
            }

            if($this->jzVersionType==1){
                $serviceTypeBgColor=$serviceType['bg_color_android'];
            }
            elseif($this->jzVersionType==2){
                $serviceTypeBgColor=$serviceType['bg_color_ios'];
            }
            else{
                $serviceTypeBgColor='';
            }

            $data=[
                'servicetypeid'=>$serviceTypeId,
                'servicetypename'=>$serviceType['name'],
                'title_servicetypeimage'=>$this->checkPictureUrl($this->allControl['servicetype_image_url'],
                    'servicetype/title_servicetype'.str_pad($serviceTypeId,2,'0',STR_PAD_LEFT).'.png'),
                'detailtitle_servicetypeimage'=>$this->checkPictureUrl($this->allControl['servicetype_image_url'],
                    'servicetype/detailtitle_servicetype'.str_pad($serviceTypeId,2,'0',STR_PAD_LEFT).'.png'),
                'detailicon_servicetypeimage'=>$this->checkPictureUrl($this->allControl['servicetype_image_url'],
                    'servicetype/detailicon_servicetype'.str_pad($serviceTypeId,2,'0',STR_PAD_LEFT).'.png'),
                'grey_servicetypeimage'=>$this->checkPictureUrl($this->allControl['servicetype_image_url'],
                    'servicetype/grey_servicetype'.str_pad($serviceTypeId,2,'0',STR_PAD_LEFT).'.png'),
                'bg_color'=>$serviceTypeBgColor,
                'userlist'=>$talentlist
            ];


            $this->returndata(10000, 'save success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }


}
