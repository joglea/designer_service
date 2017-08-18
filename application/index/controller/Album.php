<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Album
 *
 * @classdesc 相册专辑接口类
 * @package app\index\controller
 */
class Album extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    相册专辑列表接口
     * @url     /album/albumList
     * @method  GET
     * @version 1000
     * @params  type 1 INT 相册专辑列表类型1我的2别人的3搜索 YES
     * @params  userid 0 STRING 用户id(type为2的时候才有用) NO
     * @params  name '' STRING 相册专辑名称(type为3的时候才有用) NO
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function albumList(){

        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数
        $type = input('request.type',1,'intval');
        $userId = input('request.userid',0,'intval');
        $name = input('request.name','');
        $page = input('request.page',1,'intval');

        if($page<1){
            $page=1;
        }
        //验证参数是否为空
        if(!in_array($type,[1,2,3])  ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }
        if($type == 2 && $userId<=0){
            $this->returndata( 14001,  'params error1', $this->curTime, $data);
        }
        if($type == 3 && $name==''){
            $this->returndata( 14001,  'params error2', $this->curTime, $data);
        }

        try{

            switch ($type){

                case 1:
                    $userId = $this->curUserInfo['userid'];

                    $albumMainList = model('albummain')->where(['userid'=>$userId,'delflag'=>0])
                        ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
                case 2:

                    $albumMainList = model('albummain')->where(['userid'=>$userId,'delflag'=>0])
                        ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
                case 3:
                    //创建搜索日志  搜索类型  2用户  3服务 4活动  5专辑 6 动态
                    model('searchlog')->addSearchLog(5,$name);

                    $albumTagList = model('albumtag')->where(['tagname'=>['like','%'.$name.'%'],'delflag'=>0])
                        ->order('favour_counter desc')->select();
                    $albumIds =[];
                    foreach($albumTagList as $oneAlbumTag){
                        if(!in_array($oneAlbumTag['albumid'],$albumIds)){
                            $albumIds[]=$oneAlbumTag['albumid'];
                        }
                    }
                    if(!$albumIds){
                        $albumIds='';
                    }

                    $albumMainList = model('albummain')->where(['albumid'=>['in',$albumIds],'delflag'=>0])
                        ->order('createtime desc')->limit((($page-1)*$pageSize).','.$pageSize)->select();
                    break;
            }


            if(!$albumMainList){
                $this->returndata( 10000, 'album list is empty', $this->curTime, $data);
            }

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

                $newAlbumlist[]=[
                    'albumid'=>$oneAlbumMain['albumid'],
                    'name'=>$oneAlbumMain['name'],

                    'userid'=>$oneAlbumMain['userid'],
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
                            ($oneAlbumMain['userid']==$this->curUserInfo['userid']?2:0)):0,   //2是自己1已关注0未关注

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
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建相册专辑接口
     * @url     /album/albumCreate
     * @method  POST
     * @version 1000
     * @params  name '名称' STRING 相册专辑名称 YES
     * @params  surface_image_url "a.jpg" STRING 封面图地址 YES
     * @params  image_urls [{"url":"a.jpg","lock":0}] STRING 相册图地址json串,lock是否上锁为0否1是 YES
     * @params  price '0.01' STRING 专辑售价，为0表示免费 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function albumCreate(){

        //返回结果
        $data = [];

        //获取接口参数
        $name = input('request.name','');
        $surfaceImageUrl = input('request.surface_image_url','');
        $imageUrls = input('request.image_urls','[]');
        $imageArr = json_decode($imageUrls,true);
        $price = input('request.price',0);


        //验证参数是否为空
        if(!check_string_length($name,1,15)||$surfaceImageUrl==''||!$imageUrls||$price<0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }
        if($price == 0){
            $hasLock = 0 ;
            foreach($imageArr as $image){
                if($image['lock']==1){
                    $hasLock = 1;
                }
            }
            if($hasLock){
                $this->returndata( 14002,  'free album can not lock', $this->curTime, $data);
            }
        }


        try{


            $albumMainWhere = [
                'userid'=>$this->curUserInfo['userid'],
                'name'=>$name,
                'delflag'=>0
            ];
            $albumMain = model('albummain')->where($albumMainWhere)->find();

            if($albumMain){
                $this->returndata( 14002, 'album  exist', $this->curTime, $data);
            }
            else{
                //创建相册专辑主要信息
                $newAlbumMain = [
                    'userid'        => $this->curUserInfo['userid'],
                    'name'          => $name,
                    'image_url'     => $surfaceImageUrl,
                    'price'         => $price,
                    'createtime'    => $this->curTime,
                    'delflag'       => 0

                ];
                $albumId = model('albummain')->insertGetId($newAlbumMain);

                //创建相册专辑图片
                $newAlbumImages = [];
                foreach($imageArr as $image){
                    $imageUrl = $image["url"];
                    $imageLock = $image["lock"];

                    $newAlbumImages[]=[
                        'albumid'       => $albumId,
                        'image_url'     => $imageUrl,
                        'lock_flag'     => $imageLock==0?0:1,
                        'createtime'    => $this->curTime,
                        'delflag'       => 0
                    ];
                }



                //创建相册专辑图片
                $newAlbumImages = [];
                foreach($imageArr as $image){
                    $imageUrl = $image["url"];
                    $imageLock = $image["lock"];

                    $newAlbumImages[]=[
                        'albumid'       => $albumId,
                        'image_url'     => $imageUrl,
                        'lock_flag'     => $imageLock==0?0:1,
                        'createtime'    => $this->curTime,
                        'delflag'       => 0
                    ];
                }
                model('albumimage')->insertAll($newAlbumImages);


                //创建相册专辑统计信息
                $newAlbumData = [
                    'albumid'     => $albumId,
                    'updatetime'    => $this->curTime
                ];
                model('albumdata')->insertGetId($newAlbumData);

                //更新个人相册专辑数
                $saveUserData = [
                    'album_counter'=>['exp','album_counter+1'],
                    'updatetime'=>$this->curTime
                ];
                model('userdata')->where(['userid'=>$this->curUserInfo['userid']])
                    ->update($saveUserData);
            }

            $data = array(
                'albumid'=> $albumId
            );
            $this->returndata(10000, '创建成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    修改相册专辑接口
     * @url     /album/albumEdit
     * @method  POST
     * @version 1000
     * @params  albumid 1 INT 相册专辑id YES
     * @params  name '名称' STRING 相册专辑名称 YES
     * @params  surface_image_url "a.jpg" STRING 封面图地址,传空就不修改 NO
     * @params  price '0.01' STRING 专辑售价，为0表示免费 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function albumEdit(){

        //返回结果
        $data = [];

        //获取接口参数
        $albumId = input('albumid');
        $name = input('request.name','');
        $surfaceImageUrl = input('request.surface_image_url','');
        $price = input('request.price',0);

        //验证参数是否为空
        if(!check_string_length($name,1,15)||$price<0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{


            $albumMainWhere = [
                'userid'=>$this->curUserInfo['userid'],
                'albumid'=>$albumId,
                'delflag'=>0
            ];
            $albumMain = model('albummain')->where($albumMainWhere)->find();

            if(!$albumMain){
                $this->returndata( 14002, 'album not exist', $this->curTime, $data);
            }
            else{
                $newAlbumMain['price']=$price;
                $newAlbumMain['name']=$name;

                if($surfaceImageUrl!=''){
                    $newAlbumMain['image_url']=$surfaceImageUrl;
                }
                model('albummain')->where($albumMainWhere)->update($newAlbumMain);
            }

            $this->returndata(10000, 'edit success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建相册专辑的图片接口
     * @url     /album/albumImageCreate
     * @method  POST
     * @version 1000
     * @params  albumid 1 INT 相册专辑id YES
     * @params  image_urls [{"url":"a.jpg","lock":0}] STRING 相册图地址json串,lock是否上锁为0否1是 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function albumImageCreate(){

        //返回结果
        $data = [];

        //获取接口参数
        $albumId = input('albumid');
        $imageUrls = input('request.image_urls','[]');
        $imageArr = json_decode($imageUrls,true);

        //验证参数是否为空
        if(!$imageUrls||$albumId<0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $albumMainWhere = [
                'albumid'=>$albumId,
                'userid'=>$this->curUserInfo['userid'],
                'delflag'=>0
            ];
            $albumMain = model('albummain')->where($albumMainWhere)->find();

            if(!$albumMain){
                $this->returndata( 14002, 'album not exist', $this->curTime, $data);
            }

            //创建相册专辑图片
            $newAlbumImages = [];
            foreach($imageArr as $image){

                $imageUrl = $image["url"];
                $imageLock = $image["lock"];

                $newAlbumImages[]=[
                    'albumid'       => $albumId,
                    'image_url'     => $imageUrl,
                    'lock_flag'     => $imageLock==0?0:1,
                    'createtime'    => $this->curTime,
                    'delflag'       => 0
                ];
            }
            model('albumimage')->insertAll($newAlbumImages);


            $this->returndata(10000, 'create success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    删除相册专辑的图片接口
     * @url     /album/albumImageDel
     * @method  POST
     * @version 1000
     * @params  albumimageid 1 INT 相册专辑id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function albumImageDel(){

        //返回结果
        $data = [];

        //获取接口参数
        $albumImageId = input('albumimageid');

        //验证参数是否为空
        if($albumImageId<0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $albumImageWhere = [
                'albumimageid'=>$albumImageId,
                'delflag'=>0
            ];
            $albumImage = model('albumimage')->where($albumImageWhere)->find();

            if(!$albumImage){
                $this->returndata( 14002, 'albumimage not exist', $this->curTime, $data);
            }

            $albumMainWhere = [
                'albumid'=>$albumImage['albumid'],
                'userid'=>$this->curUserInfo['userid'],
                'delflag'=>0
            ];
            $albumMain = model('albummain')->where($albumMainWhere)->find();

            if(!$albumMain){
                $this->returndata( 14002, 'album not exist', $this->curTime, $data);
            }

            //删除相册专辑图片
            model('albumimage')->where($albumImageWhere)->update(['delflag'=>1]);

            $this->returndata(10000, 'del success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建相册专辑的标签接口
     * @url     /album/albumTagCreate
     * @method  POST
     * @version 1000
     * @params  albumid 1 INT 相册专辑id YES
     * @params  tagname '标签名称' STRING 标签名称 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function albumTagCreate(){

        //返回结果
        $data = [];

        //获取接口参数
        $albumId = input('albumid');
        $tagName = input('request.tagname','');

        //验证参数是否为空
        if(!check_string_length($tagName,1,15)||$albumId<0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $albumTagWhere = [
                'albumid'=>$albumId,
                'tagname'=>$tagName,
                'delflag'=>0
            ];
            $albumTag = model('albumtag')->where($albumTagWhere)->find();

            if($albumTag){
                $this->returndata( 14002, 'album exist', $this->curTime, $data);
            }
            else{
                //创建相册专辑标签
                $newAlbumTag = [
                    'albumid'       => $albumId,
                    'userid'        => $this->curUserInfo['userid'],
                    'tagname'       => $tagName,
                    'delflag'       => 0
                ];
                model('albumtag')->insertGetId($newAlbumTag);
            }


            $this->returndata(10000, '创建成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    相册专辑的标签点赞接口
     * @url     /album/albumTagFavour
     * @method  POST
     * @version 1000
     * @params  albumid 1 INT 相册专辑id YES
     * @params  tagname '标签名称' STRING 标签名称 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function albumTagFavour(){

        //返回结果
        $data = [];

        //获取接口参数
        $albumId = input('albumid');
        $tagName = input('request.tagname','');

        //验证参数是否为空
        if(!check_string_length($tagName,1,15)||$albumId<0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $albumTagFavourWhere = [
                'userid'=>$this->curUserInfo['userid'],
                'albumid'=>$albumId,
                'tagname'=>$tagName,
            ];
            $albumTagFavour = model('albumtagfavour')->where($albumTagFavourWhere)->find();

            if($albumTagFavour){
                //删除点赞记录
                model('albumtagfavour')->where($albumTagFavourWhere)->delete();
                //更新相册单个标签点赞数
                model('albumtag')->where(['albumid'=>$albumId,'tagname'=>$tagName,'favour_counter'=>['gt',0]])
                    ->update(['favour_counter'=>['exp','favour_counter-1']]);
                //更新相册总点赞数
                model('albumdata')->where(['albumid'=>$albumId,'total_favour_counter'=>['gt',0]])
                    ->update(['total_favour_counter'=>['exp','total_favour_counter-1'],'updatetime'=>$this->curTime]);

                $data['is_favour']=0;
                $this->returndata(10000, 'cancel favour success', $this->curTime, $data);
            }
            else{
                //创建相册专辑标签点赞记录
                $newAlbumFavour = [
                    'albumid'       => $albumId,
                    'userid'        => $this->curUserInfo['userid'],
                    'tagname'       => $tagName,
                    'createtime'    => $this->curTime,
                ];

                model('albumtagfavour')->insertGetId($newAlbumFavour);

                //更新相册单个标签点赞数
                model('albumtag')->where(['albumid'=>$albumId,'tagname'=>$tagName])
                    ->update(['favour_counter'=>['exp','favour_counter+1']]);
                //更新相册总点赞数
                model('albumdata')->where(['albumid'=>$albumId])
                    ->update(['total_favour_counter'=>['exp','total_favour_counter+1'],'updatetime'=>$this->curTime]);

                $data['is_favour']=1;
                $this->returndata(10000, 'favour success', $this->curTime, $data);
            }

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    查看相册专辑详情
     * @url     /album/albumView
     * @method  GET
     * @version 1000
     * @params  albumid 1 INT 相册专辑id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function albumView(){

        //返回结果
        $data = [];

        //获取接口参数
        $albumId = input('albumid');

        if($albumId <= 0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $albumMain = model('albummain')->where(['albumid'=>$albumId,'delflag'=>0])->find();
            $albumData = model('albumdata')->where(['albumid'=>$albumId])->find();

            if(!$albumMain || !$albumData ){
                $this->returndata( 14002, 'album not exist', $this->curTime, $data);
            }

            $userInfo = model('userinfo')->where(['userid'=>$albumMain['userid']])->find();
            $userHx = model('userhx')->where(['userid'=>$albumMain['userid']])->find();

            //支付打赏状态 0免费未打赏1免费已打赏2付费未支付3付费已支付未打赏4已支付已打赏
            $paySupportType = 0;
            //是否打赏
            $albumPaySupport1 = model('albumpaysupport')->where(['type'=>1,'userid'=>$this->curUserInfo['userid']])->find();
            //是否支付
            $albumPaySupport2 = model('albumpaysupport')->where(['type'=>2,'userid'=>$this->curUserInfo['userid']])->find();

            if($albumMain['price']>0){
                if($albumPaySupport2){
                    if($albumPaySupport1){
                        $paySupportType = 4;
                    }
                    else{
                        $paySupportType = 3;
                    }
                }
                else{
                    $paySupportType = 2;
                }

            }
            else{
                if($albumPaySupport1){
                    $paySupportType = 1;
                }
            }

            $allControl = $this->getAllControl();

            $coverImageUrl = $this->checkpictureurl($allControl['album_image_url'],$albumMain['image_url']);
            $data['album']=[
                'albumid'            => $albumMain['albumid'],
                'name'              => $albumMain['name'],
                'paysupporttype'    => $paySupportType, //0免费未打赏1免费已打赏2付费未支付3付费已支付未打赏4已支付已打赏
                'image_url'         => $coverImageUrl,
                'price'    => $albumMain['price'],
                'createtime'        => $albumMain['createtime'],

                'total_favour_counter'   => $albumData['total_favour_counter'],
                'pay_counter'    => $albumData['pay_counter'],
                'be_collected_counter'     => $albumData['be_collected_counter'],
                'be_supported_counter'     => $albumData['be_supported_counter'],
                'read_counter'      => $albumData['read_counter'],
                //'be_reported_counter'    => $albumData['be_reported_counter'],
            ];

            //专辑的图片列表
            $albumImageList = model('albumimage')->where(['albumid'=>$albumId,'delflag'=>0])
                ->order('createtime desc')->select();
            $data['album']['albumimages']=[$coverImageUrl];
            foreach($albumImageList as $oneAlbumImage){
                $data['album']['albumimages'][]=$this->checkpictureurl($allControl['album_image_url'],$oneAlbumImage['image_url']);
            }


            $avatar = $this->checkpictureurl($allControl['avatar_url'],$userInfo['avatar']);
            $userServiceType = model('userservicetype')->where(['userid'=>$albumMain['userid'],'delflag'=>0])
                ->order('is_default desc,createtime asc')->find();

            $this->getAllServiceType();
            $serviceTypes=[
                'servicetypeid'=>$userServiceType['servicetypeid'],
                'name'=>$this->allServiceType[$userServiceType['servicetypeid']],
                'exp'=>$userServiceType['exp'],
            ];

            $followList = model('follow')->where(['userid'=>['in',$albumMain['userid']]])->select();
            $newUserFollowList = [];
            foreach($followList as $oneFollow){
                $newUserFollowList[$oneFollow['followedid']]=$oneFollow;
            }


            $data['user']=[
                'userid'        => $albumMain['userid'],
                'avatar'        => $avatar,
                'nickName'      => $userInfo['nickname'],
                'sex'           => $userInfo['sex'],
                'verify_state'  => $userInfo['verify_state'],
                'zhima_code'    => model('userinfo')->getZhimaState($userInfo['zhima_code']),
                "identity"      => $serviceTypes,
                'hxid'          => $userHx['hx_id'],           //环信id
                'hxpa'          => $userHx['hx_pass'],          //环信密码
                'isfollow'=>isset($this->curUserInfo['userid'])?
                    (isset($newUserFollowList[$albumMain['userid']])?1:
                        ($albumMain['userid']==$this->curUserInfo['userid']?2:0)):0,   //2是自己1已关注0未关注
            ];

            //专辑的标签列表
            $albumTagList = model('albumtag')->where(['albumid'=>$albumId,'delflag'=>0])
                ->order('favour_counter desc')->select();
            $data['albumtags']=[];
            foreach($albumTagList as $oneAlbumTag){
                $data['albumtags'][]=[
                    'tagname'=>$oneAlbumTag['tagname'],
                    'favour_counter'=>$oneAlbumTag['favour_counter'],
                ];
            }

            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }



    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    删除自己的相册专辑
     * @url     /album/albumDel
     * @method  POST
     * @version 1000
     * @params  albumid 1 INT 相册专辑id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function albumDel(){

        //返回结果
        $data = [];

        //获取接口参数
        $albumId = input('albumid',0);

        if($albumId<0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $albumMain = model('albummain')
                ->where(['userid'=>$this->curUserInfo['userid'],'albumid'=>$albumId,'delflag'=>0])
                ->find();
            if(!$albumMain){
                $this->returndata( 14002, 'album not exist', $this->curTime, $data);
            }
            model('albummain')
                ->where(['userid'=>$this->curUserInfo['userid'],'albumid'=>$albumId,'delflag'=>0])
                ->update(['delflag'=>1]);
            //更新个人相册专辑数
            $saveUserData = [
                'album_counter'=>['exp','album_counter-1'],
                'updatetime'=>$this->curTime
            ];
            model('userdata')->where(['userid'=>$this->curUserInfo['userid'],'album_counter'=>['gt',0]])
                ->update($saveUserData);

            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


}
