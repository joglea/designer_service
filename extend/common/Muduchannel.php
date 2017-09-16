<?php
namespace common;
/*
	--------------------------------------------------
	目睹api
	--------------------------------------------------
*/
class Muduchannel{
	private $url = '';
	private $access_token = '';
	private $header = '';

	public function __construct()
	{
		$this->url=config('mudu_api_url');
		$this->access_token=config('mudu_api_token');
		$this->header="Authorization:Bearer ".config('mudu_api_token');
	}
//------------------------------------------------------用户体系	
	/*频道api*/
	/**
	*@param  live     string    直播状态
	*@param  manager  string    管理员id   
	*获取频道列表
	*/
	function get_channel_list($live,$manager){
		$url = $this->url.'activities';
		$options=array(
			'live' => $live,
			'manager' => $manager,
			);
		$body = http_build_query($options);
		$url = $url.$body;
		$header=array($this->header);
		$result=$this->postCurl($url,'',$header,"GET");
		return $result;
	}
	/**
	*@param  id     string    频道id
	*获取指定频道信息
	*/
	function get_channel_data($id){
		$url = $this->url.'activities/'.$id;
		$header=array($this->header);
		$result=$this->postCurl($url,'',$header,"GET");
		return $result;
	}
	/**
	*@param  name     string    频道名称
	*@param  start_time  string   创建时间   
	*创建频道
	*/
	function add_channel($name,$start_time){
		$url = $this->url.'activities';
		$options=array(
			"name"=>$name,
			"start_time"=>$start_time
		);
		$body=json_encode($options);
		$header=array($this->header);
		$result=$this->postCurl($url,$body,$header);
		return $result;
	}
	/**
	*@param  name     string    频道id
	*@param  start_time  string  频道名称 
	*修改频道*/
	function edit_channel($id,$name){
		$url = $this->url.'activities/'.$id;
		$options=array(
			"name"=>$name,
		);
		$body=json_encode($options);
		$header=array($this->header);
		$result=$this->postCurl($url,$body,$header,"PUT");
		return $result;
	}
	/**
	*@param  name     string    频道id
	*删除频道*/
	function del_channel($id){
		$url=$this->url.'activities/'.$id;
		$header=array($this->header);
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
/********************************************************************************/
	/*媒体api*/
	/**
	*@param  title     string    直播状态
	*@param  act_id    string    视频所属频道号
	*@param  manager  string    频道管理员id   
	*获取频道列表
	*/
	function get_channel_movie_list($title,$act_id,$manager,$p,$perPage){
		$url = $this->url.'videos?';
		$options=array(
			'title' => $title,
			'act_id' => $act_id,
			'manager' => $manager,
			'p' => $p,
			'perPage' => $perPage
			);
		//var_dump($options);exit;
		$body = http_build_query($options);
		$url = $url.$body;
		$header=array($this->header);
		$result=$this->postCurl($url,'',$header,"GET");
		return $result;
	}
	/**
	*@param  id     string    频道id
	*获取指定视频信息
	*/
	function get_channel_movie_data($id){
		$url = $this->url.'videos/'.$id;
		$header=array($this->header);
		$result=$this->postCurl($url,'',$header,"GET");
		return $result;
	}
	/**
	*@param  name     string    频道id
	*删除视频*/
	function del_movie($id){
		$url=$this->url.'videos/'.$id;
		$header=array($this->header);
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
	/**
	*@param  name     string    频道id
	*获取频道观看用户列表*/
	function get_visitors($id,$perPage='100',$p='1'){
		$url=$this->url.'activities/'.$id.'/visitors';
		$options=array(
			"p" => $p,
			"perPage" => $perPage
		);
		$body=json_encode($options);
		$header=array($this->header);
		$result=$this->postCurl($url,'',$header,'post');
		return $result;
	}

	/**
	 *@param  name     string    频道id
	 *获取频道报表*/
	function get_report($id){
		$url = $this->url.'activities/'.$id.'/report';
		$header=array($this->header);
		$result=$this->postCurl($url,'',$header,"GET");
		return $result;
	}

	/**
	 *$this->postCurl方法
	 */
	function postCurl($url,$body,$header,$type="POST"){
		//1.创建一个curl资源
		$ch = curl_init();
		//2.设置URL和相应的选项
		curl_setopt($ch,CURLOPT_URL,$url);//设置url
		//1)设置请求头
		//array_push($header, 'Accept:application/json');
		//array_push($header,'Content-Type:application/json');
		//array_push($header, 'http:multipart/form-data');
		//设置为false,只会获得响应的正文(true的话会连响应头一并获取到)
		curl_setopt($ch,CURLOPT_HEADER,0);
//		curl_setopt ( $ch, CURLOPT_TIMEOUT,5); // 设置超时限制防止死循环
		//设置发起连接前的等待时间，如果设置为0，则无限等待。
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
		//将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//2)设备请求体
		if (count($body)>0) {
			//$b=json_encode($body,true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);//全部数据使用HTTP协议中的"POST"操作来发送。
		}
		//设置请求头
		if(count($header)>0){
			curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
		}
		//上传文件相关设置
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算
		
		//3)设置提交方式
		switch($type){
			case "GET":
				curl_setopt($ch,CURLOPT_HTTPGET,true);
				break;
			case "POST":
				curl_setopt($ch,CURLOPT_POST,true);
				break;
			case "PUT"://使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。这对于执行"DELETE" 或者其他更隐蔽的HTT
				curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"PUT");
				break;
			case "DELETE":
				curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"DELETE");
				break;
		}
		
		
		//4)在HTTP请求中包含一个"User-Agent: "头的字符串。-----必设
	
//		curl_setopt($ch, CURLOPT_USERAGENT, 'SSTS Browser/1.0');
//		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
	
		curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)' ); // 模拟用户使用的浏览器
		//5)
		
		
		//3.抓取URL并把它传递给浏览器
		$res=curl_exec($ch);

		$result=json_decode($res,true);
		//4.关闭curl资源，并且释放系统资源
		curl_close($ch);
		if(empty($result))
			return $res;
		else
			return $result;
	
	}
}
?>
