<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Workexp
 *
 * @classdesc g接口类
 * @package app\index\controller
 */
class Workexp extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    任务列表接口
     * @url     /Workexp/WorkexpList
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
                "WorkexpList":[
                {
                    "Workexpid":2,
                    "userid":1000000005,
                    "nickname":"dd都是",
                    "invited_userid":1000000006,
                    "invited_avatar":"http://omsnjcbau.bkt.clouddn.com/avatar/default01",
                    "invited_nickname":"花满楼",
                    "invited_Workexptypename":"",
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
    public function WorkexpList(){
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
            $userId = $this->curUserInfo['userid'];

            $WorkexpWhere = ['userid'=>$userId,'delflag'=>0];
            $order = 'begindate desc,expid desc';

            $WorkexpList = model('Workexp')->where($WorkexpWhere)->order($order)
                ->limit((($page-1)*$pageSize).','.$pageSize)->select();
            $WorkexpIds = [];
            $userIds = [];
            foreach($WorkexpList as $oneWorkexp){
                $WorkexpIds[]=$oneWorkexp['expid'];
            }

            if(!$WorkexpIds){
                $this->returndata( 14003, 'Workexp list is empty', $this->curTime, $data);
            }

            $this->getAllControl();

            $newWorkexpList = [];
            foreach($WorkexpList as $oneWorkexp){
                $newWorkexpList[]=[
                    'expid'=>$oneWorkexp['expid'],
                    'begindate'=>$oneWorkexp['begindate'],
                    'enddate'=>$oneWorkexp['enddate'],
                    'companyname'=>$oneWorkexp['companyname'],
                    'desc'=>$oneWorkexp['desc'],
                ];
            }

            $data['WorkexpList'] = $newWorkexpList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }

    
    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    查看任务详情
     * @url     /Workexp/WorkexpView
     * @method  GET
     * @version 1000
     * @params  Workexpid 1 INT 任务id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
        "code":10000,
        "message":"view success",
        "time":1492593379,
        "data":{
            "Workexp":{
            },
            "signup_userlist":{
            },
            
           
            
        }
    }
     *
     */
    public function WorkexpView(){

        //返回结果
        $data = [];

        //获取接口参数
        $WorkexpId = input('expid');

        if($WorkexpId <= 0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $Workexp = model('Workexp')->where(['expid'=>$WorkexpId,'delflag'=>0])->find();

            if(!$Workexp  ){
                $this->returndata( 14002, 'Workexp not exist', $this->curTime, $data);
            }

            $data['Workexp']=[
                'expid'                => $Workexp['expid'],
                'begindate'                 => $Workexp['begindate'],
                'enddate'               => $Workexp['enddate'],
                'companyname'            => $Workexp['companyname'],
                'desc'                => $Workexp['desc'],
            ];


            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }




    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    添加工作经历接口
     * @url     /Workexp/WorkexpAdd
     * @method  POST
     * @version 1000
     * @params  begindate 20160101 STRING 开始日期 YES
     * @params  enddate 20170101 STRING 结束日期 YES
     * @params  companyname 啊啊 STRING 公司名称 YES
     * @params  desc 啊 STRING 描述 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function WorkexpAdd(){
        //返回结果
        $data = [];

        //获取接口参数
        $beginDate = input('request.begindate','');
        $endDate = input('request.enddate','');
        $companyName = input('request.companyname','');
        $desc = input('request.desc','');


        //验证参数是否为空
        if($beginDate==''||$endDate==''||$companyName==''||$desc==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $newWorkexp = [
                'begindate'=>$beginDate,
                'enddate'=>$endDate,
                'companyname'=>$companyName,
                'desc'=>$desc
            ];
            $Workexpid = model('Workexp')->add($newWorkexp);

            if(!$Workexpid){
                $this->returndata( 14002, 'Workexp add fail', $this->curTime, $data);
            }

            $data['expid']=$Workexpid;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    修改工作经历接口
     * @url     /Workexp/WorkexpEdit
     * @method  POST
     * @version 1000
     * @params  expid 1 INT 经历id YES
     * @params  begindate 20160101 STRING 开始日期 YES
     * @params  enddate 20170101 STRING 结束日期 YES
     * @params  companyname 啊啊 STRING 公司名称 YES
     * @params  desc 啊 STRING 描述 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function WorkexpEdit(){
        //返回结果
        $data = [];

        //获取接口参数
        $expid = input('request.expid','');
        $beginDate = input('request.begindate','');
        $endDate = input('request.enddate','');
        $companyName = input('request.companyname','');
        $desc = input('request.desc','');


        //验证参数是否为空
        if($expid<=0||$beginDate==''||$endDate==''||$companyName==''||$desc==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $newWorkexp = [
                'begindate'=>$beginDate,
                'enddate'=>$endDate,
                'companyname'=>$companyName,
                'desc'=>$desc
            ];
            $Workexpid = model('Workexp')->where(['expid'=>$expid])->save($newWorkexp);

            if(!$Workexpid){
                $this->returndata( 14002, 'Workexp add fail', $this->curTime, $data);
            }

            $data['expid']=$Workexpid;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

}
