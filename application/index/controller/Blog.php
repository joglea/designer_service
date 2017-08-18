<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Blog
 *
 * @classdesc 话题动态接口类
 * @package app\index\controller
 */
class Blog extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    话题动态列表接口
     * @url     /blog/blogList
     * @method  GET
     * @version 1000
     * @params  type 1 INT 话题动态列表类型1关注2兴趣3我的4别人的5搜索 YES
     * @params  userid 0 STRING 用户id(type为4的时候才有用) NO
     * @params  content '' STRING 话题动态内容(type为5的时候才有用) NO
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function blogList(){

        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数
        $type = input('request.type',1,'intval');
        $content = input('request.content','');
        $userId = input('request.userid',0,'intval');
        $page = input('request.page',1,'intval');

        if($page<1){
            $page=1;
        }
        //验证参数是否为空
        if(!in_array($type,[1,2,3,4,5])  ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }
        if($type == 4 && $userId<=0){
            $this->returndata( 14001,  'params error1', $this->curTime, $data);
        }
        if($type == 5 && $content==''){
            $this->returndata( 14001,  'params error2', $this->curTime, $data);
        }

        try{

            switch ($type){
                case 1:

                    $followList = model('follow')->where(['userid'=>$this->curUserInfo['userid']])->select();
                    /*if(!$followList){
                        $this->returndata( 10000, 'follow list is empty', $this->curTime, $data);
                    }*/
                    $followUserIds = [$this->curUserInfo['userid']];
                    foreach($followList as $oneFollow){
                        $followUserIds[]=$oneFollow['followedid'];
                    }

                    $blogMainList = model('blogmain')->where(['userid'=>['in',$followUserIds],'delflag'=>0])
                        ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();

                    break;
                case 2:

                    $userLikeServiceTypeList = model('userlikeservicetype')
                        ->where(['userid'=>$this->curUserInfo['userid']])->select();
                    if(!$userLikeServiceTypeList){
                        $this->returndata( 10000, 'like servicetype list is empty', $this->curTime, $data);
                    }
                    $userLikeServiceTypeIds = [];
                    foreach($userLikeServiceTypeList as $oneUserLikeServiceType){
                        $userLikeServiceTypeIds[]=$oneUserLikeServiceType['servicetypeid'];
                    }
                    $serviceTypeList = model('servicetype')
                        ->where(['servicetypeid'=>['in',$userLikeServiceTypeIds],'delflag'=>0])
                        ->select();
                    $serviceTypeIds = [];
                    foreach($serviceTypeList as $oneServiceType){
                        $serviceTypeIds[]=$oneServiceType['servicetypeid'];
                    }

                    $on = 'jz_blogmain.userid=jz_userservicetype.userid';
                    $where=['jz_userservicetype.servicetypeid'=>['in',$serviceTypeIds]];
                    $field='*';
                    $userInfoWhere['is_default'] = 1;
                    $order = 'jz_blogmain.createtime desc';
                    $group = 'jz_blogmain.blogid';
                    $limit=(($page-1)*$pageSize).','.$pageSize;
                    $jointype='LEFT';
                    $blogMainList = model('blogmain')->joinUserServiceTypeByWhere($on,$where,$field,$order,
                        $group,$limit,$jointype);
                    //var_dump(model('blogmain')->getLastSql());exit;
                    break;
                case 3:
                    $userId = $this->curUserInfo['userid'];

                    $blogMainList = model('blogmain')->where(['userid'=>$userId,'delflag'=>0])
                        ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
                case 4:

                    $blogMainList = model('blogmain')->where(['userid'=>$userId,'delflag'=>0])
                        ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
                case 5:
                    //创建搜索日志 搜索类型  2用户  3服务 4活动  5专辑 6 动态
                    model('searchlog')->addSearchLog(6,$content);
                    $blogMainList = model('blogmain')->where(['content'=>['like','%'.$content.'%'],'delflag'=>0])
                        ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
            }


            if(!$blogMainList){
                $this->returndata( 10000, 'blog list is empty', $this->curTime, $data);
            }

            $blogIds = [];
            $userIds = [];
            foreach($blogMainList as $oneBlogMain){
                $blogIds[] = $oneBlogMain['blogid'];
                $userIds[] = $oneBlogMain['userid'];
            }
            $blogDataList = model('blogdata')->where(['blogid'=>['in',$blogIds]])->select();
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

                $newBloglist[]=[
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

            $data['blogList'] = $newBloglist;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建话题动态接口
     * @url     /blog/blogCreate
     * @method  POST
     * @version 1000
     * @params  content '内容' STRING 话题动态内容描述 YES
     * @params  type 1 INT 任务媒体文件类型1图2文3音4视 YES
     * @params  urls '["a.jpg","b.jpg"]' STRING 图音视文件地址的json串 YES
     * @params  relate_objs '[{"type":"5","obj":"10000148"}]' STRING 关联对象串 YES
     * @params  officialtaskid 0 INT 关联的活动id,不关联就传0 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function blogCreate(){

        //返回结果
        $data = [];

        //获取接口参数
        $content = input('request.content','');
        $type = input('request.type',1);
        $urls = input('request.urls','');

        $relateObjs = input('request.relate_objs','[]');

        $officialTaskId = input('request.officialtaskid',0);

        //验证参数是否为空
        if(!check_string_length($content,1,2000)||!in_array($type,[1,2,3,4])||
            $urls==''||$officialTaskId<0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        if(!$relateObjs){
            $relateArr = json_decode($relateObjs,true);
            $relateArr = array_slice($relateArr,0,4);
            $relateObjs = json_encode($relateArr);
        }

        try{

            $blogMainWhere = [
                'userid'=>$this->curUserInfo['userid'],
                'content'=>$content,
                'type'=>$type,
                'urls'=>$urls,
                'relate_objs'=>$relateObjs,
                'officialtaskid'=>$officialTaskId,
                'delflag'=>0
            ];
            $blogMain = model('blogmain')->where($blogMainWhere)->find();

            if($blogMain){
                $this->returndata( 14002, 'blog  exist', $this->curTime, $data);
            }
            else{
                //创建话题动态主要信息
                $newBlogMain = [
                    'userid'        => $this->curUserInfo['userid'],
                    'content'       => $content,
                    'type'        => $type,
                    'urls'        => $urls,

                    'relate_objs'   => $relateObjs,
                    'officialtaskid'=> $officialTaskId,
                    'createtime'    => $this->curTime,
                    'delflag'       => 0

                ];
                $blogId = model('blogmain')->insertGetId($newBlogMain);

                //创建话题动态统计信息
                $newBlogData = [
                    'blogid'     => $blogId,
                    'updatetime'    => $this->curTime
                ];
                model('blogdata')->insertGetId($newBlogData);

                //更新个人话题动态数
                $saveUserData = [
                    'blog_counter'=>['exp','blog_counter+1'],
                    'updatetime'=>$this->curTime
                ];
                model('userdata')->where(['userid'=>$this->curUserInfo['userid']])
                    ->update($saveUserData);
            }

            $data = array(
                'blogid'=> $blogId
            );
            $this->returndata(10000, '创建成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    话题动态发布时选择关联服务或专辑列表接口
     * @url     /blog/relateObjsList
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function relateObjsList(){
        //返回结果
        $data = [];
        $userId = $this->curUserInfo['userid'];

        try{
            $albumMainList = model('albummain')->where(['userid'=>$userId,'delflag'=>0])
                ->order('createtime desc')->select();
            $albumIds = [];
            $userIds = [];
            foreach($albumMainList as $oneAlbumMain){
                $albumIds[] = $oneAlbumMain['albumid'];
                $userIds[] = $oneAlbumMain['userid'];
            }
            $albumDataList = model('albumdata')->where(['albumid'=>['in',$albumIds]])->select();
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

            $newAlbumlist = [];
            foreach($albumMainList as $oneAlbumMain){
                $newAlbumlist[]=[
                    'albumid'=>$oneAlbumMain['albumid'],
                    'name'=>$oneAlbumMain['name'],
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
            $data['albumList'] = $newAlbumlist;

            $serviceMainWhere=[
                'userid'=>$userId,
                'state'=>1,
                'delflag'=>0
            ];

            $order = 'createtime desc ';
            $serviceMainList = model('servicemain')->where($serviceMainWhere)->order($order)
                ->select();
            $serviceIds = [];
            foreach($serviceMainList as $oneServiceMain){
                $serviceIds[]=$oneServiceMain['serviceid'];
            }
            if(!$serviceIds){
                $serviceIds='';
            }
            $serviceDataList = model('servicedata')->where(['serviceid'=>['in',$serviceIds],'delflag'=>0])
                ->select();
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

            $newServicelist = [];
            foreach($serviceMainList as $oneServiceMain){
                //var_dump($oneServiceMain);
                $urls = json_decode($oneServiceMain['urls'],true);

                $newServicelist[]=[
                    'serviceid'     => $oneServiceMain['serviceid'],
                    'urls'          => $urls?$this->checkPictureUrl($this->allControl['service_image_url'],$urls[0]):'',
                    'title'         => $oneServiceMain['title'],
                    'service_num'   => isset($newServiceDataList[$oneServiceMain['serviceid']])?
                        $newServiceDataList[$oneServiceMain['serviceid']]['service_num']:0,
                    'star_score'    => isset($newServiceDataList[$oneServiceMain['serviceid']])?
                        $newServiceDataList[$oneServiceMain['serviceid']]['star_score']:0,

                ];
            }

            $data['serviceList'] = $newServicelist;


            $this->returndata(10000, 'get success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }



    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    查看话题动态详情
     * @url     /blog/blogView
     * @method  GET
     * @version 1000
     * @params  blogid 1 INT 话题动态id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function blogView(){

        //返回结果
        $data = [];

        //获取接口参数
        $blogId = input('blogid');

        if($blogId <= 0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $blogMain = model('blogmain')->where(['blogid'=>$blogId,'delflag'=>0])->find();
            $blogData = model('blogdata')->where(['blogid'=>$blogId])->find();

            if(!$blogMain || !$blogData ){
                $this->returndata( 14002, 'blog not exist', $this->curTime, $data);
            }

            $userInfo = model('userinfo')->where(['userid'=>$blogMain['userid']])->find();
            $userHx = model('userhx')->where(['userid'=>$blogMain['userid']])->find();

            $allControl = $this->getAllControl();

            $relateObjs = json_decode($blogMain['relate_objs'],true);
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
            //动态关联的服务和专辑
            $newRelateObj = [];
            if($serviceIds){
                $serviceMainList = model('servicemain')
                    ->where(['serviceid'=>['in',$serviceIds],'state'=>1,'delflag'=>0])->select();
                $serviceDataList = model('servicedata')
                    ->where(['serviceid'=>['in',$serviceIds],'delflag'=>0])->select();
                $newServiceDataList = [];
                foreach($serviceDataList as $oneServiceData){
                    $newServiceDataList[$oneServiceData['serviceid']]=$oneServiceData;
                }


                foreach($serviceMainList as $oneServiceMain){
                    $serviceUrls = json_decode($oneServiceMain['urls'],true);
                    $serviceImage = '';
                    if($serviceUrls){
                        $serviceImage= $this->checkPictureUrl($this->allControl['service_image_url'],$serviceUrls[0]);
                    }
                    $newRelateObj[]=[
                        'type'=>3,
                        'obj'=>$oneServiceMain['serviceid'],
                        'title'=>$oneServiceMain['title'],
                        'image'=>$serviceImage,
                        'star_score'=>isset($newServiceDataList[$oneServiceMain['serviceid']])?
                            $newServiceDataList[$oneServiceMain['serviceid']]['star_score']:0,
                        'counter'=>isset($newServiceDataList[$oneServiceMain['serviceid']])?
                            $newServiceDataList[$oneServiceMain['serviceid']]['service_num']:0
                    ];
                }
            }
            if($albumIds){
                $albumMainList = model('albummain')
                    ->where(['albumid'=>['in',$albumIds],'delflag'=>0])->select();
                $albumDataList = model('albumdata')
                    ->where(['albumid'=>['in',$albumIds]])->select();
                $newAlbumDataList = [];
                foreach($albumDataList as $oneAlbumData){
                    $newAlbumDataList[$oneAlbumData['albumid']]=$oneAlbumData;
                }

                foreach($albumMainList as $oneAlbumMain){

                    $newRelateObj[]=[
                        'type'=>5,
                        'obj'=>$oneAlbumMain['albumid'],
                        'title'=>$oneAlbumMain['name'],
                        'image'=>$this->checkPictureUrl($this->allControl['album_image_url'],$oneAlbumMain['image_url']),
                        'star_score'=>0,
                        'counter'=>isset($newAlbumDataList[$oneAlbumMain['albumid']])?
                            $newAlbumDataList[$oneAlbumMain['albumid']]['be_supported_counter']:0
                    ];
                }
            }
            //话题打赏用户列表
            $blogSupportList = model('blogsupport')->where(['blogid'=>$blogId,'delflag'=>0])
                ->order('createtime desc')->select();

            $blogSupportUserIds = [];
            foreach($blogSupportList as $oneBlogSupport){
                $blogSupportUserIds[]=$oneBlogSupport['userid'];
            }

            $finalBlogSupportUserList = [];
            if($blogSupportUserIds){
                $blogSupportUserList = model('userinfo')->where(['userid'=>['in',$blogSupportUserIds]])->select();
                $newBlogSupportUserList = [];
                foreach($blogSupportUserList as $oneBlogSupportUser){
                    $newBlogSupportUserList[$oneBlogSupportUser['userid']]=$oneBlogSupportUser;
                }
                foreach($blogSupportUserIds as $oneBlogSupportUserId){
                    $finalBlogSupportUserList[]=[
                        'userid'=>$oneBlogSupportUserId,
                        'avatar'=>$this->checkpictureurl($allControl['avatar_url'],
                            $newBlogSupportUserList[$oneBlogSupportUserId]['avatar'])
                    ];
                }
            }


            $data['blog']=[
                'blogid'            => $blogMain['blogid'],
                'content'           => $blogMain['content'],
                'type'              => $blogMain['type'],
                'urls'              => $this->checkpictureurl($allControl['blog_image_url'],json_decode($blogMain['urls'],true)),
                'relate_objs'       => $newRelateObj,
                'officialtaskid'    => $blogMain['officialtaskid'],
                'is_top'            => $blogMain['is_top'],
                'is_hot'            => $blogMain['is_hot'],
                'createtime'        => $blogMain['createtime'],

                'comment_counter'   => $blogData['comment_counter'],
                'favour_counter'    => $blogData['favour_counter'],
                'share_counter'     => $blogData['share_counter'],
                'read_counter'      => $blogData['read_counter'],
                'be_collected_counter'      => $blogData['be_collected_counter'],
                'be_supported_counter'      => $blogData['be_supported_counter'],
                'be_supported_list'=>$finalBlogSupportUserList,
                'be_reported_counter'    => $blogData['be_reported_counter'],
            ];

            $avatar = $this->checkpictureurl($allControl['avatar_url'],$userInfo['avatar']);
            $userServiceType = model('userservicetype')->where(['userid'=>$blogMain['userid'],'delflag'=>0])
                ->order('is_default desc,createtime asc')->find();

            $this->getAllServiceType();
            $serviceTypes=[
                'servicetypeid'=>$userServiceType['servicetypeid'],
                'name'=>$this->allServiceType[$userServiceType['servicetypeid']],
                'exp'=>$userServiceType['exp'],
            ];


            $followList = model('follow')->where(['userid'=>['in',$blogMain['userid']]])->select();
            $newUserFollowList = [];
            foreach($followList as $oneFollow){
                $newUserFollowList[$oneFollow['followedid']]=$oneFollow;
            }
            $data['user']=[
                'userid'        => $blogMain['userid'],
                'avatar'        => $avatar,
                'nickName'      => $userInfo['nickname'],
                'sex'           => $userInfo['sex'],
                'verify_state'  => $userInfo['verify_state'],
                'zhima_code'    => model('userinfo')->getZhimaState($userInfo['zhima_code']),
                "identity"      => $serviceTypes,
                'hxid'          => $userHx['hx_id'],           //环信id
                'hxpa'          => $userHx['hx_pass'],          //环信密码
                'isfollow'=>isset($this->curUserInfo['userid'])?
                    (isset($newUserFollowList[$blogMain['userid']])?1:
                        ($blogMain['userid']==$this->curUserInfo['userid']?2:0)):0,   //2是自己1已关注0未关注
            ];


            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    话题动态评论列表接口
     * @url     /blog/blogCommentList
     * @method  GET
     * @version 1000
     * @params  blogid 1 INT 话题动态ID YES
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function blogCommentList(){
        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数
        $blogId = input('request.blogid',0,'intval');
        $page = input('request.page',1,'intval');
        //验证参数是否为空
        if($blogId<=0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        if($page<1){
            $page=1;
        }

        try{

            //获取动态评论列表
            $blogCommentList = model('blogcomment')->where(['blogid'=>$blogId,'delflag'=>0])
                ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();

            if(!$blogCommentList){
                $this->returndata( 10000, 'comment list is empty', $this->curTime, $data);
            }

            $this->getAllControl();

            $reCommentIds = [];
            $userIds = [];
            foreach($blogCommentList as $oneBlogComment){
                if($oneBlogComment['recommentid']!=0) {
                    $reCommentIds[] = $oneBlogComment['recommentid'];
                }
                $userIds[] = $oneBlogComment['userid'];
                $userIds[] = $oneBlogComment['blog_userid'];
                if($oneBlogComment['recomment_userid']!=0){
                    $userIds[] = $oneBlogComment['recomment_userid'];
                }
            }
            if(!$reCommentIds){
                $reCommentIds='';
            }
            //获取回复评论的原评论信息
            $reCommentList = model('blogcomment')->where(['commentid'=>['in',$reCommentIds]])->select();
            $newReCommentList = [];
            foreach($reCommentList as $oneReComment){
                $newReCommentList[$oneReComment['commentid']] = [
                    'content'=>$oneReComment['content'],
                    'picture'=>$this->checkPictureUrl($this->allControl['comment_image_url'],$oneReComment['picture']),
                ];
            }

            if(!$userIds){
                $userIds='';
            }
            //获取所有用户信息
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

            $newBlogCommentList = [];
            foreach($blogCommentList as $oneBlogComment){

                $newBlogCommentList[]=[
                    'commentid'=>$oneBlogComment['commentid'],
                    'content'=>$oneBlogComment['content'],
                    //'picture'=>$this->checkPictureUrl($this->allControl['comment_image_url'],$oneReComment['picture']),
                    'favour_counter'=>$oneBlogComment['favour_counter'],

                    'userid'=>$oneBlogComment['userid'],
                    'avatar'=>$newUserInfoList[$oneBlogComment['userid']]['avatar'],
                    'nickname'=>$newUserInfoList[$oneBlogComment['userid']]['nickname'],
                    'verify_state'=>$newUserInfoList[$oneBlogComment['userid']]['verify_state'],
                    'zhima_state'=>$newUserInfoList[$oneBlogComment['userid']]['zhima_state'],

                    'recomment_nickname'=>$oneBlogComment['recomment_userid']>0?
                        $newUserInfoList[$oneBlogComment['recomment_userid']]['nickname']:'',
                    'recomment_content'=>$oneBlogComment['recommentid']>0?
                        $newReCommentList[$oneBlogComment['recommentid']]['content']:'',
                    //'recomment_picture'=>$newReCommentList['recommentid']['picture'],
                    'createtime'=>$oneBlogComment['createtime'],
                    //'isself'=>$oneBlogComment['userid']==$this->curUserInfo['userid']?1:0   //1是自己0不是自己
                ];
            }

            $data['commentlist'] = $newBlogCommentList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建话题动态接口
     * @url     /blog/blogCommentCreate
     * @method  POST
     * @version 1000
     * @params  blogid 1 INT 话题动态ID YES
     * @params  content '内容' STRING 话题动态内容描述 YES
     * @params  recommentid 0 INT 被回复的评论id,普通评论传0 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function blogCommentCreate(){

        //返回结果
        $data = [];

        //获取接口参数
        $blogId = input('request.blogid',0);
        $content = input('request.content','');
        $reCommentId = input('request.recommentid',0);

        //验证参数是否为空
        if($blogId<=0||!check_string_length($content,1,140)||$reCommentId<0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $blogMainWhere = [
                'blogid'=>$blogId,
                'delflag'=>0
            ];
            $blogMain = model('blogmain')->where($blogMainWhere)->find();

            if(!$blogMain){
                $this->returndata( 14002, 'blog not exist', $this->curTime, $data);
            }
            $reComment = [];
            if($reCommentId>0){
                $reComment = model('blogcomment')->where(['commentid'=>$reCommentId,'blogid'=>$blogId,'delflag'=>0])->find();
                if(!$reComment){
                    $this->returndata( 14003, 'recomment not exist', $this->curTime, $data);
                }
            }

            //创建话题动态主要信息
            $newBlogComment = [
                'userid'        => $this->curUserInfo['userid'],
                'blogid'    =>$blogId,
                'blog_userid'=>$blogMain['userid'],
                'recommentid'=>$reCommentId,
                'recomment_userid'=>$reComment?$reComment['userid']:0,
                'content'       => $content,
                'favour_counter'=>0,
                'be_reported_counter'=>0,
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0
            ];
            $blogCommentId = model('blogcomment')->insertGetId($newBlogComment);


            //更新话题动态评论数
            $saveBlogData = [
                'comment_counter'=>['exp','comment_counter+1'],
                'updatetime'=>$this->curTime
            ];
            model('blogdata')->where(['blogid'=>$blogId])
                ->update($saveBlogData);


            //更新个人发表评论数
            $saveUserData = [
                'Scomment_counter'=>['exp','Scomment_counter+1'],
                'updatetime'=>$this->curTime
            ];
            model('userdata')->where(['userid'=>$this->curUserInfo['userid']])
                ->update($saveUserData);


            $data = array(
                'blogcommentid'=> $blogCommentId
            );
            $this->returndata(10000, '创建成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    删除话题动态评论接口
     * @url     /blog/blogCommentDel
     * @method  POST
     * @version 1000
     * @params  commentid 1 INT 话题动态下评论ID YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function blogCommentDel(){

        //返回结果
        $data = [];

        //获取接口参数
        $commentId = input('request.commentid',0);

        //验证参数是否为空
        if($commentId<=0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{


            $blogComment = model('blogcomment')
                ->where(['commentid'=>$commentId,'userid'=>$this->curUserInfo['userid'],'delflag'=>0])->find();
            if(!$blogComment){
                $this->returndata( 14003, 'comment not exist', $this->curTime, $data);
            }



            $ret = model('blogcomment')->where(['commentid'=>$commentId,'userid'=>$this->curUserInfo['userid']])
                ->update(['updatetime'=>$this->curTime,'delflag'=>1]);

            if($ret){
                //更新话题动态评论数
                $saveBlogData = [
                    'comment_counter'=>['exp','comment_counter-1'],
                    'updatetime'=>$this->curTime
                ];
                model('blogdata')->where(['blogid'=>$blogComment['blogid'],'comment_counter'=>['gt',0]])
                    ->update($saveBlogData);


                //更新个人发表评论数
                $saveUserData = [
                    'Scomment_counter'=>['exp','Scomment_counter-1'],
                    'updatetime'=>$this->curTime
                ];
                model('userdata')->where(['userid'=>$this->curUserInfo['userid'],'Scomment_counter'=>['gt',0]])
                    ->update($saveUserData);


                $this->returndata(10000, '删除成功', $this->curTime, $data);
            }
            else{
                $this->returndata(10000, '已删除', $this->curTime, $data);
            }


        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    删除自己的话题动态
     * @url     /blog/blogDel
     * @method  POST
     * @version 1000
     * @params  blogid 1 INT 话题动态id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function blogDel(){

        //返回结果
        $data = [];

        //获取接口参数
        $blogId = input('blogid',0);

        if($blogId<0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $blogMain = model('blogmain')
                ->where(['userid'=>$this->curUserInfo['userid'],'blogid'=>$blogId,'delflag'=>0])
                ->find();
            if(!$blogMain){
                $this->returndata( 14002, 'blog not exist', $this->curTime, $data);
            }
            model('blogmain')
                ->where(['userid'=>$this->curUserInfo['userid'],'blogid'=>$blogId,'delflag'=>0])
                ->update(['delflag'=>1]);
            //更新个人话题动态数
            $saveUserData = [
                'blog_counter'=>['exp','blog_counter-1'],
                'updatetime'=>$this->curTime
            ];
            model('userdata')->where(['userid'=>$this->curUserInfo['userid'],'blog_counter'=>['gt',0]])
                ->update($saveUserData);

            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


}
