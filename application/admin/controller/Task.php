<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 后台日志管理
 * Enter description here ...
 * @author jogle
 * @created on 20170813
 */
class Task extends Admin{

	/**
	 *  用户列表
	 * @param number $in 是否内部调用 0不是  1是
     * @return string
     */
	public function index($in=0){
        //var_dump($this->rolelist);exit;
        $this->setPageHeaderRightButton(
            array(
                array(
                    'class'=>'btn btn-fit-height grey-salt refreshtask',
                    'icon'=>"<i class='fa fa-plus'></i>",
                    'text'=>'刷新')
            )
        );

        if(IS_AJAX){
            $where = array();

            $title = input('post.title','');  //操作人ID
            if($title !='')
            {
                $where['title'] = ['like','%'.$title.'%'];
            }
            $price = input('post.price','0');;  //
            if($price>0 )
            {
                $where['price'] = $price;
            }
            $limitstarttime = input('post.limitstarttime','');  //开始时间
            $limitendtime = input('post.limitendtime','');  //结束时间
            if('' != $limitstarttime )
            {
                if('' != $limitendtime ){
                    $where['limittime'] = array('between',array(($limitstarttime),($limitendtime)));
                }
                else{
                    $where['limittime'] = array('egt',($limitstarttime));
                }
            }
            else{
                if('' != $limitendtime )
                {
                    $where['limittime'] = array('elt',($limitendtime));
                }
            }

            $desc = input('post.desc','');  //描述
            if('' != $desc )
            {
                $where['desc']=array('like','%'.$desc.'%');
            }

            $state = input('post.state','-1'); //
            if('-1' != $state )
            {
                $where['state']=$state;
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
            $ordercolumn=$_POST['order'];

            $order='';
            if(count($ordercolumn>0)){
                if(0==$ordercolumn[0]['column']){
                    $order=' taskid '.$ordercolumn[0]['dir'].' ';
                }
                else if(2==$ordercolumn[0]['column']){
                    $order=' title '.$ordercolumn[0]['dir'].' ';
                }
                else if(3==$ordercolumn[0]['column']){
                    $order=' price '.$ordercolumn[0]['dir'].' ';
                }
                else if(4==$ordercolumn[0]['column']){
                    $order=' limittime '.$ordercolumn[0]['dir'].' ';
                }
                else if(6==$ordercolumn[0]['column']){
                    $order=' state '.$ordercolumn[0]['dir'].' ';
                }
                else if(7==$ordercolumn[0]['column']){
                    $order=' createtime '.$ordercolumn[0]['dir'].' ';
                }
            }

            $tasklist=model('Task')->where($where)->limit($start,$pagesize)
                ->order($order)->select();
            //var_dump($where,$tasklist);exit;
            $taskcount=model('Task')->where($where)->count();
            $recordsfiltered=$taskcount;
            $recordstotal=$taskcount;
            //var_dump($this->allControl);exit;
            $data=array();
            foreach($tasklist as $k=>$v){
                $data[$k][]=$v['taskid'];
                $data[$k][]='<a class="fancybox"  href="'.$this->allControl['task_image_url'].$v['task_pic'] .'" data-fancybox-group="blogpicurl" title="'.$v['title'].'--&lt;'.'&gt;"><img style="max-width:120px;max-height:120px;" src="'.$this->allControl['task_image_url'].$v['task_pic'].'" /></a>';

                $data[$k][]=$v['title'];
                $data[$k][]=$v['price'];
                $data[$k][]=date('Y-m-d H:i:s',$v['limittime']);
                $data[$k][]=$v['desc'];
                //任务状态 1新建接受报名 2停止报名 3订金预付 4尾款支付 5关闭
                if($v['state']==1){
                    $state= '新建接受报名';
                }
                elseif($v['state']==2){
                    $state= '停止报名';
                }
                elseif($v['state']==3){
                    $state= '订金预付';
                }
                elseif($v['state']==4){
                    $state= '尾款支付';
                }
                elseif($v['state']==5){
                    $state= '关闭';
                }
                else{
                    $state='-';
                }

                $data[$k][]=$state;
                $data[$k][]=date('Y-m-d H:i:s',$v['createtime']);
                $data[$k][]='<div style="text-align:center;">'.
                    '<a id="remove_task_'.$v['taskid'].'" class="btn btn-danger btn-xs" href="javascript:;" onclick="return 0;removetask('.$v['taskid'].')"><i class="fa fa-12px fa-trash-o"></i>删除</a></div>';

            }
            //var_dump($data);exit;
            //var_dump($where,$this->usercount,$this->userlist);exit;
            echo json_encode(array("data"=>$data,"draw"=>$draw,"recordsTotal"=>$recordstotal,"recordsFiltered"=>$recordsfiltered));exit;

        }
        else{
            return $this->fetch();
        }
	}

}