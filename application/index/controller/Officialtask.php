<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Task
 *
 * @classdesc 官方任务接口类
 * @package app\index\controller
 */
class Officialtask extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    官方任务列表接口
     * @url     /officialtask/officialTaskList
     * @method  GET
     * @version 1000
     * @params  title '' STRING 任务标题 NO
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
        "code":10000,
        "message":"get success",
        "time":1492587433,
        "data":{
            "officialTaskList":[
            {
                "officialtaskid":1,
                "title":"dfg",
                "content":"sdf",
                "type":1,//任务成果类型1图2文3音4视
                "urls":[
                "http://omsnjcbau.bkt.clouddn.com/a.jpg"
                ],
                "state":1,//状态1新建进行中2正常完成3异常关闭
                "is_top":0,//是否置顶 0否1是
                "is_hot":0,//是否热门 0否1是
                "creator_name":"sfsdf",
                "join_counter":1,//参加人数
                "createtime":"1970-01-01 08:00:00"
            }
            ]
        }
    }
     *
     */
    public function officialTaskList(){


        //返回结果
        $data = [];
        $pageSize = config('page_size');
        //获取接口参数
        $title = input('request.title','');

        $page = input('request.page',1,'intval');

        //验证参数是否为空
        if($page<1){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            if($title!=''){
                $officialTaskWhere = ['title'=>['like','%'.$title.'%'],'state'=>['in',[1,2]],'delflag'=>0];
            }
            else{
                $officialTaskWhere = ['state'=>['in',[1,2]],'delflag'=>0];
            }

            $order='is_top desc,is_hot desc,createtime desc';

            $officialTaskList = model('officialtask')->where($officialTaskWhere)->order($order)
                ->limit((($page-1)*$pageSize).','.$pageSize)->select();


            if(!$officialTaskList){
                $this->returndata( 14003, 'official task list is empty', $this->curTime, $data);
            }

            $this->getAllControl();
            $newOfficialTasklist = [];
            foreach($officialTaskList as $oneOfficialTask){
                $urls = json_decode($oneOfficialTask['urls'],true);
                $urls = $urls?array_slice($urls,0,3):[];
                $newOfficialTasklist[]=[
                    'officialtaskid'=>$oneOfficialTask['officialtaskid'],
                    'title'=>$oneOfficialTask['title'],
                    'content'=>$oneOfficialTask['content'],
                    'type'=>$oneOfficialTask['type'],
                    'urls'=>$urls?$this->checkPictureUrl($this->allControl['task_image_url'],$urls):[],
                    'state'=>$oneOfficialTask['state'],
                    'is_top'=>$oneOfficialTask['is_top'],
                    'is_hot'=>$oneOfficialTask['is_hot'],
                    'creator_name'=>$oneOfficialTask['creator_name'],
                    'join_counter'=>$oneOfficialTask['join_counter'],
                    'createtime'=>$oneOfficialTask['createtime'],
                ];
            }

            $data['officialTaskList'] = $newOfficialTasklist;
            $this->returndata(10000, 'get success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    参加的官方任务列表接口
     * @url     /officialtask/officialTaskJoinList
     * @method  GET
     * @version 1000
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
    "code":10000,
    "message":"get success",
    "time":1492587433,
    "data":{
    "officialTaskJoinList":[
    {
    "officialtaskid":1,
    "title":"dfg",
    "content":"sdf",
    "type":1,//任务成果类型1图2文3音4视
    "urls":[
    "http://omsnjcbau.bkt.clouddn.com/a.jpg"
    ],
    "state":1,//状态1新建进行中2正常完成3异常关闭
    "is_top":0,//是否置顶 0否1是
    "is_hot":0,//是否热门 0否1是
    "creator_name":"sfsdf",
    "join_counter":1,//参加人数
    "createtime":"1970-01-01 08:00:00"
    }
    ]
    }
    }
     *
     */
    public function officialTaskJoinList(){


        //返回结果
        $data = [];
        $pageSize = config('page_size');
        //获取接口参数
        $page = input('request.page',1,'intval');

        //验证参数是否为空
        if($page<1){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $joinList = model('officialtaskresult')->joinOfficialTaskByWhere('jz_officialtask.officialtaskid = jz_officialtaskresult.officialtaskid',
                ['jz_officialtaskresult.userid'=>$this->curUserInfo['userid']],'*','','',($page-1)*$pageSize.','.$pageSize,'LEFT');

            if(!$joinList){
                $this->returndata( 14003, 'official task join list is empty', $this->curTime, $data);
            }

            $this->getAllControl();
            $newOfficialTaskJoinList = [];
            foreach($joinList as $oneOfficialTask){
                $urls = json_decode($oneOfficialTask['urls'],true);
                $urls = $urls?array_slice($urls,0,3):[];
                $newOfficialTasklist[]=[
                    'officialtaskid'=>$oneOfficialTask['officialtaskid'],
                    'title'=>$oneOfficialTask['title'],
                    'content'=>$oneOfficialTask['content'],
                    'type'=>$oneOfficialTask['type'],
                    'urls'=>$urls?$this->checkPictureUrl($this->allControl['task_image_url'],$urls):[],
                    'address'=>$oneOfficialTask['address'],
                    'startdate'=>$oneOfficialTask['startdate'],
                    'enddate'=>$oneOfficialTask['enddate'],
                    'state'=>$oneOfficialTask['state'],
                    'is_top'=>$oneOfficialTask['is_top'],
                    'is_hot'=>$oneOfficialTask['is_hot'],
                    'creator_name'=>$oneOfficialTask['creator_name'],
                    'join_counter'=>$oneOfficialTask['join_counter'],
                    'createtime'=>$oneOfficialTask['createtime'],
                ];
            }

            $data['officialTaskJoinList'] = $newOfficialTaskJoinList;
            $this->returndata(10000, 'get success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }



    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    查看任务详情
     * @url     /officialtask/officialTaskView
     * @method  GET
     * @version 1000
     * @params  officialtaskid 1 INT 任务id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
        "code":10000,
        "message":"view success",
        "time":1492589471,
        "data":{
            "officialtask":{
                "officialtaskid":2,
                "title":"nnnnn",
                "content":"df",
                "type":1,
                "urls":[
                "http://omsnjcbau.bkt.clouddn.com/a.jpg"
                ],
                "state":1,
                "join_counter":0,
                "createtime":"1970-01-01 08:00:00"
            }
        }
    }
     *
     */
    public function officialTaskView(){

        //返回结果
        $data = [];

        //获取接口参数
        $officialTaskId = input('officialtaskid');

        if($officialTaskId <= 0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            //
            $officialTask = model('officialtask')->where(['officialtaskid'=>$officialTaskId,'delflag'=>0])->find();

            if(!$officialTask ){
                $this->returndata( 14002, 'officialtask not exist', $this->curTime, $data);
            }

            $this->getAllControl();

            $urls = json_decode($officialTask['urls'],true);
            $urls = $urls?array_slice($urls,0,3):[];

            $data['officialtask']=[
                'officialtaskid'=> $officialTask['officialtaskid'],
                'creator_name'  => $officialTask['creator_name'],
                'title'         => $officialTask['title'],
                'content'       => $officialTask['content'],
                'address'       => $officialTask['address'],
                'startdate'     => $officialTask['startdate'],
                'enddate'       => $officialTask['enddate'],
                'type'          => $officialTask['type'],
                'urls'          => $urls?$this->checkPictureUrl($this->allControl['task_image_url'],$urls):[],
                'state'         => $officialTask['state'],
                'join_counter'  => $officialTask['join_counter'],
                'createtime'    => $officialTask['createtime'],
            ];

            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    查看官方任务详情话题列表
     * @url     /officialtask/officialTaskBlogList
     * @method  GET
     * @version 1000
     * @params  officialtaskid 1 INT 官方任务id YES
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
        "code":10000,
        "message":"view success",
        "time":1492589471,
        "data":{
            "officialtaskblog":[
            {
                "avatar":"http://omsnjcbau.bkt.clouddn.com/avatar/default02",
                "nickname":"dd都是",
                "tasktypename":"人气明星",
                "exp":111,
                "blogid":4,
                "content":"dfgdfg",
                "type":1,
                "urls":[
                    "http://omsnjcbau.bkt.clouddn.com/a.jpg"
                ],
                "createtime":"2017-04-20 14:44:09"
            }
            ]
        }
    }
     *
     */
    public function officialTaskBlogList(){

        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数
        $officialTaskId = input('officialtaskid');
        $page = input('request.page',1,'intval');

        //验证参数是否为空
        if($page<1||$officialTaskId <= 0){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            //
            $officialTask = model('officialtask')->where(['officialtaskid'=>$officialTaskId,'delflag'=>0])->find();

            if(!$officialTask ){
                $this->returndata( 14002, 'officialtask not exist', $this->curTime, $data);
            }

            $officialTaskBlogs = model('blogmain')->where(['officialtaskid'=>$officialTaskId,'delflag'=>0])->order('createtime desc')
                ->limit((($page-1)*$pageSize).','.$pageSize)->select();

            $userIds = [];
            foreach($officialTaskBlogs as $oneOfficialTask){
                $userIds[]=$oneOfficialTask['userid'];
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
            $newUserTasktypeList = [];

            $this->getAllServiceType();

            foreach($userServiceTypeList as $oneUserServiceType){
                if(!isset($newUserTasktypeList[$oneUserServiceType['userid']])){
                    $newUserTasktypeList[$oneUserServiceType['userid']]=[
                        'tasktypename'=>$this->allServiceType[$oneUserServiceType['servicetypeid']],
                        'exp'=>$oneUserServiceType['exp']
                    ];
                }
                elseif($newUserTasktypeList[$oneUserServiceType['userid']]['exp']<$oneUserServiceType['exp']){
                    $newUserTasktypeList[$oneUserServiceType['userid']]=[
                        'tasktypename'=>$this->allServiceType[$oneUserServiceType['servicetypeid']],
                        'exp'=>$oneUserServiceType['exp']
                    ];
                }
            }

            $allControl = $this->getAllControl();

            $data['officialtaskblog'] = [];
            foreach($officialTaskBlogs as $oneOfficialTaskBlog){
                $data['officialtaskblog'][]=[
                    'avatar'=>$newUserInfoList[$oneOfficialTaskBlog['userid']]['avatar'],
                    'nickname'=>$newUserInfoList[$oneOfficialTaskBlog['userid']]['nickname'],
                    'tasktypename'=>isset($newUserTasktypeList[$oneOfficialTaskBlog['userid']])?
                        $newUserTasktypeList[$oneOfficialTaskBlog['userid']]['tasktypename']:'',
                    'exp'=>isset($newUserTasktypeList[$oneOfficialTaskBlog['userid']])?
                        $newUserTasktypeList[$oneOfficialTaskBlog['userid']]['exp']:0,
                    'blogid'=>$oneOfficialTaskBlog['blogid'],
                    'content'=>$oneOfficialTaskBlog['content'],
                    'type'=>$oneOfficialTaskBlog['type'],
                    'urls'=>$this->checkpictureurl($allControl['task_image_url'],json_decode($oneOfficialTaskBlog['urls'],true)),
                    'createtime'=>$oneOfficialTaskBlog['createtime']
                ];
            }

            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }




    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    官方任务话题创建接口
     * @url     /officialtask/officialTaskBlogCreate
     * @method  POST
     * @version 1000
     * @params  officialtaskid 2 INT 官方任务id YES
     * @params  content '内容' STRING 任务内容描述 YES
     * @params  type 1 INT 任务媒体文件类型1图2文3音4视 YES
     * @params  urls '["a.jpg","b.jpg"]' STRING 图音视文件地址的json串 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function officialTaskBlogCreate(){
        //返回结果
        $data = [];

        //获取接口参数
        $officialTaskId = input('request.officialtaskid',0);
        $content = input('request.content','');
        $type = input('request.type',1);
        $urls = input('request.urls','');


        //验证参数是否为空
        if($officialTaskId<=0||!in_array($type,[1,2,3,4])||$content==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            //非取消状态下是否存在同标题的任务
            $officialTaskWhere = [
                'officialtaskid'=>$officialTaskId,
                'delflag'=>0
            ];
            $officialTask = model('officialtask')->where($officialTaskWhere)->find();

            if(!$officialTask){
                $this->returndata( 14004, 'officialtask not exist', $this->curTime, $data);
            }

            $newBlogMain = [
                'userid'=>$this->curUserInfo['userid'],
                'content'=>$content,
                'type'=>$type,
                'urls'=>$urls,
                'original'=>1,
                'julink'=>'[]',
                'officialtaskid'=>$officialTaskId,
                'createtime'=>$this->curTime,
                'delflag'=>0
            ];
            $blogId = model('blogmain')->insertGetId($newBlogMain);

            //创建任务统计信息
            $newBlogData = [
                'blogid'=>$blogId,
                'updatetime'=>$this->curTime,
            ];
            model('blogdata')->insertGetId($newBlogData);

            $data['blogid']=$blogId;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }





}
