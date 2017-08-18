<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Feedback
 *
 * @classdesc 反馈接口类
 * @package app\index\controller
 */
class Feedback extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    反馈类型列表接口
     * @url     /feedback/feedbackTypeList
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function feedbackTypeList(){

        //返回结果
        $data = [];
        try{
            $feedbackTypeList = model('feedbacktype')
                ->where(['delflag'=>0])
                ->order('sort desc')->select();

            $newFeedbackTypeList = [];
            foreach($feedbackTypeList as $oneFeedbackType){
                $newFeedbackTypeList[]=[
                    'typeid'=>$oneFeedbackType['typeid'],
                    'name'=>$oneFeedbackType['name'],
                ];
            }
            $data['feedbacktypeList']=$newFeedbackTypeList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    创建反馈接口
     * @url     /feedback/feedbackCreate
     * @method  POST
     * @version 1000
     * @params  typeid 1 INT 反馈类型id YES
     * @params  content '反馈内容' STRING 反馈内容 YES
     * @params  contact '联系方式' STRING 联系方式 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function feedbackCreate(){

        //返回结果
        $data = [];

        //获取接口参数
        $typeId = input('request.typeid',0);
        $content = input('request.content','');
        $contact = input('request.contact','');


        //验证参数是否为空
        if($content==''||$contact==''||$typeId<=0 ){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }


        try{

            $feedbackWhere = [
                'status'=>1,
                'typeid'=>$typeId,
                'content'=>$content,
                'userid'=>$this->curUserInfo['userid']
            ];
            $feedback = model('feedback')->where($feedbackWhere)->find();

            if($feedback){
                $this->returndata( 14002, 'feedback  exist', $this->curTime, $data);
            }
            $urls = [];
            //创建反馈主要信息
            $newFeedback = [
                'status'        =>1,
                'typeid'          => $typeId,
                'content'       => $content,
                'urls'=>json_encode($urls),
                'contact'      => $contact,
                'userid'        => $this->curUserInfo['userid'],
                'createtime'    => $this->curTime,
            ];
            $feedbackId = model('feedback')->insertGetId($newFeedback);


            //更新个人反馈数
            $saveUserData = [
                'feedback_counter'=>['exp','feedback_counter+1'],
                'updatetime'=>$this->curTime
            ];
            model('userdata')->where(['userid'=>$this->curUserInfo['userid']])
                ->update($saveUserData);


            $data = array(
                'feedbackid'=> $feedbackId
            );
            $this->returndata(10000, 'feedback success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

}
