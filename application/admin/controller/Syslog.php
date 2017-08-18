<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 后台日志管理
 * Enter description here ...
 * @author jogle
 * @created on 20170813
 */
class Syslog extends Admin{

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
                    'class'=>'btn btn-fit-height grey-salt refreshsyslog',
                    'icon'=>"<i class='fa fa-plus'></i>",
                    'text'=>'刷新')
            )
        );

        if(IS_AJAX){
            $where = array();
            $starttime = input('post.starttime','');  //开始时间
            $endtime = input('post.endtime','');  //结束时间
            if('' != $starttime )
            {
                if('' != $endtime ){
                    $where['createtime'] = array('between',array(($starttime),($endtime)));
                }
                else{
                    $where['createtime'] = array('egt',($starttime));
                }
            }
            else{
                if('' != $endtime )
                {
                    $where['createtime'] = array('elt',($endtime));
                }
            }

            $creatorid = input('post.creatorid','0');  //操作人ID
            if($creatorid > 0)
            {
                $where['creatorid'] = $creatorid;
            }


            $description = input('post.description','');  //描述
            if('' != $description )
            {
                $where['description']=array('like','%'.$description.'%');
            }
            $ip = input('post.ip','');;  //ip
            if('' != $ip )
            {
                $where['ip'] = $ip;
            }

            $moduleidentity = input('post.moduleidentity',''); //模块
            if('' != $moduleidentity )
            {
                $where['moduleidentity']=$moduleidentity;
            }

            $rightname = input('post.rightname',''); //操作
            if('' != $rightname )
            {
                $where['rightname']=$rightname;
            }

            $draw = input('post.draw','1');
            $start = input('post.start','0');
            $pagesize = input('post.length',config('PAGE_SIZE')>0?config('PAGE_SIZE'):10);

            //tp3.1 I方法获取数据时  提交参数为[]数组时  会取不到高维数据
            $ordercolumn=$_POST['order'];

            $order='';
            if(count($ordercolumn>0)){
                if(1==$ordercolumn[0]['column']){
                    $order=' creatorid '.$ordercolumn[0]['dir'].' ';
                }
                else if(5==$ordercolumn[0]['column']){
                    $order=' moduleidentity '.$ordercolumn[0]['dir'].' ';
                }
                else if(6==$ordercolumn[0]['column']){
                    $order=' rightname '.$ordercolumn[0]['dir'].' ';
                }
                else if(7==$ordercolumn[0]['column']){
                    $order=' createtime '.$ordercolumn[0]['dir'].' ';
                }
            }

            $sysloglist=model('Syslog')->where($where)->limit($start,$pagesize)
                ->order($order)->select();
            $syslogcount=model('Syslog')->where($where)->count();
            $recordsfiltered=$syslogcount;
            $recordstotal=$syslogcount;
            $data=array();
            foreach($sysloglist as $k=>$v){
                $data[$k][]=$v['syslogid'];
                $data[$k][]=$v['creatorid'];
                $data[$k][]=$v['description'];
                $data[$k][]=$v['ip'];
                $data[$k][]=$v['moduleidentity'];
                $data[$k][]=$v['rightname'];
                $data[$k][]=date('Y-m-d H:i:s',$v['createtime']);
                $data[$k][]='<div style="text-align:center;">'.
                    '<a id="remove_log_'.$v['syslogid'].'" class="btn btn-danger btn-xs" href="javascript:;" onclick="return 0;removelog('.$v['syslogid'].')"><i class="fa fa-12px fa-trash-o"></i>删除</a></div>';

            }
            //var_dump($data);exit;
            //var_dump($where,$this->usercount,$this->userlist);exit;
            echo json_encode(array("data"=>$data,"draw"=>$draw,"recordsTotal"=>$recordstotal,"recordsFiltered"=>$recordsfiltered));exit;

        }
        else{
            return $this->fetch();
            $this->_display();
        }
	}

}