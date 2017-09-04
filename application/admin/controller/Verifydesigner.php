<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 设计师认证管理
 * Enter description here ...
 * @author jogle
 * @created on 20170904
 */
class Verifydesigner extends Admin{

	/**
	 *  用户列表
	 * @param number $in 是否内部调用 0不是  1是
     * @return string
     */
	public function index($view='index'){
        //var_dump($this->rolelist);exit;
        $this->setPageHeaderRightButton(
            array(
                array(
                    'class'=>'btn btn-fit-height grey-salt refresh',
                    'icon'=>"<i class='fa fa-plus'></i>",
                    'text'=>'刷新')
            )
        );

        if(IS_AJAX&&$view=='index'){
            $where = array();

            $userid = input('post.userid','');
            if($userid !='')
            {
                $where['a.userid'] = $userid;
            }

            $nickname = input('post.nickname','');
            if($nickname !='')
            {
                $where['a.nickname'] = ['like','%'.$nickname.'%'];
            }

            $truename = input('post.truename','');
            if($truename !='')
            {
                $where['b.truename'] = ['like','%'.$truename.'%'];
            }
            $idcard = input('post.idcard','');;  //
            if($idcard!='' )
            {
                $where['b.idcard'] = $idcard;
            }


            $state = input('post.state','-1');;  //
            if($state!=-1 )
            {
                $where['b.state'] = $state;
            }

            $createstarttime = input('post.createstarttime','');  //开始时间
            $createendtime = input('post.createendtime','');  //结束时间
            if('' != $createstarttime )
            {
                if('' != $createendtime ){
                    $where['b.createtime'] = array('between',array(($createstarttime),($createendtime)));
                }
                else{
                    $where['b.createtime'] = array('egt',($createstarttime));
                }
            }
            else{
                if('' != $createendtime )
                {
                    $where['b.createtime'] = array('elt',($createendtime));
                }
            }

            $draw = input('post.draw','1');
            $start = input('post.start','0');
            $pagesize = input('post.length',config('PAGE_SIZE')>0?config('PAGE_SIZE'):10);

            //tp3.1 I方法获取数据时  提交参数为[]数组时  会取不到高维数据
            $ordercolumn=isset($_POST['order'])?$_POST['order']:[];

            $order='';
            if(count($ordercolumn)>0){
                if(0==$ordercolumn[0]['column']){
                    $order=' b.designerid '.$ordercolumn[0]['dir'].' ';
                }
                elseif(1==$ordercolumn[0]['column']){
                    $order=' a.userid '.$ordercolumn[0]['dir'].' ';
                }
                else if(2==$ordercolumn[0]['column']){
                    $order=' a.nickname '.$ordercolumn[0]['dir'].' ';
                }
                else if(3==$ordercolumn[0]['column']){
                    $order=' b.designername '.$ordercolumn[0]['dir'].' ';
                }
                else if(4==$ordercolumn[0]['column']){
                    $order=' b.businesslicense '.$ordercolumn[0]['dir'].' ';
                }
                else if(6==$ordercolumn[0]['column']){
                    $order=' b.truename '.$ordercolumn[0]['dir'].' ';
                }
                else if(7==$ordercolumn[0]['column']){
                    $order=' b.idcard '.$ordercolumn[0]['dir'].' ';
                }
                else if(9==$ordercolumn[0]['column']){
                    $order=' b.state '.$ordercolumn[0]['dir'].' ';
                }
                else if(10==$ordercolumn[0]['column']){
                    $order=' b.createtime '.$ordercolumn[0]['dir'].' ';
                }
            }
            $where['b.delflag']=0;
            $userlist=model('Verifydesigner')->alias('b')
                ->join('ds_userinfo a',' b.userid = a.userid ')->where($where)->limit($start,$pagesize)
                ->order($order)->field('a.nickname,b.*')->select();
            //var_dump(model('Verifydesigner')->getLastSql());exit;
            $usercount=model('Verifydesigner')->alias('b')
                ->join('ds_userinfo a','b.userid=a.userid')->where($where)->count();
            $recordsfiltered=$usercount;
            $recordstotal=$usercount;
            //var_dump($userlist);exit;
            $data=array();
            $this->getAllControl();
            foreach($userlist as $k=>$v){
                $data[$k][]=$v['designerid'];
                $data[$k][]=$v['userid'];
                $data[$k][]=$v['nickname'];

                $data[$k][]=$v['truename'];
                $data[$k][]=$v['idcard'];
                if(substr($v['idcard_pic'],0,4)=='http'){
                    $newimgurl=$v['idcard_pic'];
                }
                else{
                    $newimgurl=$this->allControl['verify_pic_url'].$v['idcard_pic'];
                }
                $picturestr=
                    '<a class="fancybox" style="margin:0 3px" href="'.$newimgurl .
                    '" data-fancybox-group="userpicurl" ><img style="margin:2px 0;max-width:36px;max-height:36px;" src="'.$newimgurl.'" /></a>'.
                    '';
                $data[$k][]=$picturestr;

                if($v['state']==1){
                    $state = '发起中';
                    $state_btn = '<a title="通过" id="channge_state_'.$v['designerid'].'" class="btn green btn-xs" href="javascript:;" onclick="changestate('.$v['designerid'].',2)"><i class="fa fa-12px fa-edit"></i>通过</a>';
                    $state_btn .= '<a title="不通过" id="channge_state_'.$v['designerid'].'" class="btn red btn-xs" href="javascript:;" onclick="changestate('.$v['designerid'].',3)"><i class="fa fa-12px fa-edit"></i>不通过</a>';

                }
                elseif($v['state']==2){
                    $state = '通过';
                    $state_btn = '<a title="不通过" id="channge_state_'.$v['designerid'].'" class="btn red btn-xs" href="javascript:;" onclick="changestate('.$v['designerid'].',3)"><i class="fa fa-12px fa-edit"></i>不通过</a>';

                }
                elseif($v['state']==3){
                    $state = '不通过';
                    $state_btn = '<a title="通过" id="channge_state_'.$v['designerid'].'" class="btn green btn-xs" href="javascript:;" onclick="changestate('.$v['designerid'].',2)"><i class="fa fa-12px fa-edit"></i>通过</a>';

                }
                else{
                    $state='-';
                    $state_btn='';
                }
                $data[$k][]=$state;
                $data[$k][]=date('Y-m-d H:i:s',$v['createtime']);
                $data[$k][]='<div style="text-align:center;">'.
                    $state_btn;
                    //'<a id="remove_user_'.$v['userid'].'" class="btn btn-danger btn-xs" href="javascript:;" onclick="removeuser('.$v['userid'].')"><i class="fa fa-12px fa-trash-o"></i>删除</a></div>';
            }
            //var_dump($data);exit;
            //var_dump($where,$this->usercount,$this->userlist);exit;
            echo json_encode(array("data"=>$data,"draw"=>$draw,"recordsTotal"=>$recordstotal,"recordsFiltered"=>$recordsfiltered));exit;
        }
        else{
            return $this->fetch($view);
        }
	}




    /**
     * 修改状态
     */
    public function changestate(){
        if(IS_POST){

            $verifydesignerinfo = array();
            $verifydesignerinfo["designerid"] = input("post.designerid",'');
            $verifydesignerinfo["state"] = input("post.state",'');

            if(0>=$verifydesignerinfo['designerid']){
                $code=-1;
                $msg='id不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$verifydesignerinfo['state']){
                $code=-5;
                $msg='状态不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{

                $verifydesignerinfo["updatetime"] = time();


                $ret = model("verifydesigner")
                    ->where(array('designerid'=>$verifydesignerinfo["designerid"]))
                    ->update($verifydesignerinfo);
                //var_dump($ret);exit;
                if($ret>0||0===$ret){
                    $code=0;
                    $msg='保存成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-9;
                    $msg='保存失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

            echo json_encode($ret);exit;
        }
        else{

            echo $this->fetch();
        }
    }



}