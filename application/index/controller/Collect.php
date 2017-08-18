<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Collect
 *
 * @classdesc 收藏接口类
 * @package app\index\controller
 */
class Collect extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    收藏列表接口
     * @url     /collect/collectList
     * @method  GET
     * @version 1000
     * @params  type 2 INT 收藏列表类型2用户3服务4活动5专辑6动态话题 YES
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function collectList(){

        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数
        $type = input('request.type',2,'intval');
        $page = input('request.page',1,'intval');

        if($page<1){
            $page=1;
        }
        //验证参数是否为空
        if(!in_array($type,[2,3,4,5,6])  ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $collectList = model('usercollect')
                ->where(['userid'=>$this->curUserInfo['userid'],'type'=>$type,'delflag'=>0])
                ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();
            $objIds = [];

            foreach($collectList as $oneCollect){
                $objIds[]=$oneCollect['obj_id'];
            }
            if(!$objIds){
                $objIds='';
            }


            $data['userList']=[];
            $data['serviceList']=[];
            $data['blogList']=[];
            $data['albumList']=[];

            switch ($type){

                case 2:
                    $userInfoList = model('userinfo')->where(['userid'=>['in',$objIds]])->order('createtime desc')
                        ->limit((($page-1)*$pageSize).','.$pageSize)->select();

                    $followList = model('follow')
                        ->where(['userid'=>$this->curUserInfo['userid'],'followedid'=>['in',$objIds]])->select();
                    $followUserIds = [];
                    foreach($followList as $oneFollow){
                        $followUserIds[]=$oneFollow['followedid'];
                    }

                    //var_dump($userIds,model('follow')->getLastSql());exit;
                    $this->getAllServiceType();
                    $this->getAllControl();

                    $userServiceTypeList = model('userservicetype')
                        ->where(['userid'=>['in',$objIds],'is_default'=>1,'delflag'=>0])->select();
                    $newUserServiceTypeList = [];
                    foreach($userServiceTypeList as $oneUserServiceType){
                        $newUserServiceTypeList[$oneUserServiceType['userid']]=$oneUserServiceType;
                    }

                    $userList = [];
                    foreach($userInfoList as $oneUserInfo){
                        $userList[$oneUserInfo['userid']] = [
                            'userid'=>$oneUserInfo['userid'],
                            'avatar'=>$this->checkPictureUrl($this->allControl['avatar_url'],$oneUserInfo['avatar']),
                            'nickname'=>$oneUserInfo['nickname'],
                            'brief'=>$oneUserInfo['brief'],
                            'tasktypename'=>isset($newUserServiceTypeList[$oneUserInfo['userid']])?
                                $this->allServiceType[$newUserServiceTypeList[$oneUserInfo['userid']]['servicetypeid']]:'',
                            'exp'=>isset($newUserServiceTypeList[$oneUserInfo['userid']])?
                                $newUserServiceTypeList[$oneUserInfo['userid']]['exp']:0,
                            'isfollow'=>$oneUserInfo['userid']==$this->curUserInfo['userid']?
                                2:(in_array($oneUserInfo['userid'],$followUserIds)?1:0)    //2是自己1已关注0未关注
                        ];
                    }

                    foreach($collectList as $oneCollect){
                        $data['userList'][]=array_merge(['collectid'=>$oneCollect['collectid']],$userList[$oneCollect['obj_id']]);
                    }

                    break;
                case 3:

                    $serviceMainWhere=[
                        'serviceid'=>['in',$objIds],
                        'state'=>1,
                        'delflag'=>0
                    ];

                    $serviceMainList = model('servicemain')->where($serviceMainWhere)->order('createtime desc')
                        ->limit((($page-1)*$pageSize).','.$pageSize)->select();

                    $userIds = [];
                    foreach($serviceMainList as $oneServiceMain){
                        $userIds[] = $oneServiceMain['userid'];
                    }

                    $serviceDataList = model('servicedata')->where(['serviceid'=>['in',$objIds],'delflag'=>0])->select();

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
                        $urls = json_decode($oneServiceMain['urls'],true);

                        $newServicelist[$oneServiceMain['serviceid']]=[
                            'serviceid'=>$oneServiceMain['serviceid'],
                            'servicesubtypeid'=>isset($newServiceDataList[$oneServiceMain['serviceid']])?
                                $newServiceDataList[$oneServiceMain['serviceid']]['servicesubtypeid']:0,
                            'userid'=>$oneServiceMain['userid'],
                            'avatar'=>$newUserInfoList[$oneServiceMain['userid']]['avatar'],
                            'nickname'=>$newUserInfoList[$oneServiceMain['userid']]['nickname'],
                            'verify_state'=>$newUserInfoList[$oneServiceMain['userid']]['verify_state'],
                            'zhima_state'=>$newUserInfoList[$oneServiceMain['userid']]['zhima_state'],
                            'title'=>$oneServiceMain['title'],
                            'common_price'=>$newServiceDataList[$oneServiceMain['serviceid']]['common_price'],

                            'urls'=>is_array($urls)?$this->checkPictureUrl($this->allControl['service_image_url'],$urls[0]):'',
                            //'content'=>$oneServiceMain['content'],
                            'service_num'=>isset($newServiceDataList[$oneServiceMain['serviceid']])?
                                $newServiceDataList[$oneServiceMain['serviceid']]['service_num']:0,
                            'star_score'=>isset($newServiceDataList[$oneServiceMain['serviceid']])?
                                $newServiceDataList[$oneServiceMain['serviceid']]['star_score']:0,
                            'servicetypename'=>isset($newUserServicetypeList[$oneServiceMain['userid']])?
                                $newUserServicetypeList[$oneServiceMain['userid']]['servicetypename']:'',
                            'exp'=>isset($newUserServicetypeList[$oneServiceMain['userid']])?
                                $newUserServicetypeList[$oneServiceMain['userid']]['exp']:0,

                        ];
                    }


                    foreach($collectList as $oneCollect){
                        $data['serviceList'][]=array_merge(['collectid'=>$oneCollect['collectid']],$newServicelist[$oneCollect['obj_id']]);
                    }

                    break;
                case 4:


                    break;
                case 5:
                    $albumMainList = model('albummain')->where(['albumid'=>['in',$objIds],'delflag'=>0])
                        ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();

                    $userIds = [];
                    foreach($albumMainList as $oneAlbumMain){
                        $userIds[] = $oneAlbumMain['userid'];
                    }
                    $albumDataList = model('albumdata')->where(['albumid'=>['in',$objIds]])->select();
                    $newAlbumDataList = [];
                    foreach($albumDataList as $oneAlbumData){
                        $newAlbumDataList[$oneAlbumData['albumid']] = [
                            'total_favour_counter'=>$oneAlbumData['total_favour_counter'],
                            'pay_counter'=>$oneAlbumData['pay_counter'],
                            'be_collected_counter'=>$oneAlbumData['be_collected_counter'],
                            'be_supported_counter'=>$oneAlbumData['be_supported_counter'],
                            'read_counter'=>$oneAlbumData['read_counter'],
                            'be_reported_counter'=>$oneAlbumData['be_reported_counter'],
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

                    $followList = model('follow')->where(['userid'=>['in',$userIds]])->select();
                    $newUserFollowList = [];
                    foreach($followList as $oneFollow){
                        $newUserFollowList[$oneFollow['followedid']]=$oneFollow;
                    }

                    $newAlbumlist = [];
                    foreach($albumMainList as $oneAlbumMain){

                        $newAlbumlist[$oneAlbumMain['albumid']]=[
                            'albumid'=>$oneAlbumMain['albumid'],
                            'name'=>$oneAlbumMain['name'],

                            /*'userid'=>$oneAlbumMain['userid'],
                            'avatar'=>$newUserInfoList[$oneAlbumMain['userid']]['avatar'],
                            'nickname'=>$newUserInfoList[$oneAlbumMain['userid']]['nickname'],
                            'verify_state'=>$newUserInfoList[$oneAlbumMain['userid']]['verify_state'],
                            'zhima_state'=>$newUserInfoList[$oneAlbumMain['userid']]['zhima_state'],
                            'servicetypename'=>isset($newUserServicetypeList[$oneAlbumMain['userid']])?
                                $newUserServicetypeList[$oneAlbumMain['userid']]['servicetypename']:'',
                            'exp'=>isset($newUserServicetypeList[$oneAlbumMain['userid']])?
                                $newUserServicetypeList[$oneAlbumMain['userid']]['exp']:0,
                            'isfollow'=>isset($this->curUserInfo['userid'])?
                                (isset($newUserFollowList[$oneAlbumMain['userid']])?1:
                                    ($oneAlbumMain['userid']==$this->curUserInfo['userid']?2:0)):0,   //2是自己1已关注0未关注*/

                            'image_url'=>$this->checkPictureUrl($this->allControl['album_image_url'],$oneAlbumMain['image_url']),

                            'total_favour_counter'=>isset($newAlbumDataList[$oneAlbumMain['albumid']])?
                                $newAlbumDataList[$oneAlbumMain['albumid']]['total_favour_counter']:0,
                            //'pay_counter'=>isset($newAlbumDataList[$oneAlbumMain['albumid']])?
                            //    $newAlbumDataList[$oneAlbumMain['albumid']]['pay_counter']:0,
                            'be_collected_counter'=>isset($newAlbumDataList[$oneAlbumMain['albumid']])?
                                $newAlbumDataList[$oneAlbumMain['albumid']]['be_collected_counter']:0,
                            'be_supported_counter'=>isset($newAlbumDataList[$oneAlbumMain['albumid']])?
                                $newAlbumDataList[$oneAlbumMain['albumid']]['be_supported_counter']:0,

                        ];
                    }

                    foreach($collectList as $oneCollect){
                        $data['albumList'][]=array_merge(['collectid'=>$oneCollect['collectid']],$newAlbumlist[$oneCollect['obj_id']]);
                    }
                    break;
                case 6:

                    $blogMainList = model('blogmain')->where(['blogid'=>['in',$objIds],'delflag'=>0])
                        ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();


                    $userIds = [];
                    foreach($blogMainList as $oneBlogMain){
                        $userIds[] = $oneBlogMain['userid'];
                    }
                    $blogDataList = model('blogdata')->where(['blogid'=>['in',$objIds]])->select();
                    $newBlogDataList = [];
                    foreach($blogDataList as $oneBlogData){
                        $newBlogDataList[$oneBlogData['blogid']] = [
                            'comment_counter'=>$oneBlogData['comment_counter'],
                            'favour_counter'=>$oneBlogData['favour_counter'],
                            'share_counter'=>$oneBlogData['share_counter'],
                            'read_counter'=>$oneBlogData['read_counter'],
                            'be_collected_counter'=>$oneBlogData['be_collected_counter'],
                            'be_supported_counter'=>$oneBlogData['be_supported_counter'],
                            'be_reported_counter'=>$oneBlogData['be_reported_counter'],
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

                    $followList = model('follow')->where(['userid'=>['in',$userIds]])->select();
                    $newUserFollowList = [];
                    foreach($followList as $oneFollow){
                        $newUserFollowList[$oneFollow['followedid']]=$oneFollow;
                    }

                    $newBloglist = [];
                    foreach($blogMainList as $oneBlogMain){
                        $urls = json_decode($oneBlogMain['urls'],true);
                        if(!is_array($urls)){
                            $urls=[];
                        }

                        $relateObjs = json_decode($oneBlogMain['relate_objs'],true);
                        if(!$relateObjs){
                            $relateObjs=[];
                        }
                        $serviceIds = [];
                        $albumIds = [];

                        foreach($relateObjs as $oneObj){

                            if(isset($oneObj['type'])&&$oneObj['type']==3){
                                $serviceIds[]=$oneObj['obj'];
                            }
                            elseif(isset($oneObj['type'])&&$oneObj['type']==5){
                                $albumIds[]=$oneObj['obj'];
                            }
                        }
                        $newRelateObj = [];
                        if($serviceIds){
                            $serviceMainList = model('servicemain')
                                ->where(['serviceid'=>['in',$serviceIds],'state'=>1,'delflag'=>0])->select();
                            foreach($serviceMainList as $oneServiceMain){
                                $serviceUrls = json_decode($oneServiceMain['urls'],true);
                                $serviceImage = '';
                                if($serviceUrls){
                                    $serviceImage= $this->checkPictureUrl($this->allControl['service_image_url'],$serviceUrls[0]);
                                }
                                $newRelateObj[]=[
                                    'type'=>3,
                                    'obj'=>$oneServiceMain['serviceid'],
                                    'image'=>$serviceImage
                                ];
                            }

                        }
                        if($albumIds){
                            $albumMainList = model('albummain')
                                ->where(['albumid'=>['in',$albumIds],'delflag'=>0])->select();
                            foreach($albumMainList as $oneAlbumMain){

                                $newRelateObj[]=[
                                    'type'=>5,
                                    'obj'=>$oneAlbumMain['albumid'],
                                    'image'=>$this->checkPictureUrl($this->allControl['album_image_url'],$oneAlbumMain['image_url'])
                                ];
                            }
                        }

                        $newBloglist[$oneBlogMain['blogid']]=[
                            'blogid'=>$oneBlogMain['blogid'],

                            'userid'=>$oneBlogMain['userid'],
                            'avatar'=>$newUserInfoList[$oneBlogMain['userid']]['avatar'],
                            'nickname'=>$newUserInfoList[$oneBlogMain['userid']]['nickname'],
                            'verify_state'=>$newUserInfoList[$oneBlogMain['userid']]['verify_state'],
                            'zhima_state'=>$newUserInfoList[$oneBlogMain['userid']]['zhima_state'],
                            'servicetypename'=>isset($newUserServicetypeList[$oneBlogMain['userid']])?
                                $newUserServicetypeList[$oneBlogMain['userid']]['servicetypename']:'',
                            'exp'=>isset($newUserServicetypeList[$oneBlogMain['userid']])?
                                $newUserServicetypeList[$oneBlogMain['userid']]['exp']:0,
                            'isfollow'=>isset($this->curUserInfo['userid'])?
                                (isset($newUserFollowList[$oneBlogMain['userid']])?1:
                                    ($oneBlogMain['userid']==$this->curUserInfo['userid']?2:0)):0,   //2是自己1已关注0未关注

                            'content'=>$oneBlogMain['content'],
                            'urls'=>$this->checkPictureUrl($this->allControl['blog_image_url'],array_slice($urls,0,3)),
                            'urls_counter'=>$urls?count($urls):0,

                            'comment_counter'=>isset($newBlogDataList[$oneBlogMain['blogid']])?
                                $newBlogDataList[$oneBlogMain['blogid']]['comment_counter']:0,
                            'favour_counter'=>isset($newBlogDataList[$oneBlogMain['blogid']])?
                                $newBlogDataList[$oneBlogMain['blogid']]['favour_counter']:0,
                            'be_collected_counter'=>isset($newBlogDataList[$oneBlogMain['blogid']])?
                                $newBlogDataList[$oneBlogMain['blogid']]['be_collected_counter']:0,
                            'be_supported_counter'=>isset($newBlogDataList[$oneBlogMain['blogid']])?
                                $newBlogDataList[$oneBlogMain['blogid']]['be_supported_counter']:0,

                            'relate_objs'=>$newRelateObj,
                            'createtime'=>$oneBlogMain['createtime']

                        ];
                    }


                    foreach($collectList as $oneCollect){
                        $data['blogList'][]=array_merge(['collectid'=>$oneCollect['collectid']],$newBloglist[$oneCollect['obj_id']]);
                    }
                    break;
            }

            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建收藏接口
     * @url     /collect/collectCreate
     * @method  POST
     * @version 1000
     * @params  obj_id '1' STRING 收藏对象id YES
     * @params  type 1 INT 收藏类型2用户3服务4活动5专辑6动态话题 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function collectCreate(){

        //返回结果
        $data = [];

        //获取接口参数
        $objId = input('request.obj_id',0);
        $type = input('request.type',2);


        //验证参数是否为空
        if(!in_array($type,[2,3,4,5,6])||$objId<=0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }


        try{

            $userCollectWhere = [
                'userid'=>$this->curUserInfo['userid'],
                'obj_id'=>$objId,
                'type'=>$type,
                'delflag'=>0
            ];
            $userCollect = model('usercollect')->where($userCollectWhere)->find();

            if($userCollect){
                $this->returndata( 14002, 'collect  exist', $this->curTime, $data);
            }

            switch ($type) {

                case 2:
                    $userInfo = model('userinfo')->where(['userid'=>$objId])->find();
                    if(!$userInfo){
                        $this->returndata( 14003, 'obj not  exist', $this->curTime, $data);
                    }

                    $saveUserData = [
                        'be_collected_counter'=>['exp','be_collected_counter+1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('userdata')->where(['userid'=>$objId])
                        ->update($saveUserData);

                    break;
                case 3:
                    $serviceMain = model('servicemain')->where(['serviceid'=>$objId])->find();
                    if(!$serviceMain){
                        $this->returndata( 14003, 'obj not  exist', $this->curTime, $data);
                    }

                    $saveServiceData = [
                        'be_collected_counter'=>['exp','be_collected_counter+1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('servicedata')->where(['serviceid'=>$objId])
                        ->update($saveServiceData);
                    break;
                case 4:
                    break;
                case 5:
                    $albumMain = model('albummain')->where(['albumid'=>$objId])->find();
                    if(!$albumMain){
                        $this->returndata( 14003, 'obj not  exist', $this->curTime, $data);
                    }

                    $saveAlbumData = [
                        'be_collected_counter'=>['exp','be_collected_counter+1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('albumdata')->where(['albumid'=>$objId])
                        ->update($saveAlbumData);
                    break;
                case 6:
                    $blogMain = model('blogmain')->where(['blogid'=>$objId])->find();
                    if(!$blogMain){
                        $this->returndata( 14003, 'obj not  exist', $this->curTime, $data);
                    }

                    $saveBlogData = [
                        'be_collected_counter'=>['exp','be_collected_counter+1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('blogdata')->where(['blogid'=>$objId])
                        ->update($saveBlogData);
                    break;

            }

            //创建收藏主要信息
            $newUserCollect = [
                'userid'        => $this->curUserInfo['userid'],
                'obj_id'       => $objId,
                'type'        => $type,
                'createtime'    => $this->curTime,
                'updatetime'    => $this->curTime,
                'delflag'       => 0

            ];
            $collectId = model('usercollect')->insertGetId($newUserCollect);


            //更新个人收藏数
            $saveUserData = [
                'collect_counter'=>['exp','collect_counter+1'],
                'updatetime'=>$this->curTime
            ];
            model('userdata')->where(['userid'=>$this->curUserInfo['userid']])
                ->update($saveUserData);


            $data = array(
                'collectid'=> $collectId
            );
            $this->returndata(10000, 'collect success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    删除自己的收藏
     * @url     /collect/collectDel
     * @method  POST
     * @version 1000
     * @params  collectid 1 INT 收藏id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function collectDel(){

        //返回结果
        $data = [];

        //获取接口参数
        $collectId = input('collectid',0);

        if($collectId<0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $userCollect = model('usercollect')
                ->where(['userid'=>$this->curUserInfo['userid'],'collectid'=>$collectId,'delflag'=>0])
                ->find();
            if(!$userCollect){
                $this->returndata( 14002, 'collect not exist', $this->curTime, $data);
            }
            model('usercollect')
                ->where(['userid'=>$this->curUserInfo['userid'],'collectid'=>$collectId,'delflag'=>0])
                ->update(['delflag'=>1]);

            $type = $userCollect['type'];
            $objId= $userCollect['obj_id'];

            switch ($type) {

                case 2:

                    $saveUserData = [
                        'be_collected_counter'=>['exp','be_collected_counter-1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('userdata')->where(['userid'=>$objId,'be_collected_counter'=>['gt',0]])
                        ->update($saveUserData);

                    break;
                case 3:

                    $saveServiceData = [
                        'be_collected_counter'=>['exp','be_collected_counter-1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('servicedata')->where(['serviceid'=>$objId,'be_collected_counter'=>['gt',0]])
                        ->update($saveServiceData);
                    break;
                case 4:
                    break;
                case 5:
                    $saveAlbumData = [
                        'be_collected_counter'=>['exp','be_collected_counter-1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('albumdata')->where(['albumid'=>$objId,'be_collected_counter'=>['gt',0]])
                        ->update($saveAlbumData);
                    break;
                case 6:
                    $saveBlogData = [
                        'be_collected_counter'=>['exp','be_collected_counter-1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('blogdata')->where(['blogid'=>$objId,'be_collected_counter'=>['gt',0]])
                        ->update($saveBlogData);
                    break;
            }

            //更新个人收藏数
            $saveUserData = [
                'collect_counter'=>['exp','collect_counter-1'],
                'updatetime'=>$this->curTime
            ];
            model('userdata')->where(['userid'=>$this->curUserInfo['userid'],'collect_counter'=>['gt',0]])
                ->update($saveUserData);

            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


}
