<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 充值管理
 * Enter description here ...
 * @author jogle
 * @created on 20180126
 */
class Walletrecharge extends Admin{

	/**
	 *  用户列表
	 * @param number $in 是否内部调用 0不是  1是
     * @return string
     */
	public function index($view='index'){
        //var_dump($this->rolelist);exit;
        $this->setPageHeaderRightButton(
            array(array(
                'class'=>'btn btn-fit-height grey-salt',
                'onclick'=>"onclick='addrecharge()'",
                'icon'=>"<i class='fa fa-plus'></i>",
                'text'=>'添加'),
                array(
                    'class'=>'btn btn-fit-height grey-salt refresh',
                    'icon'=>"<i class='fa fa-plus'></i>",
                    'text'=>'刷新')
            )
        );

        if(IS_AJAX&&$view=='index'){
            $where = array();

            $walletrechargeid = input('post.walletrechargeid','');
            if($walletrechargeid !='')
            {
                $where['walletrechargeid'] = $walletrechargeid;
            }
            $userid = input('post.userid','');
            if($userid !='')
            {
                $where['userid'] = $userid;
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
                    $order=' walletrechargeid '.$ordercolumn[0]['dir'].' ';
                }
                elseif(1==$ordercolumn[0]['column']){
                    $order=' userid '.$ordercolumn[0]['dir'].' ';
                }
                else if(2==$ordercolumn[0]['column']){
                    $order=' money '.$ordercolumn[0]['dir'].' ';
                }
                else if(3==$ordercolumn[0]['column']){
                    $order=' createtime '.$ordercolumn[0]['dir'].' ';
                }
            }
            $where['delflag']=0;
            $userlist=model('Walletrecharge')->where($where)->limit($start,$pagesize)
                ->order($order)->select();
            //var_dump(model('Walletrecharge')->getLastSql());exit;
            $usercount=model('Walletrecharge')->where($where)->count();
            $recordsfiltered=$usercount;
            $recordstotal=$usercount;
            //var_dump($userlist);exit;
            $data=array();
            $this->getAllControl();
            foreach($userlist as $k=>$v){
                $data[$k][]=$v['walletrechargeid'];
                $data[$k][]=$v['userid'];
                $data[$k][]=$v['money'];

                $data[$k][]=date('Y-m-d H:i:s',$v['createtime']);
                $data[$k][]='<div style="text-align:center;">'.

                    //'<a title="查看报名" id="signuplist_'.$v['walletrechargeid'].'" class="btn green btn-xs" href="javascript:;" onclick="signuplist('.$v['walletrechargeid'].',2)"><i class="fa fa-12px fa-search"></i>查看报名</a>'.
                    //'<a title="修改" id="editwalletrecharge_'.$v['walletrechargeid'].'" class="btn green btn-xs" href="javascript:;" onclick="editwalletrecharge('.$v['walletrechargeid'].',)"><i class="fa fa-12px fa-edit"></i>修改</a>';
                    '</div>';
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
     * 添加充值
     */
    public function addrecharge(){
        if(IS_POST){
            $rechargeinfo = array();
            $rechargeinfo["userid"] = input("post.userid",'');
            $rechargeinfo["money"] = input("post.money",'');

            if(''==$rechargeinfo['userid']){
                $code=-1;
                $msg='用户id不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$rechargeinfo['money']){
                $code=-2;
                $msg='充值金额不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{
                $rechargeinfo["createtime"] = time();
                $rechargeinfo["delflag"] = 0;

                $rechargeid = model("Walletrecharge")->insertGetId($rechargeinfo);

                if($rechargeid>0){

                    $wallet=model("wallet")->where(array('userid'=>$rechargeinfo["userid"]))
                        ->find();

                    if(!$wallet){
                        $wallet = [
                            'userid'=>$rechargeinfo['userid'],
                            'now_money'=>$rechargeinfo["money"],
                            'createtime'=>$this->curTime,
                            'updatetime'=>$this->curTime,
                        ];
                        //更新余额
                        model('wallet')->insertGetId($wallet);
                    }
                    else{
                        $balance = bcadd($wallet['now_money'],$rechargeinfo['money'],2);
                        $savewallet = [
                            'now_money'=>$balance,
                            'updatetime'=>$this->curTime,
                        ];
                        //更新余额
                        model('wallet')
                            ->where(['userid'=>$rechargeinfo['userid']])->update($savewallet);
                    }

                    //余额变更记录
                    model('walletrecord')->insertGetId(
                        [
                            'userid'=>$rechargeinfo['userid'],
                            'objectid'=>$rechargeid,
                            'objecttype'=>3,//对象类型1支出2收入3充值
                            'price'=>$rechargeinfo['money'],
                            'desc'=>'充值订单id：'.$rechargeid.'',
                            'createtime'=>$this->curTime,
                            'updatetime'=>$this->curTime,
                            'delflag'=>0,
                        ]
                    );

                    //$unique_id = MD5(C('PAGE_UNIQUE_CODE').$rechargeinfo["userid"].time());
                    //model("Page")->editbywhere(array('rechargeid'=>$rechargeid),array('unique_id'=>$unique_id));
                    $code=0;
                    $msg='添加成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-12;
                    $msg='添加失败';
                    $msgtype=MSG_TYPE_DANGER;
                }

            }
            $ret=array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

            echo json_encode($ret);exit;
        }
        else{

            echo $this->fetch();
        }
    }




}