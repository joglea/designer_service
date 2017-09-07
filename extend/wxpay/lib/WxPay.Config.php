<?php
/**
* 	配置账号信息
*/

class WxPayConfig
{
	//=======【基本信息设置】=====================================
	//
	/**
	 * TODO: 修改这里配置为您自己申请的商户信息
	 * 微信公众号信息配置
	 * 
	 * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
	 * 
	 * MCHID：商户号（必须配置，开户邮件中可查看）
	 * 
	 * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
	 * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
	 * 
	 * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
	 * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
	 * @var string
	 */
    /* prodution start */
	const APPID = 'wxbd77e14b472ac019';
	const MCHID = '1488744072';
	//const KEY = 'rnQs7luU7Hl2n5ritN69uktYHrzZBbIe';
	//const APPSECRET = 'f4ca6f0710496dc287e10a1ea5382345';
	//const SSLCERT_PATH = '../cert/apiclient_cert.pem';
	//const SSLKEY_PATH = '../cert/apiclient_key.pem';
	//const PCAPPID = 'wx45f12436fcdf65fa';
    /* prodution end */
    
    /* development start */
//     const APPID = 'wx986eb2c7f9a34f04';
//     const MCHID = '1416389302';
// 	const KEY = 'B18274f0f4db59b6b67b4987becaa7f2';
// 	const APPSECRET = 'b5e231f7890f4db953eed8a1c6832a41';
	// 	const SSLCERT_PATH = '../devcert/apiclient_cert.pem';
	// 	const SSLKEY_PATH = '../devcert/apiclient_key.pem';
	/* development end */
	
	//=======【curl代理设置】===================================
	/**
	 * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
	 * @var unknown_type
	 */
	const CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
	const CURL_PROXY_PORT = 0;//8080;
	
	const NOTIFY_URL = 'http://api.awu.cn/1/payment/wxpay_notify';
	
	//=======【上报信息配置】===================================
	/**
	 * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
	 * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
	 * 开启错误上报。
	 * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
	 * @var int
	 */
	const REPORT_LEVENL = 1;
	
	/* APP 正式环境微信支付配置 */
	public static $APPID = 'wxb8c9adcf75c48f5e';
	public static $MCHID = '1440744302'; //APP的微信支付商家ID，在支付平台中查看
	public static $KEY = 'rnQs7luU7Hl2n5ritN69uktYHrzZBbIe';
	public static $APPSECRET = 'f4ca6f0710496dc287e10a1ea5382345';
	public static $SSLCERT_PATH = '../cert/apiclient_cert.pem';
	public static $SSLKEY_PATH = '../cert/apiclient_key.pem';
	public static $CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
	public static $CURL_PROXY_PORT = 0;//8080;
	public static $NOTIFY_URL = 'http://api.awu.cn/1/payment/wxpay_notify';
	public static $REPORT_LEVENL = 1;

	public static $PCAPPID = 'wx45f12436fcdf65fa';
    /* prodution end */
	
	public static function init()
	{
	    if(in_array(ENVIRONMENT, array('development', 'testing'))){
			//微信测试公众号微信支付配置
	        self::$APPID = 'wx986eb2c7f9a34f04';
	        self::$MCHID = '1416389302';
	        self::$KEY = 'B18274f0f4db59b6b67b4987becaa7f2';
	        self::$APPSECRET = 'b5e231f7890f4db953eed8a1c6832a41';
	        self::$SSLCERT_PATH = '../devcert/apiclient_cert.pem';
	        self::$SSLKEY_PATH = '../devcert/apiclient_key.pem';

	    } else if (LY_API_PLATFORM == 'weixin') {
			//微信正式公众号微信支付配置
			self::$APPID = 'wxe1c3a9fe9626ef5b';
			self::$MCHID = '1295129501';  //公众号微信支付的商家ID，在支付平台中查看
			self::$KEY = 'zhejiangawuwangluo400057125awucn';
			self::$APPSECRET = '2f07e4a236c72d35a2b297e4e974180d';
			self::$SSLCERT_PATH = '../mpcert/apiclient_cert.pem';
			self::$SSLKEY_PATH = '../mpcert/apiclient_key.pem';

		}
	}
}
