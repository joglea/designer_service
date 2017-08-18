<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Report
 *
 * @classdesc 举报接口类
 * @package app\index\controller
 */
class Report extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    反馈类型列表接口
     * @url     /report/reportTypeList
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function reportTypeList(){

        //返回结果
        $data = [];
        try{
            $reportTypeList = model('reporttype')
                ->where(['delflag'=>0])
                ->order('sort desc')->select();

            $newReportTypeList = [];
            foreach($reportTypeList as $oneReportType){
                $newReportTypeList[]=[
                    'typeid'=>$oneReportType['typeid'],
                    'name'=>$oneReportType['name'],
                ];
            }
            $data['reporttypeList']=$newReportTypeList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建举报接口
     * @url     /report/reportCreate
     * @method  POST
     * @version 1000
     * @params  typeid 1 INT 举报类型id YES
     * @params  obj_id '1' STRING 举报对象id YES
     * @params  obj_type 2 INT 举报对象类型2用户3服务4活动5专辑6动态话题61话题评论 YES
     * @params  reason '举报援引' STRING 举报原因 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function reportCreate(){

        //返回结果
        $data = [];

        //获取接口参数
        $typeId = input('request.typeid',0);
        $objId = input('request.obj_id',0);
        $objType = input('request.obj_type',0);
        $reason = input('request.reason','');


        //验证参数是否为空
        if($typeId<=0||$objId<=0||!in_array($objType,[2,3,4,5,6,61]) || $reason==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }


        try{

            $reportWhere = [
                'status'=>1,
                'typeid'=>$typeId,
                'obj_type'=>$objType,
                'obj_id'=>$objId,
                'userid'=>$this->curUserInfo['userid']
            ];
            $report = model('report')->where($reportWhere)->find();

            if($report){
                $this->returndata( 14002, 'report  exist', $this->curTime, $data);
            }

            switch ($objType) {

                case 2:
                    $userInfo = model('userinfo')->where(['userid'=>$objId])->find();
                    if(!$userInfo){
                        $this->returndata( 14003, 'obj not  exist', $this->curTime, $data);
                    }

                    $saveUserData = [
                        'be_reported_counter'=>['exp','be_reported_counter+1'],
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
                        'be_reported_counter'=>['exp','be_reported_counter+1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('servicedata')->where(['serviceid'=>$objId])
                        ->update($saveServiceData);
                    break;
                case 4:
                    $officialTask = model('officialtask')->where(['officialtaskid'=>$objId])->find();
                    if(!$officialTask){
                        $this->returndata( 14003, 'obj not  exist', $this->curTime, $data);
                    }

                    $saveOfficialTaskData = [
                        'be_reported_counter'=>['exp','be_reported_counter+1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('officialtaskdata')->where(['officialtaskid'=>$objId])
                        ->update($saveOfficialTaskData);
                case 5:
                    $albumMain = model('albummain')->where(['albumid'=>$objId])->find();
                    if(!$albumMain){
                        $this->returndata( 14003, 'obj not  exist', $this->curTime, $data);
                    }

                    $saveAlbumData = [
                        'be_reported_counter'=>['exp','be_reported_counter+1'],
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
                        'be_reported_counter'=>['exp','be_reported_counter+1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('blogdata')->where(['blogid'=>$objId])
                        ->update($saveBlogData);
                    break;
                case 61:
                    $blogComment = model('blogcomment')->where(['commentid'=>$objId])->find();
                    if(!$blogComment){
                        $this->returndata( 14003, 'obj not  exist', $this->curTime, $data);
                    }

                    $saveBlogComment = [
                        'be_reported_counter'=>['exp','be_reported_counter+1'],
                        'updatetime'=>$this->curTime
                    ];
                    model('blogcomment')->where(['commentid'=>$objId])
                        ->update($saveBlogComment);
                    break;

            }

            //创建举报主要信息
            $newReport = [
                'reason'       => $reason,
                'status'        =>1,
                'typeid'        => $typeId,
                'obj_type'        => $objType,
                'obj_id'       => $objId,
                'userid'        => $this->curUserInfo['userid'],
                'createtime'    => $this->curTime,

            ];
            $reportId = model('report')->insertGetId($newReport);


            //更新个人举报数
            $saveUserData = [
                'report_counter'=>['exp','report_counter+1'],
                'updatetime'=>$this->curTime
            ];
            model('userdata')->where(['userid'=>$this->curUserInfo['userid']])
                ->update($saveUserData);


            $data = array(
                'reportid'=> $reportId
            );
            $this->returndata(10000, 'report success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }



}
