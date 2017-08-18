<?php
// +----------------------------------------------------------------------
// | SentCMS [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.tensent.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: molong <molong@tensent.cn> <http://www.tensent.cn>
// +----------------------------------------------------------------------

namespace app\common\controller;


class Base extends \think\Controller {

	protected $url;
	protected $request;
	protected $param;
	protected $module;
	protected $controller;
	protected $action;

	public function _initialize() {
		/* 读取数据库中的配置 */
		$config = cache('db_config_data');
		if (!$config) {
			//$config = model('Config')->lists();
			//cache('db_config_data', $config);
		}
		config($config);
		//获取request信息
		$this->requestInfo();
	}

	public function execute($mc = null, $op = '', $ac = null) {
		$op = $op ? $op : $this->request->module();
		if (\think\Config::get('url_case_insensitive')) {
			$mc = ucfirst(parse_name($mc, 1));
			$op = parse_name($op, 1);
		}

		if (!empty($mc) && !empty($op) && !empty($ac)) {
			$ops    = ucwords($op);
			$class  = "\\addons\\{$mc}\\controller\\{$ops}";
			$addons = new $class;
			$addons->$aconfig();
		} else {
			$this->error('没有指定插件名称，控制器或操作！');
		}
	}


	//获取接口头内容
	public function getAllHeaders()
	{
		foreach ($_SERVER as $name => $value)
		{
			if (substr($name, 0, 5) == 'HTTP_')
			{
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}

	protected function setSeo($title = '', $keywords = '', $description = '') {
		$seo = array(
			'title'       => $title,
			'keywords'    => $keywords,
			'description' => $description,
		);
		//获取还没有经过变量替换的META信息
		$meta = model('SeoRule')->getMetaOfCurrentPage($seo);
		foreach ($seo as $key => $item) {
			if (is_array($item)) {
				$item = implode(',', $item);
			}
			$meta[$key] = str_replace("[" . $key . "]", $item . '|', $meta[$key]);
		}

		$data = array(
			'title'       => $meta['title'],
			'keywords'    => $meta['keywords'],
			'description' => $meta['description'],
		);
		$this->assign($data);
	}

	/**
	 * 验证码
	 * @param  integer $id 验证码ID
	 * @author 郭平平 <molong@tensent.cn>
	 */
	public function verify($id = 1) {
		$verify = new \org\Verify(array('length' => 4));
		$verify->entry($id);
	}

	/**
	 * 检测验证码
	 * @param  integer $id 验证码ID
	 * @return boolean     检测结果
	 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
	 */
	public function checkVerify($code, $id = 1) {
		if ($code) {
			$verify = new \org\Verify();
			$result = $verify->check($code, $id);
			if (!$result) {
				return $this->error("验证码错误！", "");
			}
		} else {
			return $this->error("验证码为空！", "");
		}
	}

	//request信息
	protected function requestInfo() {
		$this->param = $this->request->param();
		defined('MODULE_NAME') or define('MODULE_NAME', $this->request->module());
		defined('CONTROLLER_NAME') or define('CONTROLLER_NAME', $this->request->controller());
		defined('ACTION_NAME') or define('ACTION_NAME', $this->request->action());
		defined('IS_POST') or define('IS_POST', $this->request->isPost());
		defined('IS_AJAX') or define('IS_AJAX', $this->request->isAjax());
		defined('IS_GET') or define('IS_GET', $this->request->isGet());
		$this->url = strtolower($this->request->module() . '/' . $this->request->controller() . '/' . $this->request->action());
		$this->assign('request', $this->request);
		$this->assign('param', $this->param);
		$this->assign('serverhost', config('server_host'));

	}

	/**
	 * 获取单个参数的数组形式
	 */
	protected function getArrayParam($param) {
		if (isset($this->param['id'])) {
			return array_unique((array) $this->param[$param]);
		} else {
			return array();
		}
	}
	/**
	 * 根据指定格式校验日期
	 * @param $date
	 * @param string $format
	 * @return bool
	 */
	public function validateDate($date, $format = 'Y-m-d H:i:s')
	{
		$d = \DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}


	/**
	 * 密码md5
	 * @param string $passwd
	 * @return string
	 */
	public function passwdmd5($passwd)
	{
		//var_dump(config('PASSWD_PREFIX'),config('PASSWD_SUFFIX'),config('PASSWD_START'),config('PASSWD_LENGTH'));
		return substr(md5(config('PASSWD_PREFIX').$passwd.config('PASSWD_SUFFIX')), config('PASSWD_START'),config('PASSWD_LENGTH'));
	}

}
