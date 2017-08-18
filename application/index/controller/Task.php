<?php
namespace app\index\controller;

use app\common\controller\Front;
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
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
        {
            "code":10000,
            "message":"获取成功",
            "time":1492413087,
            "data":{
                "taskList":[
                {
                    "taskid":2,
                    "userid":1000000005,
                    "nickname":"dd都是",
                    "invited_userid":1000000006,
                    "invited_avatar":"http://omsnjcbau.bkt.clouddn.com/avatar/default01",
                    "invited_nickname":"花满楼",
                    "invited_tasktypename":"",
                    "invited_exp":0,
                    "title":"1111",
                    "content":"1111",
                    "reward":"11.00",
                    "state":0,
                    "image":"http://omsnjcbau.bkt.clouddn.com/a.jpg",
                    "support_counter":0
                }
                ]
            }
        }
     *
     */
    public function taskList(){


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

            //新建可以报名的任务列表
            $taskWhere = ['state'=>1,'delflag'=>0];

                $taskWhere['limittime']=['gt',time()];
                $order = 'taskid desc';




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



            $data['task']=[
                'taskid'                => $task['taskid'],
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



            $data['tasksignuplist'] = [];
            foreach($taskSignupUserList as $oneTaskUser){
                $data['tasksignuplist'][]=[
                    'userid'=>$oneTaskUser['userid'],
                    'avatar'=>isset($newUserInfoList[$oneTaskUser['userid']])?
                        $newUserInfoList[$oneTaskUser['userid']]['avatar']:'',
                    'nickname'=>isset($newUserInfoList[$oneTaskUser['userid']])?
                        $newUserInfoList[$oneTaskUser['userid']]['nickname']:'',

                ];
            }

            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }





    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    任务首页三类推荐列表
     * @url     /task/taskRecommendList
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
        {
            "code":10000,
            "message":"do success",
            "time":1492399870,
            "data":{
                "recommendlist":[
                {
                    "type":2,//推荐类型 1外部url2用户3服务4任务
                    "object":"1000000006",//推荐对象id或外部url
                    "title":"dfgdfg",//标题
                    "desc":"对方过后为",//描述
                    "button_word":"的"//按钮文字
                }
                ]
            }
        }
     */
    public function taskRecommendList(){

        //返回结果
        $data = [];

        try{

            //推荐活动列表 默认3个
            $taskRecommendWhere = ['delflag'=>0];
            $taskRecommendList = model('taskrecommend')->where($taskRecommendWhere)->order('sort desc')->limit(0,3)->select();
            $newTaskRecommendList = [];
            foreach($taskRecommendList as $oneTaskRecommend){
                $newTaskRecommendList[]=[
                    'type'=>$oneTaskRecommend['type'],
                    'object'=>$oneTaskRecommend['object'],
                    'title'=>$oneTaskRecommend['title'],
                    'desc'=>$oneTaskRecommend['desc'],
                    'button_word'=>$oneTaskRecommend['button_word'],
                ];
            }
            $data['recommendlist'] = $newTaskRecommendList;

            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    修改我发起的任务状态接口
     * @url     /task/changeMySendTask
     * @method  POST
     * @version 1000
     * @params  taskid 2 INT 任务id YES
     * @params  type 1 INT 要修改成任务状态类型1取消2接收成果3拒收成果 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function changeMySendTask(){
        //返回结果
        $data = [];

        //获取接口参数
        $taskId = input('request.taskid',0);
        $type = input('request.type',1);


        //验证参数是否为空
        if($taskId<=0||!in_array($type,[1,2,3])){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            //非取消状态下是否存在同标题的任务
            $taskWhere = [
                'userid'=>$this->curUserInfo['userid'],
                'taskid'=>$taskId,
                'delflag'=>0
            ];
            $task = model('task')->where($taskWhere)->find();

            if(!$task){
                $this->returndata( 14004, 'task not exist', $this->curTime, $data);
            }

            switch ($type){
                case 1:
                    if(in_array($task['state'],[0,1])){
                        $updateData=[
                            'state'=>2,
                            'updatetime'=>$this->curTime
                        ];
                        $taskDetailDesc = '取消任务';
                    }
                    else{
                        $this->returndata( 14004, 'task state error', $this->curTime, $data);
                    }
                    break;
                case 2:
                    if(in_array($task['state'],[5])){
                        $updateData=[
                            'state'=>6,
                            'updatetime'=>$this->curTime
                        ];
                        $taskDetailDesc = '任务成功';
                    }
                    else{
                        $this->returndata( 14004, 'task state error', $this->curTime, $data);
                    }
                    break;
                case 3:
                    if(in_array($task['state'],[5])){
                        $updateData=[
                            'state'=>7,
                            'updatetime'=>$this->curTime
                        ];
                        $taskDetailDesc = '任务失败';
                    }
                    else{
                        $this->returndata( 14004, 'task state error', $this->curTime, $data);
                    }
                    break;
            }

            model('task')->where($taskWhere)->update($updateData);

            //创建任务统计信息
            $newTaskDetail = [
                'taskid'        => $taskId,
                'deal_userid'   => $this->curUserInfo['userid'],
                'desc'          => $taskDetailDesc,
                'createtime'    => $this->curTime
            ];
            model('taskdetail')->insertGetId($newTaskDetail);

            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    修改我收到的任务状态接口
     * @url     /task/changeMyReceiveTask
     * @method  POST
     * @version 1000
     * @params  taskid 2 INT 任务id YES
     * @params  type 1 INT 要修改成任务状态类型1接受2拒绝 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function changeMyReceiveTask(){
        //返回结果
        $data = [];

        //获取接口参数
        $taskId = input('request.taskid',0);
        $type = input('request.type',1);


        //验证参数是否为空
        if($taskId<=0||!in_array($type,[1,2])){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            //非取消状态下是否存在同标题的任务
            $taskWhere = [
                'invited_userid'=>$this->curUserInfo['userid'],
                'taskid'=>$taskId,
                'delflag'=>0
            ];
            $task = model('task')->where($taskWhere)->find();

            if(!$task){
                $this->returndata( 14004, 'task not exist', $this->curTime, $data);
            }

            switch ($type){
                case 1:
                    if(in_array($task['state'],[1])){
                        $updateData=[
                            'state'=>3,
                            'updatetime'=>$this->curTime
                        ];
                        $taskDetailDesc = '接受任务';
                    }
                    else{
                        $this->returndata( 14004, 'task state error', $this->curTime, $data);
                    }

                    break;
                case 2:
                    if(in_array($task['state'],[1])){
                        $updateData=[
                            'state'=>4,
                            'updatetime'=>$this->curTime
                        ];
                        $taskDetailDesc = '拒绝任务';
                    }
                    else{
                        $this->returndata( 14004, 'task state error', $this->curTime, $data);
                    }
                    break;
            }

            model('task')->where($taskWhere)->update($updateData);

            //创建任务统计信息
            $newTaskDetail = [
                'taskid'        => $taskId,
                'deal_userid'   => $this->curUserInfo['userid'],
                'desc'          => $taskDetailDesc,
                'createtime'    => $this->curTime
            ];
            model('taskdetail')->insertGetId($newTaskDetail);

            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    交付任务成果接口
     * @url     /task/changeMyReceiveTask
     * @method  POST
     * @version 1000
     * @params  taskid 2 INT 任务id YES
     * @params  content '内容' STRING 任务内容描述 YES
     * @params  type 1 INT 任务媒体文件类型1图2文3音4视 YES
     * @params  urls '["a.jpg","b.jpg"]' STRING 图音视文件地址的json串 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskCreateResult(){
//返回结果
        $data = [];

        //获取接口参数
        $taskId = input('request.taskid',0);
        $content = input('request.content','');
        $type = input('request.type',1);
        $urls = input('request.urls','');


        //验证参数是否为空
        if($taskId<=0||!in_array($type,[1,2,3,4])||$content==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            //非取消状态下是否存在同标题的任务
            $taskWhere = [
                'invited_userid'=>$this->curUserInfo['userid'],
                'taskid'=>$taskId,
                'delflag'=>0
            ];
            $task = model('task')->where($taskWhere)->find();

            if(!$task){
                $this->returndata( 14004, 'task not exist', $this->curTime, $data);
            }

            $newTaskResult = [
                'userid'=>$this->curUserInfo['userid'],
                'taskid'=>$taskId,
                'content'=>$content,
                'type'=>$type,
                'urls'=>$urls,
                'createtime'=>$this->curTime,
                'delflag'=>0
            ];
            model('taskresult')->insertGetId($newTaskResult);

            //创建任务统计信息
            $newTaskDetail = [
                'taskid'        => $taskId,
                'deal_userid'   => $this->curUserInfo['userid'],
                'desc'          => '交付成功',
                'createtime'    => $this->curTime
            ];
            model('taskdetail')->insertGetId($newTaskDetail);

            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    支持或偷看任务成果接口
     * @url     /task/taskCreateSupport
     * @method  POST
     * @version 1000
     * @params  taskid 2 INT 任务id YES
     * @params  type 2 INT 类型1支持2偷看(暂时没有支持功能) YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function taskCreateSupport(){
        //返回结果
        $data = [];

        //获取接口参数
        $taskId = input('request.taskid',0);
        $type = input('request.type',1);


        //验证参数是否为空
        if($taskId<=0||!in_array($type,[2])){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            //非取消状态下是否存在同标题的任务
            $taskWhere = [
                'taskid'=>$taskId,
                'delflag'=>0
            ];
            $task = model('task')->where($taskWhere)->find();

            if(!$task){
                $this->returndata( 14002, 'task not exist', $this->curTime, $data);
            }

            //支持暂不处理
            if($type==1){
                $this->returndata( 14003,  'params error', $this->curTime, $data);
            }
            elseif($type==2){
                if($task['state']==5){
                    if(in_array($this->curUserInfo['userid'],[$task['userid'],$task['invited_userid']])){
                        $this->returndata( 14004,  'not need support', $this->curTime, $data);
                    }
                    else{
                        $newTaskSupport = [
                            'userid'=>$this->curUserInfo['userid'],
                            'taskid'=>$taskId,
                            'type'=>$type,
                            'createtime'=>$this->curTime,
                            'delflag'=>0
                        ];
                        model('tasksupport')->insertGetId($newTaskSupport);

                        //更新任务被支持数
                        $saveTaskData = [
                            'support_counter'=>['exp','support_counter+1'],
                            'updatetime'=>$this->curTime
                        ];
                        model('taskdata')->where(['taskid'=>$taskId])
                            ->update($saveTaskData);
                        //更新个人支持的任务数
                        $saveUserData = [
                            'support_counter'=>['exp','support_counter+1'],
                            'updatetime'=>$this->curTime
                        ];
                        model('userdata')->where(['userid'=>$this->curUserInfo['userid']])
                            ->update($saveUserData);
                    }
                }
                else{
                    $this->returndata( 14005,  'task state error', $this->curTime, $data);
                }

            }
            else{
                $this->returndata( 14006,  'params error', $this->curTime, $data);
            }

            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }



}
