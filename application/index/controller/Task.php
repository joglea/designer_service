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
     * @desc    我发布的任务列表接口
     * @url     /task/myTaskList
     * @method  GET
     * @version 1000
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function myTaskList(){


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
        if($page<1||$taskid<=0){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
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
     * @params  pics ["aa.jpg"] STRING 图片列表 YES
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
        if($taskid<=0 || $desc==''||!$newPics){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $task = model('task')->where(['taskid'=>$taskid,'delflag'=>0])->find();
            if(!$task||!($task['check_state']==2&&$task['state']==1)){
                $this->returndata( 14002, 'task can not signup ', $this->curTime, $data);
            }

            $signup = model('tasksignup')->where([
                'taskid'=>$taskid,
                'userid'=>$this->curUserInfo['userid'],'delflag'=>0])->find();
            if($signup){
                $this->returndata( 14002, 'signup exist', $this->curTime, $data);
            }

            $newtaskSignup = [
                'taskid'=>$taskid,
                'userid'=>$this->curUserInfo['userid'],
                'desc'=>$desc,
                'pics'=>json_encode($newPics),
                'suit_state'=>1,//被商家选中的适合报名状态 1初稿未选中 2初稿被选中 3最终选中 4最终未选中
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0,
            ];
            $signupid = model('tasksignup')->insertGetId($newtaskSignup);

            if(!$signupid){
                $this->returndata( 14002, 'signup  fail', $this->curTime, $data);
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

        Db::startTrans();
        try{

            $task = model('task')
                ->where(['userid'=>$this->curUserInfo['userid'],
                         'taskid'=>$taskId,'delflag'=>0])
                ->find();
            if(!$task){
                $this->returndata( 14002, 'task not exist', $this->curTime, $data);
            }
            $signupList = model('tasksignup')->where(
                ['signupid'=>['in',$signupIdsArr],'suit_state'=>1,'delflag'=>0]);
            if(!$signupList){
                $this->returndata( 14003, 'signup not exist or state error', $this->curTime, $data);
            }
            $wallet = model('wallet')
                ->where(['userid'=>$this->curUserInfo['userid']])->find();
            if(!$wallet){
                $this->returndata( 14003, 'wallet not exist ', $this->curTime, $data);
            }
            $price = bcmul($task['price'],config('desposit_rate'),2);
            $balance = $wallet['now_money']-$price;
            if($balance<0){
                $this->returndata( 14003, 'wallet balance insufficient ', $this->curTime, $data);
            }


            $order = [
                'userid'=>$this->curUserInfo['userid'],
                'taskid'=>$taskId,
                'total_price'=>$task['price'],
                'pay_rate'=>config('desposit_rate'),
                'state'=>2,
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0,
            ];
            //创建订单
            $orderid = model('order')->insertGetId($order);

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

            $date = [
                'now_money'=>$balance,
                'updatetime'=>$this->curTime,
            ];
            //更新余额
            $walletUpdate = model('wallet')
                ->where(['userid'=>$this->curUserInfo['userid']])->update($date);
            //余额变更记录
            model('walletrecord')->insertGetId(
                [
                    'objectid'=>$orderid,
                    'objecttype'=>1,//对象类型1支出2收入3充值
                    'price'=>$price,
                    'desc'=>'任务：'.$taskId.'的支付定金',
                    'createtime'=>$this->curTime,
                    'updatetime'=>$this->curTime,
                    'delflag'=>0,

                ]
            );
            Db::commit();
            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            // 回滚事务
            Db::rollback();
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

        Db::startTrans();
        try{

            $task = model('task')
                ->where(['userid'=>$this->curUserInfo['userid'],
                         'taskid'=>$taskId,'delflag'=>0])
                ->find();
            if(!$task){
                $this->returndata( 14002, 'task not exist', $this->curTime, $data);
            }
            $signupList = model('tasksignup')->where(
                ['signupid'=>$signupId,'suit_state'=>2,'delflag'=>0]);
            if(!$signupList){
                $this->returndata( 14003, 'signup not exist or state error', $this->curTime, $data);
            }
            $wallet = model('wallet')
                ->where(['userid'=>$this->curUserInfo['userid']])->find();
            if(!$wallet){
                $this->returndata( 14003, 'wallet not exist ', $this->curTime, $data);
            }
            $price = bcmul($task['price'],config('tail_rate'),2);
            $balance = $wallet['now_money']-$price;
            if($balance<0){
                $this->returndata( 14003, 'wallet balance insufficient ', $this->curTime, $data);
            }

            $order = [
                'userid'=>$this->curUserInfo['userid'],
                'taskid'=>$taskId,
                'total_price'=>$task['price'],
                'pay_rate'=>config('tail_rate'),
                'state'=>2,
                'createtime'=>$this->curTime,
                'updatetime'=>$this->curTime,
                'delflag'=>0,
            ];
            //创建订单
            $orderid = model('order')->insertGetId($order);

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
                    ->update(['suit_state'=>3,'updatetime'=>$this->curTime]);
            }

            $date = [
                'now_money'=>$balance,
                'updatetime'=>$this->curTime,
            ];
            //更新余额
            $walletUpdate = model('wallet')
                ->where(['userid'=>$this->curUserInfo['userid']])->update($date);
            //余额变更记录
            model('walletrecord')->insertGetId(
                [
                    'objectid'=>$orderid,
                    'objecttype'=>1,//对象类型1支出2收入3充值
                    'price'=>$price,
                    'desc'=>'任务：'.$taskId.'的支付尾款',
                    'createtime'=>$this->curTime,
                    'updatetime'=>$this->curTime,
                    'delflag'=>0,

                ]
            );
            Db::commit();
            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            // 回滚事务
            Db::rollback();
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

}
