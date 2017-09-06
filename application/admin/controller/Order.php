<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 订单管理
 * Enter description here ...
 * @author jogle
 * @created on 20170906
 */
class Order extends Admin{

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

            $orderid = input('post.orderid','');
            if($orderid !='')
            {
                $where['orderid'] = $orderid;
            }
            $userid = input('post.userid','');
            if($userid !='')
            {
                $where['userid'] = $userid;
            }
            $out_order_id= input('post.out_order_id','');
            if($out_order_id !='')
            {
                $where['out_order_id'] = $out_order_id;
            }
            $taskid= input('post.taskid','');
            if($taskid !='')
            {
                $where['taskid'] = $taskid;
            }

            $pay_rate = input('post.pay_rate','-1');
            if($pay_rate !='-1')
            {
                $where['pay_rate'] = $pay_rate;
            }



            $state = input('post.state','-1');;  //
            if($state!=-1 )
            {
                $where['state'] = $state;
            }

            $createstarttime = input('post.createstarttime','');  //开始时间
            $createendtime = input('post.createendtime','');  //结束时间
            if('' != $createstarttime )
            {
                if('' != $createendtime ){
                    $where['createtime'] = array('between',array(($createstarttime),($createendtime)));
                }
                else{
                    $where['createtime'] = array('egt',($createstarttime));
                }
            }
            else{
                if('' != $createendtime )
                {
                    $where['createtime'] = array('elt',($createendtime));
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
                    $order=' orderid '.$ordercolumn[0]['dir'].' ';
                }
                elseif(1==$ordercolumn[0]['column']){
                    $order=' userid '.$ordercolumn[0]['dir'].' ';
                }
                else if(2==$ordercolumn[0]['column']){
                    $order=' out_order_id '.$ordercolumn[0]['dir'].' ';
                }
                else if(3==$ordercolumn[0]['column']){
                    $order=' taskid '.$ordercolumn[0]['dir'].' ';
                }
                else if(4==$ordercolumn[0]['column']){
                    $order=' total_price '.$ordercolumn[0]['dir'].' ';
                }
                else if(5==$ordercolumn[0]['column']){
                    $order=' pay_rate '.$ordercolumn[0]['dir'].' ';
                }
                else if(6==$ordercolumn[0]['column']){
                    $order=' state '.$ordercolumn[0]['dir'].' ';
                }
                else if(7==$ordercolumn[0]['column']){
                    $order=' createtime '.$ordercolumn[0]['dir'].' ';
                }
            }
            $where['delflag']=0;
            $userlist=model('Order')->where($where)->limit($start,$pagesize)
                ->order($order)->select();
            //var_dump(model('Order')->getLastSql());exit;
            $usercount=model('Order')->where($where)->count();
            $recordsfiltered=$usercount;
            $recordstotal=$usercount;
            //var_dump($userlist);exit;
            $data=array();
            $this->getAllControl();
            foreach($userlist as $k=>$v){
                $data[$k][]=$v['orderid'];
                $data[$k][]=$v['userid'];
                $data[$k][]=$v['out_order_id'];
                $data[$k][]=$v['taskid'];
                $data[$k][]=$v['total_price'];


                $data[$k][]=bcmul($v['pay_rate'],10).'成';


                if($v['state']==1){
                    $state = '未支付';

                }
                elseif($v['state']==2){
                    $state = '已支付';

                }
                elseif($v['state']==3){
                    $state = '支付成功';

                }
                else{
                    $state='-';
                }
                $data[$k][]=$state;
                $data[$k][]=date('Y-m-d H:i:s',$v['createtime']);
                $data[$k][]='<div style="text-align:center;">'.
                   '<a title="查看报名" id="signuplist_'.$v['orderid'].'" class="btn green btn-xs" href="javascript:;" onclick="signuplist('.$v['orderid'].',2)"><i class="fa fa-12px fa-edit"></i>查看报名</a>';
                ;
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
     *
     */
    public function signuplist(){
        if(IS_POST){

            $verifycompanyinfo = array();
            $verifycompanyinfo["companyid"] = input("post.companyid",'');
            $verifycompanyinfo["state"] = input("post.state",'');

            if(0>=$verifycompanyinfo['companyid']){
                $code=-1;
                $msg='id不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$verifycompanyinfo['state']){
                $code=-5;
                $msg='状态不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{

                $verifycompanyinfo["updatetime"] = time();


                $ret = model("verifycompany")
                    ->where(array('companyid'=>$verifycompanyinfo["companyid"]))
                    ->update($verifycompanyinfo);
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


            $allControl = $this->getAllControl();
            $orderid = input("get.orderid",'');
            $signuplist = model('orderdetail')->alias('a')
                ->join('ds_tasksignup b',' a.signupid = b.signupid ')
                ->where(['a.orderid'=>$orderid,'a.delflag'=>0,'b.delflag'=>0])
                ->field('a.orderid,a.createtime  paytime,b.*')
                ->select();
            $this->assign('orderid',$orderid);
            $this->assign('signuplist',$signuplist);
            $this->assign('allControl',$allControl);
            //var_dump($signuplist);exit;
            echo $this->fetch();
        }
    }



}