<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Educationexp
 *
 * @classdesc 教育经验接口类
 * @package app\index\controller
 */
class Educationexp extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    教育经验列表接口
     * @url     /Educationexp/EducationexpList
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
                "EducationexpList":[
                {
                    "Educationexpid":2,
                    "userid":1000000005,
                    "nickname":"dd都是",
                    "invited_userid":1000000006,
                    "invited_avatar":"http://omsnjcbau.bkt.clouddn.com/avatar/default01",
                    "invited_nickname":"花满楼",
                    "invited_Educationexptypename":"",
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
    public function EducationexpList(){
        //返回结果
        $data = [];
        $pageSize = config('page_size');
        //获取接口参数
        $page = input('request.page',1,'intval');
        //验证参数是否为空
        if($page<1){
            $page=1;
        }

        try{
            $userId = $this->curUserInfo['userid'];

            $EducationexpWhere = ['userid'=>$userId,'delflag'=>0];
            $order = 'begindate desc,expid desc';

            $EducationexpList = model('educationexp')->where($EducationexpWhere)->order($order)
                ->limit((($page-1)*$pageSize).','.$pageSize)->select();
            $EducationexpIds = [];
            $userIds = [];
            foreach($EducationexpList as $oneEducationexp){
                $EducationexpIds[]=$oneEducationexp['expid'];
            }

            if(!$EducationexpIds){
                $this->returndata( 14003, '', $this->curTime, $data);
            }

            $this->getAllControl();

            $newEducationexpList = [];
            foreach($EducationexpList as $oneEducationexp){
                $newEducationexpList[]=[
                    'expid'=>$oneEducationexp['expid'],
                    'begindate'=>$oneEducationexp['begindate'],
                    'enddate'=>$oneEducationexp['enddate'],
                    'schoolname'=>$oneEducationexp['schoolname'],
                    'desc'=>$oneEducationexp['desc'],
                ];
            }

            $data['EducationexpList'] = $newEducationexpList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }

    
    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    查看教育经验详情
     * @url     /Educationexp/EducationexpView
     * @method  GET
     * @version 1000
     * @params  expid 1 INT 教育经验id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
    "code":10000,
    "message":"view success",
    "time":1492593379,
    "data":{
    "Educationexp":{
    "expid":1,
    "begindate":"2017-01-01",
    "enddate":"2017-07-07",
    "schoolname":"xxx",
    "desc":"xxx"
    }
    }
    }
     *
     */
    public function EducationexpView(){

        //返回结果
        $data = [];

        //获取接口参数
        $EducationexpId = input('expid');

        if($EducationexpId <= 0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $Educationexp = model('Educationexp')->where(['expid'=>$EducationexpId,'delflag'=>0])->find();

            if(!$Educationexp  ){
                $this->returndata( 14002, 'Educationexp not exist', $this->curTime, $data);
            }

            $data['Educationexp']=[
                'expid'                => $Educationexp['expid'],
                'begindate'                 => $Educationexp['begindate'],
                'enddate'               => $Educationexp['enddate'],
                'schoolname'            => $Educationexp['schoolname'],
                'desc'                => $Educationexp['desc'],
            ];


            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }




    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    添加教育经历接口
     * @url     /Educationexp/EducationexpAdd
     * @method  POST
     * @version 1000
     * @params  begindate 20160101 STRING 开始日期 YES
     * @params  enddate 20170101 STRING 结束日期 YES
     * @params  schoolname 啊啊 STRING 公司名称 YES
     * @params  desc 啊 STRING 描述 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function EducationexpAdd(){
        //返回结果
        $data = [];

        //获取接口参数
        $beginDate = input('request.begindate','');
        $endDate = input('request.enddate','');
        $companyName = input('request.schoolname','');
        $desc = input('request.desc','');


        //验证参数是否为空
        if($beginDate==''||$endDate==''||$companyName==''||$desc==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $newEducationexp = [
                'begindate'=>$beginDate,
                'enddate'=>$endDate,
                'schoolname'=>$companyName,
                'desc'=>$desc
            ];
            $Educationexpid = model('Educationexp')->insertGetId($newEducationexp);

            if(!$Educationexpid){
                $this->returndata( 14002, 'Educationexp add fail', $this->curTime, $data);
            }

            $data['expid']=$Educationexpid;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    修改教育经历接口
     * @url     /Educationexp/EducationexpEdit
     * @method  POST
     * @version 1000
     * @params  expid 1 INT 经历id YES
     * @params  begindate 20160101 STRING 开始日期 YES
     * @params  enddate 20170101 STRING 结束日期 YES
     * @params  schoolname 啊啊 STRING 公司名称 YES
     * @params  desc 啊 STRING 描述 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function EducationexpEdit(){
        //返回结果
        $data = [];

        //获取接口参数
        $expid = input('request.expid','');
        $beginDate = input('request.begindate','');
        $endDate = input('request.enddate','');
        $companyName = input('request.schoolname','');
        $desc = input('request.desc','');


        //验证参数是否为空
        if($expid<=0||$beginDate==''||$endDate==''||$companyName==''||$desc==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $newEducationexp = [
                'begindate'=>$beginDate,
                'enddate'=>$endDate,
                'schoolname'=>$companyName,
                'desc'=>$desc
            ];
            $Educationexpid = model('Educationexp')->where(['expid'=>$expid])->update($newEducationexp);

            if(!$Educationexpid){
                $this->returndata( 14002, 'Educationexp edit fail', $this->curTime, $data);
            }

            $data['expid']=$Educationexpid;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }



    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    删除自己的经验
     * @url     /Educationexp/EducationexpDel
     * @method  POST
     * @version 1000
     * @params  expid 1 INT 经历id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function EducationexpDel(){

        //返回结果
        $data = [];

        //获取接口参数
        $expId = input('expid',0);

        if($expId<0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $educationexp = model('educationexp')
                ->where(['userid'=>$this->curUserInfo['userid'],'expid'=>$expId,'delflag'=>0])
                ->find();
            if(!$educationexp){
                $this->returndata( 14002, 'exp not exist', $this->curTime, $data);
            }
            model('educationexp')
                ->where(['userid'=>$this->curUserInfo['userid'],'expid'=>$expId,'delflag'=>0])
                ->update(['delflag'=>1,'updatetime'=>$this->curTime]);


            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


}
