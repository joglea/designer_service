<?php
namespace app\index\controller;

use app\common\controller\Front;

require_once APP_PATH . "/../extend/wxpay/lib/WxPay.Api.php";
require_once APP_PATH . "/../extend/wxpay/example/WxPay.JsApiPay.php";
/**
 * Class Task
 *
 * @classdesc 任务接口类
 * @package app\index\controller
 */
class Task extends Front
{


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    任务列表接口
     * @url     /task/taskList
     * @method  GET
     * @version 1000
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  tasktypeid 1 INT 任务分类id为0表示全部 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskList(){


        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数
        $page = input('request.page',1,'intval');
        $tasktypeid = input('request.tasktypeid',0,'intval');


        //验证参数是否为空
        if($page<1){
            $page = 1;
        }
        if($tasktypeid<0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{


            //新建可以报名的任务列表
            $taskWhere = ['check_state'=>2,'state'=>['in',[1,2,3,4]],'delflag'=>0];
            if($tasktypeid>0){
                $taskWhere['tasktypeid']=$tasktypeid;
            }
            //$taskWhere['limittime']=['gt',time()];
            $order = 'state asc,taskid desc';

            $taskList = model('task')->where($taskWhere)->order($order)
                ->limit((($page-1)*$pageSize).','.$pageSize)->select();


            $taskIds = [];
            $userIds = [];
            foreach($taskList as $oneTask){
                $taskIds[]=$oneTask['taskid'];
            }

            if(!$taskIds){
                $this->returndata( 14003, 'task list is empty', $this->curTime, $data);
            }

            $taskDataList = model('taskdata')->where(['taskid'=>['in',$taskIds]])->select();

            $newTaskDataList = [];
            foreach($taskDataList as $oneTaskData){
                $newTaskDataList[$oneTaskData['taskid']] = [
                    'read_counter'=>$oneTaskData['read_counter'],
                    'signup_counter'=>$oneTaskData['signup_counter'],
                ];
            }

            $this->getAllControl();




            $newTaskList = [];
            foreach($taskList as $oneTask){
                $newTaskList[]=[
                    'taskid'=>$oneTask['taskid'],
                    'title'=>$oneTask['title'],
                    'desc'=>$oneTask['desc'],
                    'limittime'=>$oneTask['limittime'],
                    'price'=>$oneTask['price'],
                    'state'=>$oneTask['state'],
                    'task_pic' =>$this->checkPictureUrl($this->allControl['task_image_url'],$oneTask['task_pic']),
                    //'content'=>$oneTask['content'],
                    'read_counter'=>isset($newTaskDataList[$oneTask['taskid']])?
                        $newTaskDataList[$oneTask['taskid']]['read_counter']:0,
                    'signup_counter'=>isset($newTaskDataList[$oneTask['taskid']])?
                        $newTaskDataList[$oneTask['taskid']]['signup_counter']:0,
                ];
            }

            $data['taskList'] = $newTaskList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    我发布的任务列表接口
     * @url     /task/myCreateTaskList
     * @method  GET
     * @version 1000
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function myCreateTaskList(){


        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数

        $page = input('request.page',1,'intval');

        //验证参数是否为空
        if($page<1){
            $page = 1;

        }


        try{

            //新建可以报名的任务列表
            $taskWhere = [
                'userid'=>$this->curUserInfo['userid'],'delflag'=>0];

            //$taskWhere['limittime']=['gt',time()];
            $order = 'taskid desc';

            $taskList = model('task')->where($taskWhere)->order($order)
                ->limit((($page-1)*$pageSize).','.$pageSize)->select();


            $taskIds = [];
            foreach($taskList as $oneTask){
                $taskIds[]=$oneTask['taskid'];
            }

            if(!$taskIds){
                $this->returndata( 14003, 'task list is empty', $this->curTime, $data);
            }

            $taskDataList = model('taskdata')->where(['taskid'=>['in',$taskIds]])->select();

            $newTaskDataList = [];
            foreach($taskDataList as $oneTaskData){
                $newTaskDataList[$oneTaskData['taskid']] = [
                    'read_counter'=>$oneTaskData['read_counter'],
                    'signup_counter'=>$oneTaskData['signup_counter'],
                ];
            }

            $this->getAllControl();
            $newTaskList = [];
            foreach($taskList as $oneTask){
                $newTaskList[]=[
                    'taskid'=>$oneTask['taskid'],
                    'title'=>$oneTask['title'],
                    'desc'=>$oneTask['desc'],
                    'limittime'=>$oneTask['limittime'],
                    'price'=>$oneTask['price'],
                    'check_state'=>$oneTask['check_state'],
                    'state'=>$oneTask['state'],
                    'task_pic' =>$this->checkPictureUrl($this->allControl['task_image_url'],$oneTask['task_pic']),
                    //'content'=>$oneTask['content'],
                    'read_counter'=>isset($newTaskDataList[$oneTask['taskid']])?
                        $newTaskDataList[$oneTask['taskid']]['read_counter']:0,
                    'signup_counter'=>isset($newTaskDataList[$oneTask['taskid']])?
                        $newTaskDataList[$oneTask['taskid']]['signup_counter']:0,
                ];
            }

            $data['taskList'] = $newTaskList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }
    
    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    查看任务详情
     * @url     /task/taskView
     * @method  GET
     * @version 1000
     * @params  taskid 1 INT 任务id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
        "code":10000,
        "message":"view success",
        "time":1492593379,
        "data":{
            "task":{
            },
            "signup_userlist":{
            },
            
           
            
        }
    }
     *
     */
    public function taskView(){

        //返回结果
        $data = [];

        //获取接口参数
        $taskId = input('taskid');

        if($taskId <= 0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            //
            $task = model('task')->where(['taskid'=>$taskId,'delflag'=>0])->find();


            $taskData = model('taskdata')->where(['taskid'=>$taskId])->find();
            $taskSignupUserList = model('tasksignup')->where(['taskid'=>$taskId,'delflag'=>0])->select();


            if(!$task || !$taskData ){
                $this->returndata( 14002, 'task not exist', $this->curTime, $data);
            }
            if($task['state']==1&&$task['stop_signup_time']<=$this->curTime){
                model('task')->where(['taskid'=>$taskId,'state'=>1,'delflag'=>0])
                    ->update(['state'=>2,'updatetime'=>$this->curTime]);
                $task['state']=2;
            }
            $userIds = [];
            foreach($taskSignupUserList as $oneTaskUser){
                $userIds[]=$oneTaskUser['userid'];
            }

            $this->getAllControl();

            $userInfoList = model('userinfo')->where(['userid'=>['in',$userIds]])->select();
            $newUserInfoList = [];
            foreach($userInfoList as $oneUserInfo){

                $newUserInfoList[$oneUserInfo['userid']] = [
                    'avatar'=>$this->checkPictureUrl($this->allControl['avatar_url'],$oneUserInfo['avatar']),
                    'nickname'=>$oneUserInfo['nickname'],
                    'verify_state'=>$oneUserInfo['verify_state'],
                    'verifyid'=>$oneUserInfo['verifyid'],
                ];
            }

            $userDataList = model('userdata')->where(['userid'=>['in',$userIds]])->select();
            $newUserDataList = [];
            foreach($userDataList as $oneUserData){
                $newUserDataList[$oneUserData['userid']]=$oneUserData;
            }
            //$userHxList = model('userhx')->where(['userid'=>$task['userid']])->find();

            if($this->checkLogin()&&in_array($this->curUserInfo['userid'],$userIds)){
                $is_signup=1;
            }
            else{
                $is_signup=0;
            }

            $data['task']=[
                'taskid'                => $task['taskid'],
                'userid'                 => $task['userid'],
                'title'                 => $task['title'],
                'desc'               => $task['desc'],
                'task_pic'                  => $this->checkpictureurl($this->allControl['task_image_url'],$task['task_pic']),
                'price'                => $task['price'],
                'createtime'            => $task['createtime'],
                'limittime'              => $task['limittime'],
                'state'              => $task['state'],

                'read_counter'       => $taskData['read_counter'],
                'signup_counter'       => $taskData['signup_counter'],
                'is_signup'=>$is_signup
            ];



            $data['tasksignuplist'] = [];
            foreach($taskSignupUserList as $oneTaskUser){
                $data['tasksignuplist'][]=[
                    'userid'=>$oneTaskUser['userid'],
                    'avatar'=>isset($newUserInfoList[$oneTaskUser['userid']])?
                        $newUserInfoList[$oneTaskUser['userid']]['avatar']:'',
                    'nickname'=>isset($newUserInfoList[$oneTaskUser['userid']])?
                        $newUserInfoList[$oneTaskUser['userid']]['nickname']:'',
                    'suit_state'=>$oneTaskUser['suit_state']

                ];
            }

            model('taskdata')->where(['taskid'=>$taskId])->setInc('read_counter', 1);
            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    我接的任务列表接口
     * @url     /task/myAcceptTaskList
     * @method  GET
     * @version 1000
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function myAcceptTaskList(){


        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数

        $page = input('request.page',1,'intval');

        //验证参数是否为空
        if($page<1){
            $page = 1;
            //$this->returndata( 14001,  'params error', $this->curTime, $data);
        }


        try{

            //新建可以报名的任务列表
            $taskSignupWhere = [
                'userid'=>$this->curUserInfo['userid'],'delflag'=>0];

            //$taskWhere['limittime']=['gt',time()];
            $order = 'signupid desc';

            $signupList = model('tasksignup')->where($taskSignupWhere)->order($order)
                ->limit((($page-1)*$pageSize).','.$pageSize)->select();


            $taskIds = [];
            foreach($signupList as $oneSignup){
                $taskIds[]=$oneSignup['taskid'];
            }

            if(!$taskIds){
                $this->returndata( 14003, 'task list is empty', $this->curTime, $data);
            }


            $taskList = model('task')->where(['taskid'=>['in',$taskIds],'delflag'=>0])
                ->select();
            $newTaskList = [];
            foreach($taskList as $oneTask){
                $newTaskList[$oneTask['taskid']]=$oneTask;
            }


            $taskDataList = model('taskdata')->where(['taskid'=>['in',$taskIds]])->select();

            $newTaskDataList = [];
            foreach($taskDataList as $oneTaskData){
                $newTaskDataList[$oneTaskData['taskid']] = [
                    'read_counter'=>$oneTaskData['read_counter'],
                    'signup_counter'=>$oneTaskData['signup_counter'],
                ];
            }

            $this->getAllControl();
            $acceptList = [];
            foreach($signupList as $oneSignup){
                if(!isset($newTaskList[$oneSignup['taskid']])){
                    continue;
                }
                $acceptList[]=[
                    'taskid'=>$oneSignup['taskid'],
                    'title'=>$newTaskList[$oneSignup['taskid']]['title'],
                    'desc'=>$newTaskList[$oneSignup['taskid']]['desc'],
                    'limittime'=>$newTaskList[$oneSignup['taskid']]['limittime'],
                    'price'=>$newTaskList[$oneSignup['taskid']]['price'],
                    'check_state'=>$newTaskList[$oneSignup['taskid']]['check_state'],
                    'state'=>$newTaskList[$oneSignup['taskid']]['state'],
                    'task_pic' =>$this->checkPictureUrl($this->allControl['task_image_url'],$newTaskList[$oneSignup['taskid']]['task_pic']),
                    //'content'=>$oneTask['content'],
                    'read_counter'=>isset($newTaskDataList[$oneSignup['taskid']])?
                        $newTaskDataList[$oneSignup['taskid']]['read_counter']:0,
                    'signup_counter'=>isset($newTaskDataList[$oneSignup['taskid']])?
                        $newTaskDataList[$oneSignup['taskid']]['signup_counter']:0,
                ];
            }

            $data['acceptTaskList'] = $acceptList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    接受的任务详情
     * @url     /task/myAcceptTaskView
     * @method  GET
     * @version 1000
     * @params  taskid 1 INT 任务id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
    "code":10000,
    "message":"view success",
    "time":1492593379,
    "data":{
    "task|":{
    "taskid":15,
    "userid":1000000005,
    "title":"77",
    "desc":"<p><br/><img src=\"http://www.ds.com/statics/Plugin/umeditor1_2_2/php/upload/20170822/15034080574507.jpg\" _src=\"http://www.ds.com/statics/Plugin/umeditor1_2_2/php/upload/20170822/15034080574507.jpg\"/></p>",
    "task_pic":"http://www.ds.com/statics/Image/task/2017082221382753993f2263787.jpg",
    "price":"77.000",
    "createtime":1503408076,
    "limittime":1503331200,
    "state":2,
    "read_counter":0,
    "signup_counter":0
    },
    "mytasksignup":{
    "userid":1000000005,
    "desc":"111",
    "pics":[
    "http://www.ds.com/statics/Image/task/b.jpg"
    ],
    "suit_state":3
    }
    }
    }
     *
     */
    public function myAcceptTaskView(){

        //返回结果
        $data = [];

        //获取接口参数
        $taskId = input('taskid');

        if($taskId <= 0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $task = model('task')->where(['taskid'=>$taskId,'delflag'=>0])->find();

            $taskData = model('taskdata')->where(['taskid'=>$taskId])->find();
            $myTaskSignup = model('tasksignup')
                ->where(['taskid'=>$taskId,'userid'=>$this->curUserInfo['userid'],'delflag'=>0])
                ->find();

            if(!$task || !$taskData ){
                $this->returndata( 14002, 'task not exist', $this->curTime, $data);
            }
            if(!$myTaskSignup){
                $this->returndata( 14002, 'my task signup not exist', $this->curTime, $data);
            }
            if($task['state']==1&&$task['stop_signup_time']<=$this->curTime){
                model('task')->where(['taskid'=>$taskId,'state'=>1,'delflag'=>0])
                    ->update(['state'=>2,'updatetime'=>$this->curTime]);
                $task['state']=2;
            }

            $this->getAllControl();

            $userInfo= model('userinfo')->where(['userid'=>$this->curUserInfo['userid']])->find();

            //$userData = model('userdata')->where(['userid'=>$this->curUserInfo['userid']])->find();

            $data['task']=[
                'taskid'                => $task['taskid'],
                'userid'                 => $task['userid'],
                'title'                 => $task['title'],
                'desc'               => $task['desc'],
                'task_pic'                  => $this->checkpictureurl($this->allControl['task_image_url'],$task['task_pic']),
                'price'                => $task['price'],
                'createtime'            => $task['createtime'],
                'limittime'              => $task['limittime'],
                'state'              => $task['state'],
                'read_counter'       => $taskData['read_counter'],
                'signup_counter'       => $taskData['signup_counter'],
            ];

            $piclist = json_decode($myTaskSignup['pics'],true);
            $new_pic_list = [];
            if(is_array($piclist)){
                foreach($piclist as $onepic){
                    $new_pic_list[]=$this->checkPictureUrl($this->allControl['task_image_url'],$onepic);
                }
            }

            $data['mytasksignup'] = [
                'userid'=>$this->curUserInfo['userid'],
                'desc'=>$myTaskSignup['desc'],
                'pics'=>$new_pic_list,
                'suit_state'=>$myTaskSignup['suit_state'],
                //'avatar'=>$userInfo?$this->checkPictureUrl($this->allControl['avatar_url'],$userInfo['avatar']):'',
                //'nickname'=>$userInfo?$userInfo['nickname']:'',
            ];

            model('taskdata')->where(['taskid'=>$taskId])->setInc('read_counter', 1);
            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    任务报名人员列表接口
     * @url     /task/taskSignupList
     * @method  GET
     * @version 1000
     * @params  taskid 1 INT 任务id YES
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskSignupList(){


        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数

        $taskid = input('request.taskid',0,'intval');
        $page = input('request.page',1,'intval');

        //验证参数是否为空
        if($taskid<=0){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }
        if($page<1){
            $page= 1;
        }


        try{

            //新建可以报名的任务列表
            $taskSignupWhere = ['taskid'=>$taskid,'delflag'=>0];

            $order = ' signupid desc';

            $signupList = model('tasksignup')->where($taskSignupWhere)->order($order)
                ->limit((($page-1)*$pageSize).','.$pageSize)->select();


            $userIds = [];
            foreach($signupList as $oneSignup){
                $userIds[]=$oneSignup['userid'];
            }

            if(!$userIds){
                $this->returndata( 14003, 'user list is empty', $this->curTime, $data);
            }

            $userinfoList = model('userinfo')->where(['userid'=>['in',$userIds]])->select();

            $newUserinfoList = [];
            $allControls = $this->getAllControl();
            foreach($userinfoList as $oneUserinfo){
                $newUserinfoList[$oneUserinfo['userid']] = [
                    'userid'=>$oneUserinfo['userid'],
                    'avatar'=>$this->checkPictureUrl($allControls['avatar_url'],$oneUserinfo['avatar']),
                    'nickname'=>$oneUserinfo['nickname'],
                ];
            }


            $newSignupList = [];
            foreach($signupList as $oneSignup){
                $pics = json_decode($oneSignup['pics']);
                $newPics = $this->checkPictureUrl($allControls['task_image_url'],$pics);
                $newSignupList[]=[
                    'signupid'=>$oneSignup['signupid'],
                    'userid'=>$oneSignup['userid'],
                    'avatar'=>$newUserinfoList[$oneSignup['userid']]['avatar'],
                    'nickname'=>$newUserinfoList[$oneSignup['userid']]['nickname'],
                    'createtime'=>date('Y-m-d H:i',$oneSignup['createtime']),

                    'desc'=>$oneSignup['desc'],
                    'pics'=>$newPics,
                ];
            }

            $data['signupList'] = $newSignupList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    添加报名任务接口
     * @url     /task/taskSignupAdd
     * @method  POST
     * @version 1000
     * @params  taskid 1 INT 任务id YES
     * @params  desc 啊 STRING 描述 YES
     * @params  pics ["aa.jpg"] STRING 图片列表 NO
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskSignupAdd(){
        //返回结果
        $data = [];

        //获取接口参数
        $taskid = input('request.taskid','');
        $desc = input('request.desc','');
        $pics = input('request.pics','');
        $newPics = json_decode($pics,true);


        //验证参数是否为空
        if($taskid<=0 || $desc==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $task = model('task')->where(['taskid'=>$taskid,'delflag'=>0])->find();
            if($task&&$task['state']==1&&$task['stop_signup_time']<=$this->curTime){
                model('task')->where(['taskid'=>$taskid,'state'=>1,'delflag'=>0])
                    ->update(['state'=>2,'updatetime'=>$this->curTime]);
                $task['state']=2;
            }
            if(!$task||!($task['check_state']==2&&$task['state']==1)){
                $this->returndata( 14002, 'task can not signup ', $this->curTime, $data);
            }

            $signup = model('tasksignup')->where([
                'taskid'=>$taskid,
                'userid'=>$this->curUserInfo['userid'],'delflag'=>0])->find();
            $is_exist = 0;
            //已经报名并且没用被选中的话  再次报名就覆盖原先的报名
            if($signup){
                if($signup['suit_state']==1){
                    model('tasksignup')->where([
                        'taskid'=>$taskid,
                        'userid'=>$this->curUserInfo['userid'],'delflag'=>0])
                        ->update(['delflag'=>1,'updatetime'=>$this->curTime]);
                    $is_exist=1;
                }
                else{
                    $this->returndata( 14002, 'signup exist', $this->curTime, $data);
                }

            }

            $newtaskSignup = [
                'taskid'=>$taskid,
                'userid'=>$this->curUserInfo['userid'],
                'desc'=>$desc,
                'pics'=>json_encode((array)$newPics),
                'suit_state'=>1,//被商家选中的适合报名状态 1初稿未选中 2初稿被选中 3最终选中 4最终未选中
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0,
            ];
            $signupid = model('tasksignup')->insertGetId($newtaskSignup);

            if(!$signupid){
                $this->returndata( 14002, 'signup  fail', $this->curTime, $data);
            }
            if(!$is_exist){
                model('taskdata')->where(['taskid'=>$taskid])->setInc('signup_counter', 1);
            }

            $data['signupid']=$signupid;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    添加工作经历接口
     * @url     /task/taskAdd
     * @method  POST
     * @version 1000
     * @params  desc 啊 STRING 描述 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskAdd(){
        //返回结果
        $data = [];

        //获取接口参数
        $desc = input('request.desc','');


        //验证参数是否为空
        if($desc==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $newtask = [
                'userid'=>$this->curUserInfo['userid'],
                'desc'=>$desc,
                'check_state'=>1,
                'state'=>1,
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0,
            ];
            $taskid = model('task')->insertGetId($newtask);

            if(!$taskid){
                $this->returndata( 14002, 'task add fail', $this->curTime, $data);
            }

            $data['taskid']=$taskid;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    修改工作经历接口
     * @url     /task/taskEdit
     * @method  POST
     * @version 1000
     * @params  taskid 1 INT 经历id YES
     * @params  desc 啊 STRING 描述 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskEdit(){
        //返回结果
        $data = [];

        //获取接口参数
        $taskid = input('request.taskid','');
        $desc = input('request.desc','');


        //验证参数是否为空
        if($taskid<=0||$desc==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $task = model('task')->where([
                'userid'=>$this->curUserInfo['userid'],
                'taskid'=>$taskid,'delflag'=>0])->find();
            if($task&&$task['check_state']==1){
                $newtask = [
                    'desc'=>$desc,
                    'updatetime'=>$this->curTime
                ];
                $ret = model('task')->where(['taskid'=>$taskid])->update($newtask);

                if(!$ret){
                    $this->returndata( 14002, 'task edit fail', $this->curTime, $data);
                }

                $this->returndata(10000, 'do success', $this->curTime, $data);

            }
            else{
                $this->returndata(14003, 'check_state error', $this->curTime, []);
            }

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }



    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    删除自己的经验
     * @url     /task/taskDel
     * @method  POST
     * @version 1000
     * @params  taskid 1 INT 经历id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskDel(){

        //返回结果
        $data = [];

        //获取接口参数
        $taskId = input('taskid',0);

        if($taskId<0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $task = model('task')
                ->where(['userid'=>$this->curUserInfo['userid'],
                         'taskid'=>$taskId,'delflag'=>0])
                ->find();
            if(!$task){
                $this->returndata( 14002, 'task not exist', $this->curTime, $data);
            }
            model('task')
                ->where(['userid'=>$this->curUserInfo['userid'],
                         'taskid'=>$taskId,'delflag'=>0])
                ->update(['delflag'=>1,'updatetime'=>$this->curTime]);
            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }



    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    直接下单
     * @url     /task/taskOrder
     * @method  POST
     * @version 1000
     * @params  taskid 1 INT 任务id YES
     * @params  signupids 1,2 STRING 报名id串,多个id逗号隔开 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskOrder(){
        //返回结果
        $data = [];

        //获取接口参数
        $taskId = input('taskid',0);
        $signupIds = input('signupids','');
        $signupIdsArr = explode(',',$signupIds);
        if($taskId<0||!$signupIdsArr){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }
        if(count($signupIdsArr)>2){
            $this->returndata(14001, 'signup max count just can be 2', $this->curTime, $data);
        }

        model('task')->startTrans();
        try{

            $task = model('task')
                ->where(['userid'=>$this->curUserInfo['userid'],
                         'taskid'=>$taskId,'delflag'=>0])
                ->find();
            if(!$task){
                $this->returndata( 14002, 'task not exist', $this->curTime, $data);
            }
            $tasktype = model('tasktype')->where(['tasktypeid'=>$task['tasktypeid'],'delflag'=>0])->find();
            $signupList = model('tasksignup')->where(
                ['taskid'=>$taskId,'signupid'=>['in',$signupIdsArr],
                 'suit_state'=>1,'delflag'=>0])->select();

            if(!$signupList){
                $this->returndata( 14003, 'signup not exist or state error', $this->curTime, $data);
            }
            /*$wallet = model('wallet')
                ->where(['userid'=>$this->curUserInfo['userid']])->find();
            if(!$wallet){
                $this->returndata( 14003, 'wallet not exist ', $this->curTime, $data);
            }*/
            //是否已经有未取消的订单
            $orderexist = model('order')->where(
                ['userid'=>$this->curUserInfo['userid'],
                 'taskid'=>$taskId,'state'=>['in',[1,2,4]],'delflag'=>0]
            )->find();

            if($orderexist){
                $this->returndata( 14003, 'not cancel state order exist', $this->curTime, $data);
            }
            $order = [
                'userid'=>$this->curUserInfo['userid'],
                'taskid'=>$taskId,
                'total_price'=>$task['price'],
                'pay_rate'=>$tasktype?$tasktype['pay_rate']:config('pay_rate'),
                'state'=>1,
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0,
            ];
            //创建订单
            $orderid = model('order')->insertGetId($order);


            /*$price = round($task['price']*config('desposit_rate'),2);
            $balance = $wallet['now_money']-$price;
            $balance=-1;
            if($balance<0){
                model('task')->commit();
                $data['order_id']=$orderid;

                //①、获取用户openid
                $tools = new \JsApiPay();
                // $openId = $tools->GetOpenid();
                $openId = model('userthird')->where(['userid'=>$this->curUserInfo['userid'],'delflag'=>0])
                    ->value('openid');
                //var_dump($openId);exit;
                //②、统一下单
                $input = new \WxPayUnifiedOrder();
                $input->SetBody($task['title']);
                $input->SetAttach($task['title']);
                $input->SetOut_trade_no($orderid);
                $input->SetTotal_fee($price*100);
                $input->SetTime_start(date("YmdHis"));
                $input->SetTime_expire(date("YmdHis", time() + 600));
                $input->SetGoods_tag($task['title']);
                $input->SetNotify_url("http://".config('server_host')."/task/taskWxNotify");
                $input->SetTrade_type("JSAPI");
                $input->SetProduct_id("123456789");
                $input->SetOpenid($openId);
                //var_dump(33,$input->GetValues());
                //$log->INFO(json_encode($input->GetValues()));

                $order = \WxPayApi::unifiedOrder($input);

                if($order['return_code']=='SUCCESS'&&$order['result_code']=='SUCCESS'){
                    $prepay_id = $order['prepay_id'];
                    $data['prepay_id'] = $prepay_id;
                    $this->returndata( 10000, 'wallet balance insufficient ', $this->curTime, $data);

                }
                else{
                    $this->returndata( 14001, $order['return_msg'], $this->curTime, $data);

                }
                $jsApiParameters = $tools->GetJsApiParameters($order);

                //if($jsApiParameters['return_code']=='SUCCESS')

                $this->returndata( 10001, 'wallet balance insufficient ', $this->curTime, $data);
            }*/

            foreach($signupList as $oneSignup){

                //订单详情
                $detailid = model('orderdetail')->insertGetId(
                    [
                        'orderid'=>$orderid,
                        'signupid'=>$oneSignup['signupid'],
                        'createtime'=>$this->curTime,
                        'updatetime'=>$this->curTime,
                        'delflag'=>0,
                    ]
                );
                //更新报名状态
                model('tasksignup')
                    ->where(
                        ['signupid'=>$oneSignup['signupid']]
                    )
                    ->update(['suit_state'=>2,'updatetime'=>$this->curTime]);
            }

           /* $date = [
                'now_money'=>$balance,
                'updatetime'=>$this->curTime,
            ];
            //更新余额
            $walletUpdate = model('wallet')
                ->where(['userid'=>$this->curUserInfo['userid']])->update($date);
            //余额变更记录
            model('walletrecord')->insertGetId(
                [
                    'userid'=>$this->curUserInfo['userid'],
                    'objectid'=>$orderid,
                    'objecttype'=>1,//对象类型1支出2收入3充值
                    'price'=>$price,
                    'desc'=>'任务：'.$taskId.'的支付定金',
                    'createtime'=>$this->curTime,
                    'updatetime'=>$this->curTime,
                    'delflag'=>0,

                ]
            );*/
            model('task')->commit();
            $data['order_id']=$orderid;
            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            // 回滚事务
            model('task')->rollback();
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    支付定金
     * @url     /task/taskSignupDeposit
     * @method  POST
     * @version 1000
     * @params  taskid 1 INT 任务id YES
     * @params  signupids 1,2 STRING 报名id串,多个id逗号隔开 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskSignupDeposit(){
        //返回结果
        $data = [];

        //获取接口参数
        $taskId = input('taskid',0);
        $signupIds = input('signupids','');
        $signupIdsArr = explode(',',$signupIds);
        if($taskId<0||!$signupIdsArr){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        model('task')->startTrans();
        try{

            $task = model('task')
                ->where(['userid'=>$this->curUserInfo['userid'],
                         'taskid'=>$taskId,'delflag'=>0])
                ->find();
            if(!$task){
                $this->returndata( 14002, 'task not exist', $this->curTime, $data);
            }
            $tasktype = model('tasktype')->where(['tasktypeid'=>$task['tasktypeid'],'delflag'=>0])->find();
            $signupList = model('tasksignup')->where(
                ['taskid'=>$taskId,'signupid'=>['in',$signupIdsArr],
                 'suit_state'=>1,'delflag'=>0])->select();
            //var_dump(model('tasksignup')->getLastSql());
            if(!$signupList){
                $this->returndata( 14003, 'signup not exist or state error', $this->curTime, $data);
            }
            $wallet = model('wallet')
                ->where(['userid'=>$this->curUserInfo['userid']])->find();
            if(!$wallet){

                $wallet = [
                    'userid'=>$this->curUserInfo['userid'],
                    'now_money'=>0,
                    'createtime'=>$this->curTime,
                    'updatetime'=>$this->curTime,
                ];
                //更新余额
                model('wallet')->insertGetId($wallet);
            }

            $pay_rate=$tasktype?$tasktype['pay_rate']:config('pay_rate');
            $price = bcmul($task['price'],$pay_rate,2);

            if($price>$wallet['now_money']){
                model('task')->commit();
                $this->returndata( 14004, 'wallet money too less ', $this->curTime, $data);
            }


            $order = [
                'userid'=>$this->curUserInfo['userid'],
                'taskid'=>$taskId,
                'total_price'=>$task['price'],
                'pay_rate'=>$pay_rate,
                'state'=>2,
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0,
            ];
            //创建订单
            $orderid = model('order')->insertGetId($order);

            $balance = bcsub($wallet['now_money'],$price,2);
            $savewallet = [
                'now_money'=>$balance,
                'updatetime'=>$this->curTime,
            ];
            //更新余额
            model('wallet')
                ->where(['userid'=>$this->curUserInfo['userid']])->update($savewallet);

            //余额变更记录
            model('walletrecord')->insertGetId(
                [
                    'userid'=>$this->curUserInfo['userid'],
                    'objectid'=>$orderid,
                    'objecttype'=>1,//对象类型1支出2收入3充值
                    'price'=>$price,
                    'desc'=>'任务：'.$taskId.'的支付定金',
                    'createtime'=>$this->curTime,
                    'updatetime'=>$this->curTime,
                    'delflag'=>0,
                ]
            );


            if(false){
                model('task')->commit();
                $data['order_id']=$orderid;

                //①、获取用户openid
                $tools = new \JsApiPay();
                // $openId = $tools->GetOpenid();
                $openId = model('userthird')->where(['userid'=>$this->curUserInfo['userid'],'delflag'=>0])
                    ->value('openid');
                //var_dump($openId);exit;
                //②、统一下单
                $input = new \WxPayUnifiedOrder();
                $input->SetBody($task['title']);
                $input->SetAttach($task['title']);
                $input->SetOut_trade_no($orderid);
                $input->SetTotal_fee($price*100);
                $input->SetTime_start(date("YmdHis"));
                $input->SetTime_expire(date("YmdHis", time() + 600));
                $input->SetGoods_tag($task['title']);
                $input->SetNotify_url("http://".config('server_host')."/task/taskWxNotify");
                $input->SetTrade_type("JSAPI");
                $input->SetProduct_id("123456789");
                $input->SetOpenid($openId);
                //var_dump(33,$input->GetValues());
                //$log->INFO(json_encode($input->GetValues()));

                $order = \WxPayApi::unifiedOrder($input);

                if($order['return_code']=='SUCCESS'&&$order['result_code']=='SUCCESS'){
                    $prepay_id = $order['prepay_id'];
                    $data['prepay_id'] = $prepay_id;
                    $this->returndata( 10000, 'wallet balance insufficient ', $this->curTime, $data);

                }
                else{
                    $this->returndata( 14001, $order['return_msg'], $this->curTime, $data);

                }
                $jsApiParameters = $tools->GetJsApiParameters($order);

                //if($jsApiParameters['return_code']=='SUCCESS')

                $this->returndata( 10001, 'wallet balance insufficient ', $this->curTime, $data);
            }

            $signupprice = bcdiv($price,count($signupList),2);
            foreach($signupList as $oneSignup){

                $curwallet = model('wallet')
                    ->where(['userid'=>$oneSignup['userid']])->find();

                if($curwallet){
                    $cursavewallet = [
                        'now_money'=>bcadd($curwallet['now_money'],$signupprice,2),
                        'updatetime'=>$this->curTime,
                    ];
                    //更新余额
                    model('wallet')
                        ->where(['userid'=>$oneSignup['userid']])->update($cursavewallet);
                }
                else{
                    $newwallet = [
                        'userid'=>$oneSignup['userid'],
                        'now_money'=>$signupprice,
                        'createtime'=>$this->curTime,
                        'updatetime'=>$this->curTime,
                    ];
                    //更新余额
                    model('wallet')->insertGetId($newwallet);
                }

                //余额变更记录
                model('walletrecord')->insertGetId(
                    [
                        'userid'=>$oneSignup['userid'],
                        'objectid'=>$orderid,
                        'objecttype'=>2,//对象类型1支出2收入3充值
                        'price'=>$signupprice,
                        'desc'=>'任务：'.$taskId.'的支付定金',
                        'createtime'=>$this->curTime,
                        'updatetime'=>$this->curTime,
                        'delflag'=>0,
                    ]
                );

                //订单详情
                $detailid = model('orderdetail')->insertGetId(
                    [
                        'orderid'=>$orderid,
                        'signupid'=>$oneSignup['signupid'],
                        'createtime'=>$this->curTime,
                        'updatetime'=>$this->curTime,
                        'delflag'=>0,
                    ]
                );
                //更新报名状态
                model('tasksignup')
                    ->where(
                        ['signupid'=>$oneSignup['signupid']]
                    )
                    ->update(['suit_state'=>2,'updatetime'=>$this->curTime]);
            }
            //更新任务状态
            $newtask = [
                'state'=>3,//任务状态 1新建接受报名 2停止报名 3订金预付 4尾款支付 5关闭
                'updatetime'=>$this->curTime
            ];
            model('task')->where(['taskid'=>$taskId])->update($newtask);

            model('task')->commit();
            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            // 回滚事务
            model('task')->rollback();
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    支付尾款
     * @url     /task/taskSignupTail
     * @method  POST
     * @version 1000
     * @params  taskid 1 INT 任务id YES
     * @params  signupid 1 STRING 报名id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskSignupTail(){
        //返回结果
        $data = [];

        //获取接口参数
        $taskId = input('taskid',0);
        $signupId = input('signupid',0);
        if($taskId<0||$signupId<=0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        model('task')->startTrans();
        try{

            $task = model('task')
                ->where(['userid'=>$this->curUserInfo['userid'],
                         'taskid'=>$taskId,'delflag'=>0])
                ->find();
            if(!$task){
                $this->returndata( 14002, 'task not exist', $this->curTime, $data);
            }
            $tasktype = model('tasktype')->where(['tasktypeid'=>$task['tasktypeid'],'delflag'=>0])->find();

            $signupList = model('tasksignup')->where(
                ['signupid'=>$signupId,'suit_state'=>2,'delflag'=>0])->select();
            if(!$signupList){
                $this->returndata( 14003, 'signup not exist or state error', $this->curTime, $data);
            }
            $wallet = model('wallet')
                ->where(['userid'=>$this->curUserInfo['userid']])->find();
            if(!$wallet){

                $wallet = [
                    'userid'=>$this->curUserInfo['userid'],
                    'now_money'=>0,
                    'createtime'=>$this->curTime,
                    'updatetime'=>$this->curTime,
                ];
                //更新余额
                model('wallet')->insertGetId($wallet);
            }

            $pay_rate=$tasktype?bcsub(1,$tasktype['pay_rate'],4):bcsub(1,config('pay_rate'),4);
            $price = bcmul($task['price'],$pay_rate,2);

            if($price>$wallet['now_money']){
                model('task')->commit();
                $this->returndata( 14004, 'wallet money too less ', $this->curTime, $data);
            }

            $order = [
                'userid'=>$this->curUserInfo['userid'],
                'taskid'=>$taskId,
                'total_price'=>$task['price'],
                'pay_rate'=>$pay_rate,
                'state'=>4,
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0,
            ];
            //创建订单
            $orderid = model('order')->insertGetId($order);

            $price = bcmul($task['price'],$pay_rate,2);
            //$balance = $wallet['now_money']-$price;
            $balance = bcsub($wallet['now_money'],$price,2);
            $savewallet = [
                'now_money'=>$balance,
                'updatetime'=>$this->curTime,
            ];
            //更新余额
            model('wallet')
                ->where(['userid'=>$this->curUserInfo['userid']])->update($savewallet);

            //余额变更记录
            model('walletrecord')->insertGetId(
                [
                    'userid'=>$this->curUserInfo['userid'],
                    'objectid'=>$orderid,
                    'objecttype'=>1,//对象类型1支出2收入3充值
                    'price'=>$price,
                    'desc'=>'任务：'.$taskId.'的支付尾款',
                    'createtime'=>$this->curTime,
                    'updatetime'=>$this->curTime,
                    'delflag'=>0,
                ]
            );


            if(false){
                model('task')->commit();
                $data['order_id']=$orderid;

                //①、获取用户openid
                $tools = new \JsApiPay();
                // $openId = $tools->GetOpenid();
                $openId = model('userthird')->where(['userid'=>$this->curUserInfo['userid'],'delflag'=>0])
                    ->value('openid');
                //var_dump($openId);exit;
                //②、统一下单
                $input = new \WxPayUnifiedOrder();
                $input->SetBody($task['title']);
                $input->SetAttach($task['title']);
                $input->SetOut_trade_no($orderid);
                $input->SetTotal_fee($price*100);
                $input->SetTime_start(date("YmdHis"));
                $input->SetTime_expire(date("YmdHis", time() + 600));
                $input->SetGoods_tag($task['title']);
                $input->SetNotify_url("http://".config('server_host')."/task/taskWxNotify");
                $input->SetTrade_type("JSAPI");
                $input->SetProduct_id("123456789");
                $input->SetOpenid($openId);
                //var_dump(33,$input->GetValues());
                //$log->INFO(json_encode($input->GetValues()));

                $order = \WxPayApi::unifiedOrder($input);

                if($order['return_code']=='SUCCESS'&&$order['result_code']=='SUCCESS'){
                    $prepay_id = $order['prepay_id'];
                    $data['prepay_id'] = $prepay_id;
                    $this->returndata( 10000, 'wallet balance insufficient ', $this->curTime, $data);

                }
                else{
                    $this->returndata( 14001, $order['return_msg'], $this->curTime, $data);

                }
                $jsApiParameters = $tools->GetJsApiParameters($order);

                //if($jsApiParameters['return_code']=='SUCCESS')

                $this->returndata( 10001, 'wallet balance insufficient ', $this->curTime, $data);
            }


            $signupprice = bcdiv($price,count($signupList),2);
            foreach($signupList as $oneSignup){

                $curwallet = model('wallet')
                    ->where(['userid'=>$oneSignup['userid']])->find();

                if($curwallet){
                    $cursavewallet = [
                        'now_money'=>bcadd($curwallet['now_money'],$signupprice,2),
                        'updatetime'=>$this->curTime,
                    ];
                    //更新余额
                    model('wallet')
                        ->where(['userid'=>$oneSignup['userid']])->update($cursavewallet);
                }
                else{
                    $newwallet = [
                        'userid'=>$oneSignup['userid'],
                        'now_money'=>$signupprice,
                        'createtime'=>$this->curTime,
                        'updatetime'=>$this->curTime,
                    ];
                    //更新余额
                    model('wallet')->insertGetId($newwallet);
                }

                //余额变更记录
                model('walletrecord')->insertGetId(
                    [
                        'userid'=>$oneSignup['userid'],
                        'objectid'=>$orderid,
                        'objecttype'=>2,//对象类型1支出2收入3充值
                        'price'=>$signupprice,
                        'desc'=>'任务：'.$taskId.'的支付尾款',
                        'createtime'=>$this->curTime,
                        'updatetime'=>$this->curTime,
                        'delflag'=>0,
                    ]
                );

                //订单详情
                $detailid = model('orderdetail')->insertGetId(
                    [
                        'orderid'=>$orderid,
                        'signupid'=>$oneSignup['signupid'],
                        'createtime'=>$this->curTime,
                        'updatetime'=>$this->curTime,
                        'delflag'=>0,
                    ]
                );
                //更新报名状态
                model('tasksignup')
                    ->where(
                        ['signupid'=>$oneSignup['signupid']]
                    )
                    ->update(['suit_state'=>3,'updatetime'=>$this->curTime]);
            }
            //更新报名状态
            model('tasksignup')
                ->where(
                    ['taskid'=>$taskId,'signupid'=>['neq',$oneSignup['signupid']],'delflag'=>0]
                )
                ->update(['suit_state'=>4,'updatetime'=>$this->curTime]);

            //更新任务状态
            $newtask = [
                'state'=>4,//任务状态 1新建接受报名 2停止报名 3订金预付 4尾款支付 5关闭
                'updatetime'=>$this->curTime
            ];
            model('task')->where(['taskid'=>$taskId])->update($newtask);

            model('task')->commit();
            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            // 回滚事务
            model('task')->rollback();
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    public function taskWxNotify(){
        //初始化日志
        $file = getcwd().'/logs/'.date('Y-m-d',time()).'.txt';
        // $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        file_put_contents($file,$xml,FILE_APPEND);
        $obj = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $data = json_decode(json_encode($obj), true);
        $return_data=array();
        if($data['return_code']!='SUCCESS'){
            $return_data = array('return_code' => 'FAIL','return_msg'=>'' );

        }else{

            $order = model('order')->where(array('orderid'=>$data['out_trade_no']))->find();

            if($order ){
                $res = model('order')->where(array('orderid'=>$data['out_trade_no']))
                ->update(['state'=>2,'updatetime'=>$this->curTime]);
            }
            $return_data = array('return_code' => 'SUCCESS','return_msg'=>'OK' );
        }

        $xml = "";
        foreach ($return_data as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml="<xml>".$xml."</xml>";
        //var_dump($xml);exit;
        echo $xml;
        die;
    }
}
