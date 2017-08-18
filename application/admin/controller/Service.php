<?php
namespace app\index\controller;

use app\common\controller\Front;
class Service extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    服务列表接口
     * @url     /service/serviceList
     * @method  POST
     * @version 1000
     * @params  type 1 INT 服务列表类型1最新2最热3距离最近4神秘商人 YES
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
        $type = input('request.type',1);
        $page = input('request.page',1);
        $serviceLon = input('request.service_lon',0);
        $serviceLat = input('request.service_lat',0);

        //验证参数是否为空
        if(!in_array($type,[1,2,3,4]) ||$page<1||$serviceLat<0||$serviceLon<=0){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $serviceMainWhere = ['state'=>1,'delflag'=>0];
            switch ($type){
                case 1:

                    $serviceMainList = model('servicemain')->where($serviceMainWhere)->order('createtime desc ')
                        ->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
                case 2:

                    $serviceMainList = model('servicemain')->where($serviceMainWhere)->order('is_hot desc,createtime desc ')
                        ->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
                case 3:

                    $indistance=100000;//距离范围  单位 m
                    $minmaxarr=getRectByPoint($serviceLat,$serviceLon,$indistance);

                    $serviceMainWhere['service_lat']=array('between',array($minmaxarr[0],$minmaxarr[1]));
                    //如果最小经度低于了-180
                    if($minmaxarr[0]<-180){
                        $serviceMainWhere['service_lon']=array(array('between',array($minmaxarr[2]+360,180)),array('between',array(-180,$minmaxarr[3])),'or');
                    }
                    else if($minmaxarr[1]>180){//如果最大经度超过了180
                        $serviceMainWhere['service_lon']=array(array('between',array($minmaxarr[2],180)),array('between',array(-180,$minmaxarr[3]-360)),'or');
                    }
                    else{
                        $serviceMainWhere['service_lon']=array('between',array($minmaxarr[2],$minmaxarr[3]));
                    }

                    $serviceMainList = model('servicemain')->where($serviceMainWhere)->order('createtime desc ')
                        ->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
                case 4:
                    $serviceMainList = [];
                    break;


            }

            $serviceIds = [];
            $userIds = [];
            foreach($serviceMainList as $oneServiceMain){
                $serviceIds[]=$oneServiceMain['serviceid'];
                $userIds[] = $oneServiceMain['userid'];
            }

            if(!$serviceIds){
                $this->returndata( 14002, 'service list is empty', $this->curTime, $data);
            }

            $serviceDataList = model('servicedata')->where(['serviceid'=>['in',$serviceIds]])->select();
            $newServiceDataList = [];
            foreach($serviceDataList as $oneServiceData){
                $newServiceDataList[$oneServiceData['serviceid']] = [
                    'service_num'=>$oneServiceData['service_num'],
                    'star_score'=>$oneServiceData['star_score']
                ];
            }

            $this->getAllControl();
            $userInfoList = model('userinfo')->where(['userid'=>['in',$userIds]])->select();
            $newUserInfoList = [];
            foreach($userInfoList as $oneUserInfo){

                $zhimaState = model('userinfo')->getZhimaState($oneUserInfo['zhima_code']);

                $newUserInfoList[$oneUserInfo['userid']] = [
                    'avatar'=>$this->checkPictureUrl($this->allControl['avatarurl'],$oneUserInfo['avatar']),
                    'nickname'=>$oneUserInfo['nickname'],
                    'verify_state'=>$oneUserInfo['verify_state'],
                    'zhima_state'=>$zhimaState,
                ];
            }

            $userServiceTypeList = model('userservicetype')->where(['userid'=>['in',$userIds]])->select();
            $newUserServicetypeList = [];

            $this->getAllServiceType();
            foreach($userServiceTypeList as $oneUserServiceType){

                if(!isset($newUserServicetypeList[$oneUserServiceType['userid']])){
                    $newUserServicetypeList[$oneUserServiceType['userid']]=[
                        'serviceTypeName'=>$this->allServiceType[$oneUserServiceType['servicetypeid']],
                        'exp'=>$oneUserServiceType['exp']
                    ];
                }
                elseif($newUserServicetypeList[$oneUserServiceType['userid']]['exp']<$oneUserServiceType['exp']){
                    $newUserServicetypeList[$oneUserServiceType['userid']]=[
                        'serviceTypeName'=>$this->allServiceType[$oneUserServiceType['servicetypeid']],
                        'exp'=>$oneUserServiceType['exp']
                    ];
                }
            }

            $newServicelist = [];
            foreach($serviceMainList as $oneServiceMain){
                $image = json_decode($oneServiceMain['images'],true);
                $newServicelist[]=[
                    'serviceid'=>$oneServiceMain['serviceid'],
                    'servicetypeid'=>$oneServiceMain['servicetypeid'],
                    'userid'=>$oneServiceMain['userid'],
                    'avatar'=>$newUserInfoList[$oneServiceMain['userid']]['avatar'],
                    'nickname'=>$newUserInfoList[$oneServiceMain['userid']]['nickname'],
                    'verify_state'=>$newUserInfoList[$oneServiceMain['userid']]['verify_state'],
                    'zhima_state'=>$newUserInfoList[$oneServiceMain['userid']]['zhima_state'],
                    'title'=>$oneServiceMain['title'],
                    'images'=>$image?$this->checkPictureUrl($this->allControl['serviceimageurl'],$image[0]):'',
                    //'content'=>$oneServiceMain['content'],
                    'service_num'=>$newServiceDataList[$oneServiceMain['serviceid']]['service_num'],
                    'star_score'=>$newServiceDataList[$oneServiceMain['serviceid']]['star_score'],
                    'serviceTypeName'=>$newUserServicetypeList[$oneServiceMain['userid']]['serviceTypeName'],
                    'exp'=>$newUserServicetypeList[$oneServiceMain['userid']]['exp'],
                    'distance'=>getDistance($oneServiceMain['service_lat'],$oneServiceMain['service_lat'],$serviceLat,$serviceLon),
                ];
            }

            $data['serviceList'] = $newServicelist;
            $this->returndata(10000, '创建成功', $this->curTime, $data);

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
     * @params  servicetypeid 1 INT 服务类型id YES
     * @params  title '标题' STRING 服务标题 YES
     * @params  imgages '["a.jpg","b.jpg"]' STRING 服务图片json串 YES
     * @params  content '内容' STRING 服务内容描述 YES
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
        $serviceTypeId = input('request.servicetypeid',0);
        $title = input('request.title','');
        $imgages = input('request.imgages','');
        $content = input('request.content','');

        $options = input('request.options');

        $serviceLat = input('request.service_lat',0);
        $serviceLon = input('request.service_lon',0);

        //验证参数是否为空
        if($serviceTypeId<=0 || !check_string_length($title,48)||$imgages==''||!check_string_length($title,500)){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{



            $serviceType = model('servicetype')->where(['servicetypeid'=>$serviceTypeId])->find();
            if(!$serviceType){
                $this->returndata( 14002, 'service type not exist', $this->curTime, $data);
            }

            $serviceWhere = [
                'userid'=>$this->curUserInfo['userid'],
                'title'=>$title
            ];
            $service = model('service')->where($serviceWhere)->find();

            if($service){
                $this->returndata( 14002, 'service title exist', $this->curTime, $data);
            }
            else{
                //创建服务主要信息
                $newServiceMain = [
                    'servicetypeid' => $serviceTypeId,
                    'userid'        => $this->curUserInfo['userid'],
                    'title'         => $title,
                    'images'        => $imgages,
                    'content'       => $content,
                    'service_lat'   => $serviceLat,
                    'service_lon'   => $serviceLon,
                    'is_top'        => 0,
                    'is_hot'        => 0,
                    'state'         => 1,
                    'createtime'    => $this->curTime,
                    'updatetime'    => $this->curTime,
                    'delflag'       => 0

                ];
                $serviceId = model('servicemain')->insertGetId($newServiceMain);

                //创建服务选项
                $optionsArr = json_decode($options,true);
                $newOptions = [];
                foreach($optionsArr as $k=>$oneOption){
                    $newOptions[]=[
                        'serviceid'     =>$serviceId,
                        'optionname'    =>mb_substr($oneOption['optionname'],0,16),
                        'price'         =>$oneOption['price']<0?0:$oneOption['price'],
                        'stock'         =>$oneOption['stock']<0?0:$oneOption['stock'],
                        'sold_counter'  =>0,
                        'sort'          =>$k,
                        'createtime'    =>$this->curTime,
                        'delflag'       =>0
                    ];
                }
                model('serviceoptions')->insertAll($newOptions);

                //创建服务统计信息
                $newServiceData = [
                    'serviceid'     => $serviceId,
                    'updatetime'    => $this->curTime
                ];
                model('servicedata')->insertGetId($newServiceData);


                //更新服务类型数
                model('servicetype')->where(['servicetypeid'=>$serviceTypeId])
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
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function serviceView(){

        //返回结果
        $data = [];

        //获取接口参数
        $serviceId = input('serviceid');

        if($serviceId <= 0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $serviceMain = model('servicemain')->where(['serviceid'=>$serviceId])->find();
            $serviceData = model('servicedata')->where(['serviceid'=>$serviceId])->find();

            if(!$serviceMain || !$serviceData){
                $this->returndata( 14002, 'service not exist', $this->curTime, $data);
            }

            $userInfo = model('userinfo')->where(['userid'=>$serviceMain['userid']])->find();
            $userData = model('userdata')->where(['userid'=>$serviceMain['userid']])->find();
            $userHx = model('userhx')->where(['userid'=>$serviceMain['userid']])->find();

            $city = model('city')->where(['id'=>$userInfo['cityid']])->find();

            $data['service']=[
                'title'             => $serviceMain['title'],
                'images'            => $serviceMain['images'],
                'content'           => $serviceMain['content'],
                'price'             => $serviceMain['price'],
                'service_lat'       => $serviceMain['service_lat'],
                'service_lon'       => $serviceMain['service_lon'],
                'state'             => $serviceMain['state'],
                'createtime'        => $serviceMain['createtime'],
                'stock'             => $serviceData['stock'],
                'service_num'       => $serviceData['service_num'],
                'star_score'        => $serviceData['star_score'],
                'comment_counter'   => $serviceData['comment_counter'],
                'favour_counter'    => $serviceData['favour_counter'],
                'share_counter'     => $serviceData['share_counter'],
                'read_counter'      => $serviceData['read_counter'],
                'report_counter'    => $serviceData['report_counter'],
                'is_top'            => $serviceData['is_top'],
                'is_hot'            => $serviceData['is_hot'],
            ];

            $allControl = $this->getAllControl();
            $avatar = $this->checkpictureurl($allControl['useravatarurl'],$userInfo['avatar']);
            $data['user']=[
                'avatar'        => $avatar,
                'nickName'      => $userInfo['nickname'],
                'sex'           => $userInfo['sex'],
                'constellation' => $userInfo['constellation'],
                'measurements'  => $userInfo['measurements'],
                'city'          => $city,
                'verify_state'  => $userInfo['verify_state'],
                'zhima_code'    => $userInfo['zhima_code'],
                'exp'           => $userData['exp'],
                'fans_counter'  => $userInfo['fans_counter'],
                'service_counter'=>$userInfo['service_counter'],
                'hxid'          => $userHx['hx_id'],           //环信id
                'hxpa'          => $userHx['hx_pass']          //环信密码
            ];
            $this->returndata(10000, 'view success', $this->curTime, $data);


        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    服务顶级类型列表
     * @url     /service/serviceTopTypeList
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function serviceTopTypeList(){

        //返回结果
        $data = [];

        try{

            $serviceTopTypeList = model('servicetoptype')->where(['delflag'=>0])
                ->order('sort desc')->select();

            if(!$serviceTopTypeList ){
                $this->returndata( 14002, 'toptype not exist', $this->curTime, $data);
            }

            $allControl = $this->getAllControl();
            foreach($serviceTopTypeList as $oneServiceTopType){
                //var_dump($this->checkpictureurl($allControl['service_image_url'],$oneServiceType['image']));
                $data['serviceTypeList'][] = [
                    'servicetoptypeid'  => $oneServiceTopType['servicetoptypeid'],
                    'name'              => $oneServiceTopType['name'],
                    'image'             => $this->checkpictureurl($allControl['service_image_url'],$oneServiceTopType['image'])
                ];
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
     * @method  GET
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
            $allServiceTopTypeList = model('servicetoptype')->where(['delflag'=>0])
                ->order('sort desc')->select();
            if(!$allServiceTopTypeList ){
                $this->returndata( 14002, 'toptype not exist', $this->curTime, $data);
            }
            $allServicTopTypeIds = [];
            foreach($allServiceTopTypeList as $oneServiceTopType){
                $allServicTopTypeIds[]=$oneServiceTopType['servicetoptypeid'];
            }

            //已经喜欢的顶级服务分类
            $likedServiceTopTypeList = model('userlikeservicetoptype')
                ->where(['userid'=>$this->curUserInfo['userid']])->select();
            $likedServiceTopTypeIds = [];
            foreach($likedServiceTopTypeList as $oneLikedServiceTopType){
                $likedServiceTopTypeIds[]=$oneLikedServiceTopType['servicetoptypeid'];
            }
            //新的喜欢的顶级服务分类
            $newUserLikeServiceTopTypes = [];
            foreach($serviceTopTypeIds as $oneServiceTopTypeId){
                if(in_array($oneServiceTopTypeId,$allServicTopTypeIds) &&
                    !in_array($oneServiceTopTypeId,$likedServiceTopTypeIds)){
                    $newUserLikeServiceTopTypes[]=[
                        'servicetoptypeid'=>$oneServiceTopTypeId,
                        'userid'=>$this->curUserInfo['userid'],
                        'createtime'=>$this->curTime
                    ];
                }
            }
            if($newUserLikeServiceTopTypes){
                model('userlikeservicetoptype')->insertAll($newUserLikeServiceTopTypes);
            }


            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    服务类型列表
     * @url     /service/serviceTypeList
     * @method  GET
     * @version 1000
     * @params  servicetoptypeid 0 INT 服务顶级分类id,为0返回所有服务类型 YES
     * @params  ishot 0 INT 是否热门服务为0不判断热门1不热门2热门 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function serviceTypeList(){

        //返回结果
        $data = [];

        //获取接口参数
        $serviceTopTypeId = input('servicetoptypeid',0);
        $isHot = input('ishot',0);

        if($serviceTopTypeId < 0||!in_array($isHot,[0,1,2])){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{
            if($serviceTopTypeId == 0){
                $serviceTypeWhere = ['delflag'=>0];
            }
            else{
                $serviceTypeWhere = ['servicetoptypeid'=>$serviceTopTypeId,'delflag'=>0];
            }
            if($isHot != 0){
                $serviceTypeWhere['is_hot'] = $isHot;
            }
            $serviceTypeList = model('servicetype')->where($serviceTypeWhere)
                ->order('sort desc')->select();

            if(!$serviceTypeList ){
                $this->returndata( 14002, 'servicetype not exist', $this->curTime, $data);
            }

            $allControl = $this->getAllControl();
            foreach($serviceTypeList as $oneServiceType){
                //var_dump($this->checkpictureurl($allControl['service_image_url'],$oneServiceType['image']));
                $data['serviceTypeList'][] = [
                    'servicetypeid'     => $oneServiceType['servicetypeid'],
                    'servicetoptypeid'  => $oneServiceType['servicetoptypeid'],
                    'name'              => $oneServiceType['name'],
                    'image'             => $this->checkpictureurl($allControl['service_image_url'],$oneServiceType['image'])
                ];
            }

            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


}
