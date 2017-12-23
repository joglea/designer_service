<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 后台日志管理
 * Enter description here ...
 * @author jogle
 * @created on 20170813
 */
class Tasktype extends Admin{

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
                'onclick'=>"onclick='addtasktype()'",
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

            $type_name = input('post.type_name','');  //操作人ID
            if($type_name !='')
            {
                $where['type_name'] = ['like','%'.$type_name.'%'];
            }
            $type_sort = input('post.type_sort','');;  //
            if($type_sort>0 )
            {
                $where['type_sort'] = $type_sort;
            }
            

            $desc = input('post.desc','');  //描述
            if('' != $desc )
            {
                $where['desc']=array('like','%'.$desc.'%');
            }

            $pay_rate = input('post.pay_rate','0'); //
            if( $pay_rate>0&&$pay_rate<=1 )
            {
                $where['pay_rate']=$pay_rate;
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
                    $order=' tasktypeid '.$ordercolumn[0]['dir'].' ';
                }
                else if(1==$ordercolumn[0]['column']){
                    $order=' type_name '.$ordercolumn[0]['dir'].' ';
                }
                else if(2==$ordercolumn[0]['column']){
                    $order=' type_sort '.$ordercolumn[0]['dir'].' ';
                }
                else if(3==$ordercolumn[0]['column']){
                    $order=' pay_rate '.$ordercolumn[0]['dir'].' ';
                }
                else if(4==$ordercolumn[0]['column']){
                    $order=' createtime '.$ordercolumn[0]['dir'].' ';
                }
            }
            $where['delflag']=0;
            $tasktypelist=model('Tasktype')->where($where)->limit($start,$pagesize)
                ->order($order)->select();
            //var_dump($where,$tasktypelist);exit;
            $tasktypecount=model('Tasktype')->where($where)->count();
            $recordsfiltered=$tasktypecount;
            $recordstotal=$tasktypecount;
            //var_dump($this->allControl);exit;
            $data=array();
            foreach($tasktypelist as $k=>$v){
                $data[$k][]=$v['tasktypeid'];


                $data[$k][]=$v['type_name'];
                $data[$k][]=$v['type_sort'];
                $data[$k][]=$v['pay_rate'];
                $data[$k][]=date('Y-m-d H:i:s',$v['createtime']);
                $data[$k][]='<div style="text-align:center;">'.
                    '<a type_name="修改" id="edit_tasktype_'.$v['tasktypeid'].'" class="btn btn-warning btn-xs" href="javascript:;" onclick="edittasktype('.$v['tasktypeid'].')"><i class="fa fa-12px fa-edit"></i>修改</a>'.
                    '<a id="remove_tasktype_'.$v['tasktypeid'].'" class="btn btn-danger btn-xs" href="javascript:;" onclick="removetasktype('.$v['tasktypeid'].')"><i class="fa fa-12px fa-trash-o"></i>删除</a></div>';

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
     * 添加任务
     */
    public function addtasktype(){
        if(IS_POST){
            $tasktypeinfo = array();
            $tasktypeinfo["type_name"] = input("post.type_name",'');
            $tasktypeinfo["type_sort"] = input("post.type_sort",'');
            $tasktypeinfo["pay_rate"] = input("post.pay_rate",'');

            if(''==$tasktypeinfo['type_name']){
                $code=-1;
                $msg='名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(0>=$tasktypeinfo['type_sort']){
                $code=-2;
                $msg='排序不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(0>=$tasktypeinfo['pay_rate']||$tasktypeinfo['pay_rate']>=0.5){
                $code=-5;
                $msg='定金比例不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{


                $tasktypeinfo["createtime"] = time();
                $tasktypeinfo["updatetime"] = time();
                $tasktypeinfo["delflag"] = 0;
                $isExist=model("tasktype")->where(array('type_name'=>$tasktypeinfo["type_name"],'delflag'=>0))
                    ->find();

                if($isExist){
                    $code=-11;
                    $msg='相同名称已经存在';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{
                    $tasktypeid = model("tasktype")->insertGetId($tasktypeinfo);
                    $code=0;
                    $msg='添加成功';
                    $msgtype=MSG_TYPE_SUCCESS;

                }
            }
            $ret=array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

            echo json_encode($ret);exit;
        }
        else{
            echo $this->fetch();
        }
    }



    /**
     * 修改任务
     */
    public function edittasktype(){
        if(IS_POST){

            $tasktypeinfo = array();
            $tasktypeinfo["tasktypeid"] = input("post.tasktypeid",'');
            $tasktypeinfo["type_name"] = input("post.type_name",'');
            $tasktypeinfo["type_sort"] = input("post.type_sort",'');
            $tasktypeinfo["pay_rate"] = input("post.pay_rate",'');

            if(0>=$tasktypeinfo['tasktypeid']){
                $code=-1;
                $msg='id不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if(''==$tasktypeinfo['type_name']){
                $code=-1;
                $msg='名称不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(0>=$tasktypeinfo['type_sort']){
                $code=-2;
                $msg='排序不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(0>=$tasktypeinfo['pay_rate']||$tasktypeinfo['pay_rate']>=0.5){
                $code=-5;
                $msg='定金比例不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{

                $tasktypeinfo["updatetime"] = time();

                $isExist=model("tasktype")->where(array('tasktypeid'=>array('neq',$tasktypeinfo["tasktypeid"]),'type_name'=>$tasktypeinfo["type_name"],'delflag'=>0))
                    ->find();

                if($isExist){
                    $code=-8;
                    $msg='名称已经存在';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{
                    $ret = model("tasktype")->where(array('tasktypeid'=>$tasktypeinfo["tasktypeid"],'delflag'=>0))->update($tasktypeinfo);
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
            }
            $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

            echo json_encode($ret);exit;
        }
        else{
            $tasktypeid = input("get.tasktypeid",0);
            if(!($tasktypeid>0)){
                echo '参数错误';exit;
            }
            $tasktypeinfo=model('Tasktype')->where(array('tasktypeid'=>$tasktypeid,'delflag'=>0))->find();
            if(!$tasktypeinfo){
                echo '记录已被删除或不存在';exit;
            }
            $this->assign('tasktypeinfo',$tasktypeinfo);
            //var_dump($tasktypeinfo);exit;
            echo $this->fetch();
        }
    }

    /**
     * 删除任务
     */
    public function removetasktype(){
        $tasktypeid = input("post.tasktypeid",0);
        if($tasktypeid>0){
            $isExist=model('Tasktype')->where(array('tasktypeid'=>$tasktypeid,'delflag'=>0))->find();
            if($isExist){
                $ret=model("Tasktype")->where(array('tasktypeid'=>$tasktypeid,'delflag'=>0))
                ->update(['delflag'=>1,'updatetime'=>time()]);
                if($ret>0||$ret===0){
                    $code=0;
                    $msg='删除成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-1;
                    $msg='删除失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            else{
                $code=-2;
                $msg='您要删除的记录不存在或已被删除';
                $msgtype=MSG_TYPE_WARNING;
            }
        }
        else{
            $code=-3;
            $msg='参数错误';
            $msgtype=MSG_TYPE_DANGER;
        }
        $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);
        echo json_encode($ret);exit;
    }



}