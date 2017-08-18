<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Service
 *
 * @classdesc 服务接口类
 * @package app\index\controller
 */
class Service extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    服务列表接口
     * @url     /service/serviceList
     * @method  GET
     * @version 1000
     * @params  type 1 INT 服务列表类型1最新2最热3距离最近4推荐(未关注的服务类型根据热门排)5固定一个服务类型6搜索 YES
     * @params  title '' STRING 服务标题(type为6的时候才有用) NO
     * @params  servicetypeid 0 INT 服务身份类型id(type为5的时候才有用) YES
     * @params  servicesubtypeid 0 INT 子类服务类型id,为0表示全部子类 NO
     * @params  sorttype 1 INT 智能排序1销量2评分 YES
     * @params  sex 0 INT 性别筛选,提供服务人的性别0无所谓1男2女 NO
     * @params  minprice 0 INT 价格筛选,服务的最小价格 NO
     * @params  maxprice 0 INT 价格筛选,服务的最大价格 NO
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  service_lon '' STRING 提供服务的经度 YES
     * @params  service_lat '' STRING 提供服务的纬度 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function serviceList(){


        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数
        $type = input('request.type',1,'intval');
        $title = input('request.title','');

        $serviceTypeId = input('request.servicetypeid',0,'intval');
        $serviceSubTypeId = input('request.servicesubtypeid',0,'intval');
        $sorttype = input('request.sorttype',1,'intval');
        if(!$sorttype){
            $sorttype=1;
        }

        $sex = input('request.sex',0,'intval');
        $minPrice = input('request.minprice',0,'intval');
        $maxPrice = input('request.maxprice',0,'intval');

        $page = input('request.page',1,'intval');
        $serviceLon = input('request.service_lon',0);
        $serviceLat = input('request.service_lat',0);

        //验证参数是否为空
        if(!in_array($type,[1,2,3,4,5,6]) ||!in_array($sorttype,[1,2]) ||$page<1||$serviceLat<-90
            ||$serviceLat>90||$serviceLon<-180||$serviceLon>180){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        if($type == 5 && ($serviceTypeId<=0||$serviceSubTypeId<0||$sex<0||$minPrice<0||$maxPrice<0)){
            $this->returndata( 14001,  'params error1', $this->curTime, $data);
        }

        try{

            $serviceDataWhere = ['delflag'=>0];
            if($type!=5 && $type!=6){

                $allServiceSubTypeList = model('servicesubtype')->where([])->select();
                $myServiceTypeIds = [];
                if($this->curUserInfo){
                    $myServiceTypeList =$this->curUserInfo['identitys'];
                    foreach($myServiceTypeList as $oneMyServiceType){
                        $myServiceTypeIds[] = $oneMyServiceType['servicetypeid'];
                    }
                }

                $myServiceSubTypeIds = [];
                $notMyServiceSubTypeIds = [];

                foreach($allServiceSubTypeList as $oneServiceSubType){
                    if(in_array($oneServiceSubType['servicetypeid'],$myServiceTypeIds)){
                        $myServiceSubTypeIds[] = $oneServiceSubType['servicesubtypeid'];
                    }
                    else{
                        $notMyServiceSubTypeIds[] = $oneServiceSubType['servicesubtypeid'];
                    }
                }
                if(!$myServiceSubTypeIds){
                    $myServiceSubTypeIds = '';
                }
                if(!$notMyServiceSubTypeIds){
                    $notMyServiceSubTypeIds = '';
                }
                if($this->curUserInfo){
                    $serviceDataWhere['servicesubtypeid'] = ['in',$myServiceSubTypeIds];
                }
            }
            switch ($type){
                case 1:

                    $serviceDataList = model('servicedata')->where($serviceDataWhere)->order('serviceid desc ')
                        ->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
                case 2:
                    $serviceDataList = model('servicedata')->where($serviceDataWhere)->order('is_hot desc,serviceid desc ')
                        ->limit((($page-1)*$pageSize).','.$pageSize)->select();

                    break;
                case 3:
                    $indistance=config('distance_limit');//距离范围  单位 m
                    $minmaxarr=getRectByPoint($serviceLat,$serviceLon,$indistance);

                    $serviceDataWhere['service_lat']=array('between',array($minmaxarr[0],$minmaxarr[1]));
                    //如果最小经度低于了-180
                    if($minmaxarr[2]<-180){
                        $serviceDataWhere['service_lon']=array(array('between',array($minmaxarr[2]+360,180)),
                            array('between',array(-180,$minmaxarr[3])),'or');
                    }
                    else if($minmaxarr[3]>180){//如果最大经度超过了180
                        $serviceDataWhere['service_lon']=array(array('between',array($minmaxarr[2],180)),
                            array('between',array(-180,$minmaxarr[3]-360)),'or');
                    }
                    else{
                        $serviceDataWhere['service_lon']=array('between',array($minmaxarr[2],$minmaxarr[3]));
                    }

                    $serviceDataList = model('servicedata')->where($serviceDataWhere)
                        ->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    //var_dump(model('servicedata')->getLastSql());exit;
                    break;
                case 4:
                    $serviceDataList = [];
                    break;
                case 5:

                    $serviceSubTypeList = model('servicesubtype')->where(['servicetypeid'=>$serviceTypeId])->select();

                    $serviceSubTypeIds = [];
                    foreach($serviceSubTypeList as $oneServiceSubType){
                        $serviceSubTypeIds[]=$oneServiceSubType['servicesubtypeid'];
                    }
                    if(!$serviceSubTypeIds){
                        $serviceSubTypeIds = '';
                    }
                    if(!in_array($serviceSubTypeId,$serviceSubTypeIds)){
                        $serviceDataWhere['servicesubtypeid'] = ['in',$serviceSubTypeIds];
                    }
                    else{
                        $serviceDataWhere['servicesubtypeid'] = $serviceSubTypeId;
                    }
                    if($sex!=0){
                        $serviceDataWhere['user_sex'] = $sex;
                    }

                    if($minPrice>0){
                        $serviceDataWhere['common_price'] = ['gt',$minPrice];
                    }
                    if($maxPrice>0){
                        $serviceDataWhere['common_price'] = ['lt',$maxPrice];
                    }
                    
                    if($sorttype == 1){
                        $order = 'service_num desc';
                    }
                    elseif($sorttype == 2){
                        $order = 'star_score desc';
                    }
                    elseif($sorttype == 3){
                        $order = '';
                    }
                    else{
                        $order = 'createtime desc ';
                    }
                    $serviceDataList = model('servicedata')->where($serviceDataWhere)->order($order)
                        ->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
                case 6:

                    //创建搜索日志  搜索类型  2用户  3服务 4活动  5专辑 6 动态
                    model('searchlog')->addSearchLog(3,$title);

                    $serviceMainWhere=[
                        'state'=>1,
                        'delflag'=>0
                    ];
                    if($title != ''){
                        $serviceMainWhere['title']=['like','%'.$title.'%'];
                    }
                    $order = 'createtime desc ';
                    $serviceMainList = model('servicemain')->where($serviceMainWhere)->order($order)
                        ->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    //var_dump(model('servicemain')->getLastSql(),$serviceMainList);exit;
                    break;

            }

            if($type!=6){

                $serviceIds = [];
                foreach($serviceDataList as $oneServiceData){
                    $serviceIds[] = $oneServiceData['serviceid'];
                }
                $serviceMainList = [];
                if($serviceIds){
                    $serviceMainList = model('servicemain')
                        ->where(['serviceid'=>['in',$serviceIds],'state'=>1,'delflag'=>0])->order('createtime desc')->select();
                }

            }

            $serviceIds = [];
            $userIds = [];
            foreach($serviceMainList as $oneServiceMain){
                $serviceIds[]=$oneServiceMain['serviceid'];
                $userIds[] = $oneServiceMain['userid'];
            }


            if(!$serviceIds){
                $this->returndata( 10000, 'service list is empty', $this->curTime, $data);
            }

            if($type == 6){
                $serviceDataList = model('servicedata')->where(['serviceid'=>['in',$serviceIds],'delflag'=>0])->select();
            }

            $newServiceDataList = [];
            foreach($serviceDataList as $oneServiceData){
                $newServiceDataList[$oneServiceData['serviceid']] = [
                    'servicesubtypeid'=>$oneServiceData['servicesubtypeid'],
                    'common_price'=>$oneServiceData['common_price'],
                    'service_num'=>$oneServiceData['service_num'],
                    'star_score'=>$oneServiceData['star_score'],
                    'star_num'=>$oneServiceData['star_num'],
                    'service_lat'=>$oneServiceData['service_lat'],
                    'service_lon'=>$oneServiceData['service_lon'],
                ];
            }

            $this->getAllControl();
            $userInfoList = model('userinfo')->where(['userid'=>['in',$userIds]])->select();
            $newUserInfoList = [];
            foreach($userInfoList as $oneUserInfo){

                $zhimaState = model('userinfo')->getZhimaState($oneUserInfo['zhima_code']);

                $newUserInfoList[$oneUserInfo['userid']] = [
                    'avatar'=>$this->checkPictureUrl($this->allControl['avatar_url'],$oneUserInfo['avatar']),
                    'nickname'=>$oneUserInfo['nickname'],
                    'verify_state'=>$oneUserInfo['verify_state'],
                    'zhima_state'=>$zhimaState,
                ];
            }

            $userServiceTypeList = model('userservicetype')->where(['userid'=>['in',$userIds],'is_default'=>1,'delflag'=>0])->select();
            $newUserServicetypeList = [];

            $this->getAllServiceType();

            foreach($userServiceTypeList as $oneUserServiceType){

                if(!isset($newUserServicetypeList[$oneUserServiceType['userid']])){
                    $newUserServicetypeList[$oneUserServiceType['userid']]=[
                        'servicetypename'=>$this->allServiceType[$oneUserServiceType['servicetypeid']],
                        'exp'=>$oneUserServiceType['exp']
                    ];
                }
                elseif($newUserServicetypeList[$oneUserServiceType['userid']]['exp']<$oneUserServiceType['exp']){
                    $newUserServicetypeList[$oneUserServiceType['userid']]=[
                        'servicetypename'=>$this->allServiceType[$oneUserServiceType['servicetypeid']],
                        'exp'=>$oneUserServiceType['exp']
                    ];
                }
            }

            $newServicelist = [];
            foreach($serviceMainList as $oneServiceMain){
                //var_dump($oneServiceMain);
                $urls = json_decode($oneServiceMain['urls'],true);

                $distance = getDistance(isset($newServiceDataList[$oneServiceMain['serviceid']])?
                    $newServiceDataList[$oneServiceMain['serviceid']]['service_lat']:0,
                    isset($newServiceDataList[$oneServiceMain['serviceid']])?
                        $newServiceDataList[$oneServiceMain['serviceid']]['service_lon']:0,$serviceLat,$serviceLon,2,1).'km';

                $newServicelist[]=[
                    'serviceid'=>$oneServiceMain['serviceid'],
                    'servicesubtypeid'=>isset($newServiceDataList[$oneServiceMain['serviceid']])?
                        $newServiceDataList[$oneServiceMain['serviceid']]['servicesubtypeid']:0,
                    'userid'=>$oneServiceMain['userid'],
                    'avatar'=>$newUserInfoList[$oneServiceMain['userid']]['avatar'],
                    'nickname'=>$newUserInfoList[$oneServiceMain['userid']]['nickname'],
                    'verify_state'=>$newUserInfoList[$oneServiceMain['userid']]['verify_state'],
                    'zhima_state'=>$newUserInfoList[$oneServiceMain['userid']]['zhima_state'],
                    'title'=>$oneServiceMain['title'],
                    'common_price'=>isset($newServiceDataList[$oneServiceMain['serviceid']])?
                        $newServiceDataList[$oneServiceMain['serviceid']]['common_price']:0,

                    'urls'=>$urls?$this->checkPictureUrl($this->allControl['service_image_url'],$urls[0]):'',
                    //'content'=>$oneServiceMain['content'],
                    'service_num'=>isset($newServiceDataList[$oneServiceMain['serviceid']])?
                        $newServiceDataList[$oneServiceMain['serviceid']]['service_num']:0,
                    'star_score'=>isset($newServiceDataList[$oneServiceMain['serviceid']])?
                        $newServiceDataList[$oneServiceMain['serviceid']]['star_score']:0,
                    'servicetypename'=>isset($newUserServicetypeList[$oneServiceMain['userid']])?
                        $newUserServicetypeList[$oneServiceMain['userid']]['servicetypename']:'',
                    'exp'=>isset($newUserServicetypeList[$oneServiceMain['userid']])?
                        $newUserServicetypeList[$oneServiceMain['userid']]['exp']:0,
                    'distance'=>$distance,
                ];
            }

            $data['serviceList'] = $newServicelist;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建服务接口
     * @url     /service/serviceCreate
     * @method  POST
     * @version 1000
     * @params  servicesubtypeid 1 INT 服务类型id YES
     * @params  title '标题' STRING 服务标题 YES
     * @params  content '内容' STRING 服务内容描述 YES
     * @params  type 1 INT 任务媒体文件类型1图2文3音4视 YES
     * @params  urls '["a.jpg","b.jpg"]' STRING 图音视文件地址的json串 YES
     * @params  options '[{"optionname":"ddd","price":1,"stock":1}]' STRING 多项服务选项 YES
     * @params  service_lat '0' STRING 提供服务的经度 YES
     * @params  service_lon '0' STRING 提供服务的纬度 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function serviceCreate(){

        //返回结果
        $data = [];

        //获取接口参数
        $serviceSubTypeId = input('request.servicesubtypeid',0);
        $title = input('request.title','');
        $content = input('request.content','');
        $type = input('request.type',1);
        $urls = input('request.urls','');

        $options = input('request.options');

        $serviceLat = input('request.service_lat',0);
        $serviceLon = input('request.service_lon',0);

        //验证参数是否为空
        if($serviceSubTypeId<=0 || !check_string_length($title,1,48)||!in_array($type,[1,2,3,4])||
            $urls==''||!check_string_length($content,1,1000)){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $serviceSubType = model('servicesubtype')->where(['servicesubtypeid'=>$serviceSubTypeId])->find();
            if(!$serviceSubType){
                $this->returndata( 14002, 'service subtype not exist', $this->curTime, $data);
            }
            //查找当前用户是否有合法的服务身份
            $userServiceType = model('userservicetype')
                ->where(['userid'=>$this->curUserInfo['userid'],'servicetypeid'=>$serviceSubType['servicetypeid'],'delflag'=>0])
                ->find();
            if(!$userServiceType){
                $this->returndata( 14003, 'your servicetype error', $this->curTime, $data);
            }

            $serviceMainWhere = [
                'userid'=>$this->curUserInfo['userid'],
                'title'=>$title
            ];
            $service = model('servicemain')->where($serviceMainWhere)->find();

            if($service){
                $this->returndata( 14004, 'service title exist', $this->curTime, $data);
            }
            else{
                //创建服务主要信息
                $newServiceMain = [
                    'userid'        => $this->curUserInfo['userid'],
                    'title'         => $title,
                    'type'        => $type,
                    'urls'        => $urls,
                    'content'       => $content,

                    'state'         => 1,
                    'createtime'    => $this->curTime,
                    'updatetime'    => $this->curTime,
                    'delflag'       => 0

                ];
                $serviceId = model('servicemain')->insertGetId($newServiceMain);

                //创建服务选项
                $optionsArr = json_decode($options,true);
                $newOptions = [];
                $commonPrice = 0;
                foreach($optionsArr as $k=>$oneOption){
                    $price = $oneOption['price']<0?0:$oneOption['price'];
                    $newOptions[]=[
                        'serviceid'     =>$serviceId,
                        'optionname'    =>sub_string_length($oneOption['optionname'],16),
                        'price'         =>$price,
                        'stock'         =>$oneOption['stock']<0?0:$oneOption['stock'],
                        'sold_counter'  =>0,
                        'sort'          =>$k,
                        'createtime'    =>$this->curTime,
                        'delflag'       =>0
                    ];
                    if($commonPrice == 0 && $price>0){
                        $commonPrice = $price;
                    }
                }
                model('serviceoption')->insertAll($newOptions);

                //创建服务统计信息
                $newServiceData = [
                    'serviceid'     => $serviceId,
                    'servicesubtypeid' => $serviceSubTypeId,
                    'user_sex' => $this->curUserInfo['sex'],
                    'common_price' => $commonPrice,
                    'service_lat'   => $serviceLat,
                    'service_lon'   => $serviceLon,
                    'is_top'        => 0,
                    'is_hot'        => 0,
                    'updatetime'    => $this->curTime
                ];
                model('servicedata')->insertGetId($newServiceData);


                //更新服务类型数
                model('servicetype')->where(['servicetypeid'=>$serviceSubType['servicetypeid']])
                    ->update(['count'=>['exp','count+1']]);

                //更新子类服务类型数
                model('servicesubtype')->where(['servicesubtypeid'=>$serviceSubTypeId])
                    ->update(['count'=>['exp','count+1']]);

                //更新个人服务数
                $saveUserData = [
                    'service_counter'=>['exp','service_counter+1'],
                    'updatetime'=>$this->curTime
                ];
                model('userdata')->where(['userid'=>$this->curUserInfo['userid']])
                    ->update($saveUserData);
            }

            $data = array(
                'serviceid'=> $serviceId
            );
            $this->returndata(10000, '创建成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    查看服务详情
     * @url     /service/serviceView
     * @method  GET
     * @version 1000
     * @params  serviceid 1 INT 服务id YES
     * @params  service_lon '' STRING 提供服务的经度 YES
     * @params  service_lat '' STRING 提供服务的纬度 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function serviceView(){

        //返回结果
        $data = [];

        //获取接口参数
        $serviceId = input('serviceid');
        $serviceLon = input('request.service_lon',0);
        $serviceLat = input('request.service_lat',0);

        if($serviceId <= 0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $serviceMain = model('servicemain')->where(['serviceid'=>$serviceId,'state'=>['in',[1,2]]])->find();
            $serviceData = model('servicedata')->where(['serviceid'=>$serviceId])->find();
            $serviceOption = model('serviceoption')->where(['serviceid'=>$serviceId])->order('sort desc,createtime asc')->select();

            if(!$serviceMain || !$serviceData ||!$serviceOption){
                $this->returndata( 14002, 'service not exist', $this->curTime, $data);
            }


            $userInfo = model('userinfo')->where(['userid'=>$serviceMain['userid']])->find();
            $userData = model('userdata')->where(['userid'=>$serviceMain['userid']])->find();
            $userHx = model('userhx')->where(['userid'=>$serviceMain['userid']])->find();

            $city = model('city')->where(['id'=>$userInfo['cityid']])->find();

            $allControl = $this->getAllControl();

            if($serviceData['service_lat'] == 0 || $serviceData['service_lon'] ==0||
                $serviceLat == 0 || $serviceLon ==0){
                $distance = '很远';
            }
            else{
                $distance = getDistance($serviceData['service_lat'] ,$serviceData['service_lon'],
                        $serviceLat,$serviceLon,2,1).'km';
            }

            $data['service']=[
                'title'             => $serviceMain['title'],
                'urls'            => $this->checkpictureurl($allControl['service_image_url'],json_decode($serviceMain['urls'],true)),
                'content'           => $serviceMain['content'],
                'state'             => $serviceMain['state'],
                'createtime'        => $serviceMain['createtime'],
                'servicesubtypeid'  => $serviceData['servicesubtypeid'],
                'service_num'       => $serviceData['service_num'],
                'price'             => $serviceData['common_price'],
                'service_lat'       => $serviceData['service_lat'],
                'service_lon'       => $serviceData['service_lon'],
                'distance'          => $distance,
                'star_score'        => $serviceData['star_score'],
                'comment_counter'   => $serviceData['comment_counter'],
                'favour_counter'    => $serviceData['favour_counter'],
                'share_counter'     => $serviceData['share_counter'],
                'read_counter'      => $serviceData['read_counter'],
                'be_reported_counter'    => $serviceData['be_reported_counter'],
                'is_top'            => $serviceData['is_top'],
                'is_hot'            => $serviceData['is_hot'],
            ];
            $data['serviceoptions'] = [];
            foreach($serviceOption as $oneServiceOption){
                $data['serviceoptions'][]=[
                    'serviceoptionid'=>$oneServiceOption['serviceoptionid'],
                    'name'=>$oneServiceOption['optionname'],
                    'price'=>$oneServiceOption['price'],
                    'unit'=>$oneServiceOption['unit'],
                    'stock'=>$oneServiceOption['stock'],
                    'sold_counter'=>$oneServiceOption['sold_counter']
                ];
            }

            $avatar = $this->checkpictureurl($allControl['avatar_url'],$userInfo['avatar']);
            $userServiceType = model('userservicetype')->where(['userid'=>$serviceMain['userid'],'delflag'=>0])
                ->order('is_default desc,createtime asc')->find();

            $this->getAllServiceType();
            $serviceTypes=[
                'servicetypeid'=>$userServiceType['servicetypeid'],
                'name'=>$this->allServiceType[$userServiceType['servicetypeid']],
                'exp'=>$userServiceType['exp'],
            ];

            $followList = model('follow')->where(['userid'=>['in',$serviceMain['userid']]])->select();
            $newUserFollowList = [];
            foreach($followList as $oneFollow){
                $newUserFollowList[$oneFollow['followedid']]=$oneFollow;
            }

            $data['user']=[
                'userid'        => $serviceMain['userid'],
                'avatar'        => $avatar,
                'nickName'      => $userInfo['nickname'],
                'sex'           => $userInfo['sex'],
                'birthday'      => $userInfo['birthday'],
                'measurements'  => $userInfo['measurements'],
                'city'          => $city,
                'verify_state'  => $userInfo['verify_state'],
                'zhima_code'    => model('userinfo')->getZhimaState($userInfo['zhima_code']),
                "identity"      => $serviceTypes,
                'exp'           => $userData['exp'],
                'fans_counter'  => $userData['fans_counter'],
                'service_counter'=>$userData['service_counter'],
                'hxid'          => $userHx['hx_id'],           //环信id
                'hxpa'          => $userHx['hx_pass'],          //环信密码
                'isfollow'=>isset($this->curUserInfo['userid'])?
                    (isset($newUserFollowList[$serviceMain['userid']])?1:
                        ($serviceMain['userid']==$this->curUserInfo['userid']?2:0)):0,   //2是自己1已关注0未关注
            ];

            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    三级服务类型列表
     * @url     /service/serviceAllTypeList
     * @method  GET
     * @version 1000
     * @params  type 1 INT 列表类型1返回三个独立服务列表2返回一个嵌套三级类型的列表 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
        "code":10000,
        "message":"do success",
        "time":1493103387,
        "data":{
            "subTypeList":[
            {
                "servicesubtypeid":42,
                "servicetypeid":16,
                "name":"美少年写真",
                "image":"http://omsnjcbau.bkt.clouddn.com/1.jpg",
                "linetype":2        //1线上2线下3实物
            }
            ],
            "typeList":[
            {
                "servicetypeid":1,
                "servicetoptypeid":1,
                "name":"人气明星",
                "image":"http://omsnjcbau.bkt.clouddn.com/1.jpg"
            }
            ],
            "topTypeList":[
            {
                "servicetoptypeid":6,
                "name":"形象展示",
                "image":"http://omsnjcbau.bkt.clouddn.com/1.jpg"
            }
            ]
        }
    }
     *
     */
    public function serviceAllTypeList(){

        //返回结果
        $data = [];


        //获取接口参数
        $type = input('type',1);

        try{

            $serviceTopTypeList = model('servicetoptype')->where(['delflag'=>0])
                ->order('sort desc')->select();

            if(!$serviceTopTypeList ){
                $this->returndata( 14002, 'toptype not exist', $this->curTime, $data);
            }

            $serviceTypeList = model('servicetype')->where(['delflag'=>0])
                ->order('sort desc')->select();

            if(!$serviceTypeList ){
                $this->returndata( 14003, 'service type not exist', $this->curTime, $data);
            }

            $serviceSubTypeList = model('servicesubtype')->where(['delflag'=>0])
                ->order('sort desc')->select();

            if(!$serviceSubTypeList ){
                $this->returndata( 14004, 'subtype not exist', $this->curTime, $data);
            }

            $allControl = $this->getAllControl();

            $data['subTypeList'] = [];
            $data['typeList'] = [];
            $data['topTypeList'] = [];
            if($type == 1){
                foreach($serviceSubTypeList as $oneServiceSubType){
                    $data['subTypeList'][]=[
                        'servicesubtypeid'=>$oneServiceSubType['servicesubtypeid'],
                        'servicetypeid'=>$oneServiceSubType['servicetypeid'],
                        'name'=>$oneServiceSubType['name'],
                        'image'=>$this->checkpictureurl($allControl['servicetype_image_url'],$oneServiceSubType['image']),
                        'linetype'=>$oneServiceSubType['linetype'],
                    ];
                }

                foreach($serviceTypeList as $oneServiceType){
                    $data['typeList'][]=[
                        'servicetypeid'=>$oneServiceType['servicetypeid'],
                        'servicetoptypeid'=>$oneServiceType['servicetoptypeid'],
                        'name'=>$oneServiceType['name'],
                        'image'=>$this->checkpictureurl($allControl['servicetype_image_url'],$oneServiceType['image']),
                    ];
                }
                foreach($serviceTopTypeList as $oneServiceTopType){
                    $data['topTypeList'][] = [
                        'servicetoptypeid'  => $oneServiceTopType['servicetoptypeid'],
                        'name'              => $oneServiceTopType['name'],
                        'image'             => $this->checkpictureurl($allControl['servicetype_image_url'],$oneServiceTopType['image']),
                    ];
                }
            }
            else{
                $newServiceSubTypeList = [];
                foreach($serviceSubTypeList as $oneServiceSubType){
                    $newServiceSubTypeList[$oneServiceSubType['servicetypeid']][]=[
                        'servicesubtypeid'=>$oneServiceSubType['servicesubtypeid'],
                        'servicetypeid'=>$oneServiceSubType['servicetypeid'],
                        'name'=>$oneServiceSubType['name'],
                        'image'=>$this->checkpictureurl($allControl['servicetype_image_url'],$oneServiceSubType['image']),
                        'linetype'=>$oneServiceSubType['linetype'],
                    ];
                }

                $newServiceTypeList = [];
                foreach($serviceTypeList as $oneServiceType){
                    $newServiceTypeList[$oneServiceType['servicetoptypeid']][]=[
                        'servicetypeid'=>$oneServiceType['servicetypeid'],
                        'servicetoptypeid'=>$oneServiceType['servicetoptypeid'],
                        'name'=>$oneServiceType['name'],
                        'image'=>$this->checkpictureurl($allControl['servicetype_image_url'],$oneServiceType['image']),
                        'subtypelist'=>isset($newServiceSubTypeList[$oneServiceType['servicetypeid']])?
                            $newServiceSubTypeList[$oneServiceType['servicetypeid']]:[]
                    ];
                }

                foreach($serviceTopTypeList as $oneServiceTopType){
                    $data['topTypeList'][] = [
                        'servicetoptypeid'  => $oneServiceTopType['servicetoptypeid'],
                        'name'              => $oneServiceTopType['name'],
                        'image'             => $this->checkpictureurl($allControl['servicetype_image_url'],$oneServiceTopType['image']),
                        'typelist'          => isset($newServiceTypeList[$oneServiceTopType['servicetoptypeid']])?
                            $newServiceTypeList[$oneServiceTopType['servicetoptypeid']]:[]
                    ];
                }
            }


            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    选择喜欢的服务顶级类型列表
     * @url     /service/serviceTopTypeLike
     * @method  POST
     * @version 1000
     * @params  servicetoptypeids '1,2' STRING 服务顶级分类id串用,隔开 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function serviceTopTypeLike(){

        //返回结果
        $data = [];

        //获取接口参数
        $serviceTopTypeIds = input('servicetoptypeids','');

        $serviceTopTypeIds = explode(',',$serviceTopTypeIds);

        if(!$serviceTopTypeIds){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            //所有的顶级服务分类
            $serviceTopTypeList = model('servicetoptype')
                ->where(['servicetoptypeid'=>['in',$serviceTopTypeIds],'delflag'=>0])
                ->order('sort desc')->select();
            if(!$serviceTopTypeList ){
                $this->returndata( 14002, 'toptype not exist', $this->curTime, $data);
            }
            $newServicTopTypeIds = [];
            foreach($serviceTopTypeList as $oneServiceTopType){
                $newServicTopTypeIds[]=$oneServiceTopType['servicetoptypeid'];
            }
            if(!$newServicTopTypeIds){
                $newServicTopTypeIds='';
            }
            $serviceTypeList = model('servicetype')
                ->where(['servicetoptypeid'=>['in',$newServicTopTypeIds],'delflag'=>0])
                ->order('servicetoptypeid asc,sort desc')->select();


            $likedServiceTypeIds = [];
            foreach($serviceTypeList as $oneLikedServiceType){
                $likedServiceTypeIds[]=$oneLikedServiceType['servicetypeid'];
            }
            //新的喜欢的服务分类
            $newUserLikeServiceTypes = [];
            foreach($likedServiceTypeIds as $oneServiceTypeId){
                $newUserLikeServiceTypes[]=[
                    'servicetypeid'=>$oneServiceTypeId,
                    'userid'=>$this->curUserInfo['userid'],
                    'createtime'=>$this->curTime
                ];

            }
            if($newUserLikeServiceTypes){
                model('userlikeservicetype')->where(['userid'=>$this->curUserInfo['userid']])->delete();
                model('userlikeservicetype')->insertAll($newUserLikeServiceTypes);
            }


            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    选择喜欢的服务类型列表
     * @url     /service/serviceTypeLike
     * @method  POST
     * @version 1000
     * @params  servicetypeids '1,2' STRING 服务分类id串用,隔开 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function serviceTypeLike(){

        //返回结果
        $data = [];

        //获取接口参数
        $serviceTypeIds = input('servicetypeids','');

        $serviceTypeIds = explode(',',$serviceTypeIds);

        if(!$serviceTypeIds){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            //服务分类
            $serviceTypeList = model('servicetype')
                ->where(['servicetypeid'=>['in',$serviceTypeIds],'delflag'=>0])
                ->order('servicetoptypeid asc,sort desc')->select();
            if(!$serviceTypeList ){
                $this->returndata( 14002, 'type not exist', $this->curTime, $data);
            }

            $likedServiceTypeIds = [];
            foreach($serviceTypeList as $oneLikedServiceType){
                $likedServiceTypeIds[]=$oneLikedServiceType['servicetypeid'];
            }
            //新的喜欢的服务分类
            $newUserLikeServiceTypes = [];
            foreach($likedServiceTypeIds as $oneServiceTypeId){
                $newUserLikeServiceTypes[]=[
                    'servicetypeid'=>$oneServiceTypeId,
                    'userid'=>$this->curUserInfo['userid'],
                    'createtime'=>$this->curTime
                ];

            }
            if($newUserLikeServiceTypes){
                model('userlikeservicetype')->where(['userid'=>$this->curUserInfo['userid']])->delete();
                model('userlikeservicetype')->insertAll($newUserLikeServiceTypes);
            }

            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    服务首页三类推荐列表
     * @url     /service/serviceRecommendList
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function serviceRecommendList(){

        //返回结果
        $data = [];

        try{

            if(isset($this->curUserInfo['userid'])){
                $userId = $this->curUserInfo['userid'];
            }
            else{
                $userId = 0;
            }
            //服务首页顶部显示的服务类型个数 默认为7个
            $serviceTypeNum = 7;
            $userServiceTypeList = model('userservicetype')
                ->where(['userid'=>$userId,'delflag'=>0])
                ->order('exp desc')->select();
            $userServiceTypeCount = 0;
            $userServiceTypeIds = [];
            foreach($userServiceTypeList as $oneUserServiceType){
                if($userServiceTypeCount<$serviceTypeNum){
                    $userServiceTypeIds[]=$oneUserServiceType['servicetypeid'];
                    $userServiceTypeCount ++;
                }
            }
            $remainServiceTypeNum = $serviceTypeNum - $userServiceTypeCount;

            $remainServiceTypeIds = [];
            if($remainServiceTypeNum>0){
                $remainServiceTypeList = model('servicetype')
                    ->where(['servicetypeid'=>['not in',$userServiceTypeIds?$userServiceTypeIds:'']])
                    ->limit('0,'.$remainServiceTypeNum)->order('count desc')->select();
                foreach($remainServiceTypeList as $oneServiceType){
                    $remainServiceTypeIds[]=$oneServiceType['servicetypeid'];
                }
            }

            $recommendServiceTypeIds = array_merge($userServiceTypeIds,$remainServiceTypeIds);

            $recommendServiceTypeIds=[10,14,15,17,20,22,23];

            $recommendServiceTypeList = model('servicetype')
                ->where(['servicetypeid'=>['in',$recommendServiceTypeIds],'delflag'=>0])->select();

            $allControl = $this->getAllControl();
            //推荐的服务身份类型列表 默认7个
            $newRecommendServiceTypeList = [];
            foreach($recommendServiceTypeList as $oneServiceType){
                $newRecommendServiceTypeList[] = [
                    'servicetypeid'=>$oneServiceType['servicetypeid'],
                    'servicetoptypeid'=>$oneServiceType['servicetoptypeid'],
                    'name'=>$oneServiceType['name'],
                    'image'=>$this->checkPictureUrl($allControl['service_image_url'],$oneServiceType['image'])
                ];
            }
            $newRecommendServiceTypeList[] = [
                'servicetypeid'=>0,
                'servicetoptypeid'=>0,
                'name'=>'更多',
                'image'=>$this->checkPictureUrl($allControl['service_image_url'],'servicetypemore.png')
            ];

            $data['recommendservicetypelist'] = $newRecommendServiceTypeList;

            $serviceDataList = model('servicedata')->where([])->order('service_num desc')->limit(0,10)->select();
            $serviceIds = [];
            $newServiceList = [];
            foreach($serviceDataList as $oneServiceData){
                $serviceIds[]= $oneServiceData['serviceid'];
                $newServiceList[$oneServiceData['serviceid']] =$oneServiceData;
            }

            //推荐的服务次数最多的一个服务列表 默认10个
            $scrollServiceList = [];
            if($serviceIds){
                $serviceMainList = model('servicemain')->where(['serviceid'=>['in',$serviceIds]])->select();

                $serviceMainUserIds = [];
                foreach($serviceMainList as $oneServiceMain){
                    $serviceMainUserIds[]=$oneServiceMain['userid'];
                }
                if(!$serviceMainUserIds){
                    $serviceMainUserIds='';
                }
                $serviceMainUserList = model('userinfo')->where(['userid'=>['in',$serviceMainUserIds]])->select();

                $newServiceMainUserList = [];
                foreach($serviceMainUserList as $oneServiceMainUser){
                    $newServiceMainUserList[$oneServiceMainUser['userid']]=$oneServiceMainUser;
                }

                foreach($serviceMainList as $oneServiceMain){
                    $scrollServiceList[]=[
                        'serviceid'=>$oneServiceMain['serviceid'],
                        'userid'=>$oneServiceMain['userid'],
                        'nickname'=>$newServiceMainUserList[$oneServiceMain['userid']]['nickname'],
                        'title'=>$oneServiceMain['title'],
                        'service_num'=>$newServiceList[$oneServiceMain['serviceid']]['service_num'],
                        'star_score'=>$newServiceList[$oneServiceMain['serviceid']]['star_score'],
                    ];
                }
            }

            $data['recommendservicelist'] = $scrollServiceList;

            //推荐活动列表 默认3个
            $serviceRecommendWhere = ['type'=>1,'delflag'=>0];
            $serviceRecommendList = model('servicerecommend')->where($serviceRecommendWhere)->order('sort desc')->limit(0,3)->select();
            $newServiceRecommendList = [];
            foreach($serviceRecommendList as $oneServiceRecommend){
                $newServiceRecommendList[]=[
                    'type'=>$oneServiceRecommend['type'],
                    'object'=>$oneServiceRecommend['object'],
                    'image'=>$this->checkPictureUrl($allControl['recommend_image_url'],$oneServiceRecommend['image']),
                ];
            }
            
            $data['recommendlist'] = $newServiceRecommendList;

            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建服务订单接口
     * @url     /service/createOrder
     * @method  POST
     * @version 1000
     * @params  serviceid 1 INT 服务id YES
     * @params  unit_num 1 INT 服务个数 YES
     * @params  agreetime 0 INT 服务约定时间戳 YES
     * @params  addressid 0 INT 收货信息id YES
     * @params  optionid 11 INT 服务选项 YES
     * @params  mark '' STRING 留言备注 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function createOrder(){
        //返回结果
        $data = [];

        //获取接口参数
        $serviceId = input('request.serviceid',0);
        $unitNum = input('request.unit_num',1);
        $agreeTime = input('request.agreetime',0);
        $addressId = input('request.addressid',0);

        $optionId = input('request.optionid',0);

        $mark = input('request.mark','');

        //验证参数是否为空
        if($serviceId<=0 || $unitNum<1 || $addressId<0 ||$optionId<0){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $serviceMain = model('servicemain')->where(['serviceid'=>$serviceId,'state'=>1,'delflag'=>0])->find();
            if(!$serviceMain){
                $this->returndata( 14002, 'service not exist', $this->curTime, $data);
            }
            $serviceData = model('servicedata')->where(['serviceid'=>$serviceId])->find();
            if(!$serviceData){
                $this->returndata( 14003, 'service not exist', $this->curTime, $data);
            }
            $serviceSubTypeId = $serviceData['servicesubtypeid'];
            $serviceSubType = model('servicesubtype')->where(['servicesubtypeid'=>$serviceSubTypeId])->find();
            if(!$serviceSubType){
                $this->returndata( 14004, 'servicesubtype not exist', $this->curTime, $data);
            }
            if($serviceSubType['linetype']==1||$serviceSubType['linetype']==2){
                if($agreeTime<0||$agreeTime-$this->curTime<1800){
                    $this->returndata( 14005,  'agreetime param error', $this->curTime, $data);
                }
            }

            $address = model('address')->where(['addressid'=>$addressId,'userid'=>$this->curUserInfo['userid'],'delflag'=>0])->find();
            if(!$address){
                $this->returndata( 14006, 'address not exist', $this->curTime, $data);
            }
            if($serviceSubType['linetype'] == 1){
                $buyerInfo = $address['phone'];
            }
            else{
                $buyerInfo = $address['name'].' '.$address['phone'].' '.$address['address'];
            }

            $option = model('serviceoption')->where(['serviceoptionid'=>$optionId,'serviceid'=>$serviceId,'delflag'=>0])->find();
            if(!$option){
                $this->returndata( 14007, 'serviceoption not exist', $this->curTime, $data);
            }
            $serviceOption = json_encode([
                'optionid'      => $option['serviceoptionid'],
                'optionname'    => $option['optionname'],
                'price'         => $option['price'],
                'unit'          => $option['unit'],
                'sold_counter'  => $option['sold_counter'],

            ]);

            $allControls = $this->getAllControl();
            $serviceImages = json_decode($serviceMain['images'],true);

            if($serviceImages){
                $image = $this->checkPictureUrl($allControls['service_image_url'],$serviceImages[0]);
            }
            else{
                $image = '';
            }
            $totalPrice = bcmul($option['price'],$unitNum,2);
            //创建服务订单信息
            $newServiceOrder = [
                'state'         => 1,
                'sellerid'      => $serviceMain['userid'],
                'buyerid'       => $this->curUserInfo['userid'],
                'serviceid'     => $serviceId,
                'title'         => $serviceData['title'],
                'image'         => $image,
                'option'        => $serviceOption,
                'total_price'   => $totalPrice,
                'unit_num'      => $unitNum,
                'unit_price'    => $option['price'],
                'star_state'    => $serviceMain['userid'],
                'agreetime'     => $agreeTime,
                'buyer_info'    => $buyerInfo,
                'mark'          => $mark,
                'createtime'    => $this->curTime,
                'updatetime'    => $this->curTime,
                'delflag'       => 0

            ];
            $serviceOrderId = model('serviceorder')->insertGetId($newServiceOrder);

            if(!$serviceOrderId){
                $this->returndata( 14007, 'order create fail', $this->curTime, $data);
            }

            //创建服务统计信息
            $newSoDetail = [
                'serviceorderid'=> $serviceOrderId,
                'deal_userid'   => $this->curUserInfo['userid'],
                'desc'          => $this->curUserInfo['nickname'].'创建服务订单，总价:'.$totalPrice.'元',
                'createtime'    => $this->curTime
            ];
            model('serviceorderdetail')->insertGetId($newSoDetail);


           /* //更新服务类型数
            model('servicetype')->where(['servicetypeid'=>$serviceSubType['servicetypeid']])
                ->update(['count'=>['exp','count+1']]);

            //更新子类服务类型数
            model('servicesubtype')->where(['servicesubtypeid'=>$serviceSubTypeId])
                ->update(['count'=>['exp','count+1']]);

            //更新个人服务数
            $saveUserData = [
                'service_counter'=>['exp','service_counter+1'],
                'updatetime'=>$this->curTime
            ];
            model('userdata')->where(['userid'=>$this->curUserInfo['userid']])
                ->update($saveUserData);*/


            $data = array(
                'serviceorderid'=> $serviceOrderId,
                'title'=> $serviceData['title'],
                'total_price'=> $totalPrice,
                'buyer_info'=> $buyerInfo,
                'limittime' =>$this->curTime+config('order_pay_limit_time')
            );
            $this->returndata(10000, '创建成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }











}
