<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

//------------------------
// ThinkPHP 助手函数
//-------------------------

use think\Cache;
use think\Config;
use think\Cookie;
use think\Db;
use think\Debug;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\Lang;
use think\Loader;
use think\Log;
use think\Model;
use think\Request;
use think\Response;
use think\Session;
use think\Url;
use think\View;

define('EARTH_RADIUS', 6378137);//地球半径  m
define('PI', 3.1415926);

if (!function_exists('load_trait')) {
    /**
     * 快速导入Traits PHP5.5以上无需调用
     * @param string    $class trait库
     * @param string    $ext 类库后缀
     * @return boolean
     */
    function load_trait($class, $ext = EXT)
    {
        return Loader::import($class, TRAIT_PATH, $ext);
    }
}

if (!function_exists('exception')) {
    /**
     * 抛出异常处理
     *
     * @param string    $msg  异常消息
     * @param integer   $code 异常代码 默认为0
     * @param string    $exception 异常类
     *
     * @throws Exception
     */
    function exception($msg, $code = 0, $exception = '')
    {
        $e = $exception ?: '\think\Exception';
        throw new $e($msg, $code);
    }
}

if (!function_exists('debug')) {
    /**
     * 记录时间（微秒）和内存使用情况
     * @param string            $start 开始标签
     * @param string            $end 结束标签
     * @param integer|string    $dec 小数位 如果是m 表示统计内存占用
     * @return mixed
     */
    function debug($start, $end = '', $dec = 6)
    {
        if ('' == $end) {
            Debug::remark($start);
        } else {
            return 'm' == $dec ? Debug::getRangeMem($start, $end) : Debug::getRangeTime($start, $end, $dec);
        }
    }
}

if (!function_exists('lang')) {
    /**
     * 获取语言变量值
     * @param string    $name 语言变量名
     * @param array     $vars 动态变量值
     * @param string    $lang 语言
     * @return mixed
     */
    function lang($name, $vars = [], $lang = '')
    {
        return Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('config')) {
    /**
     * 获取和设置配置参数
     * @param string|array  $name 参数名
     * @param mixed         $value 参数值
     * @param string        $range 作用域
     * @return mixed
     */
    function config($name = '', $value = null, $range = '')
    {
        if (is_null($value) && is_string($name)) {
            return 0 === strpos($name, '?') ? Config::has(substr($name, 1), $range) : Config::get($name, $range);
        } else {
            return Config::set($name, $value, $range);
        }
    }
}

if (!function_exists('input')) {
    /**
     * 获取输入数据 支持默认值和过滤
     * @param string    $key 获取的变量名
     * @param mixed     $default 默认值
     * @param string    $filter 过滤方法
     * @return mixed
     */
    function input($key = '', $default = null, $filter = '')
    {
        if (0 === strpos($key, '?')) {
            $key = substr($key, 1);
            $has = true;
        }
        if ($pos = strpos($key, '.')) {
            // 指定参数来源
            list($method, $key) = explode('.', $key, 2);
            if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'param', 'request', 'session', 'cookie', 'server', 'env', 'path', 'file'])) {
                $key    = $method . '.' . $key;
                $method = 'param';
            }
        } else {
            // 默认为自动判断
            $method = 'param';
        }
        if (isset($has)) {
            return request()->has($key, $method, $default);
        } else {
            return request()->$method($key, $default, $filter);
        }
    }
}

if (!function_exists('widget')) {
    /**
     * 渲染输出Widget
     * @param string    $name Widget名称
     * @param array     $data 传入的参数
     * @return mixed
     */
    function widget($name, $data = [])
    {
        return Loader::action($name, $data, 'widget');
    }
}

if (!function_exists('model')) {
    /**
     * 实例化Model
     * @param string    $name Model名称
     * @param string    $layer 业务层名称
     * @param bool      $appendSuffix 是否添加类名后缀
     * @return \think\Model
     */
    function model($name = '', $layer = 'model', $appendSuffix = false)
    {
        return Loader::model($name, $layer, $appendSuffix);
    }
}

if (!function_exists('validate')) {
    /**
     * 实例化验证器
     * @param string    $name 验证器名称
     * @param string    $layer 业务层名称
     * @param bool      $appendSuffix 是否添加类名后缀
     * @return \think\Validate
     */
    function validate($name = '', $layer = 'validate', $appendSuffix = false)
    {
        return Loader::validate($name, $layer, $appendSuffix);
    }
}

if (!function_exists('db')) {
    /**
     * 实例化数据库类
     * @param string        $name 操作的数据表名称（不含前缀）
     * @param array|string  $config 数据库配置参数
     * @param bool          $force 是否强制重新连接
     * @return \think\db\Query
     */
    function db($name = '', $config = [], $force = true)
    {
        return Db::connect($config, $force)->name($name);
    }
}

if (!function_exists('controller')) {
    /**
     * 实例化控制器 格式：[模块/]控制器
     * @param string    $name 资源地址
     * @param string    $layer 控制层名称
     * @param bool      $appendSuffix 是否添加类名后缀
     * @return \think\Controller
     */
    function controller($name, $layer = 'controller', $appendSuffix = false)
    {
        return Loader::controller($name, $layer, $appendSuffix);
    }
}

if (!function_exists('action')) {
    /**
     * 调用模块的操作方法 参数格式 [模块/控制器/]操作
     * @param string        $url 调用地址
     * @param string|array  $vars 调用参数 支持字符串和数组
     * @param string        $layer 要调用的控制层名称
     * @param bool          $appendSuffix 是否添加类名后缀
     * @return mixed
     */
    function action($url, $vars = [], $layer = 'controller', $appendSuffix = false)
    {
        return Loader::action($url, $vars, $layer, $appendSuffix);
    }
}

if (!function_exists('import')) {
    /**
     * 导入所需的类库 同java的Import 本函数有缓存功能
     * @param string    $class 类库命名空间字符串
     * @param string    $baseUrl 起始路径
     * @param string    $ext 导入的文件扩展名
     * @return boolean
     */
    function import($class, $baseUrl = '', $ext = EXT)
    {
        return Loader::import($class, $baseUrl, $ext);
    }
}

if (!function_exists('vendor')) {
    /**
     * 快速导入第三方框架类库 所有第三方框架的类库文件统一放到 系统的Vendor目录下面
     * @param string    $class 类库
     * @param string    $ext 类库后缀
     * @return boolean
     */
    function vendor($class, $ext = EXT)
    {
        return Loader::import($class, VENDOR_PATH, $ext);
    }
}

if (!function_exists('dump')) {
    /**
     * 浏览器友好的变量输出
     * @param mixed     $var 变量
     * @param boolean   $echo 是否输出 默认为true 如果为false 则返回输出字符串
     * @param string    $label 标签 默认为空
     * @return void|string
     */
    function dump($var, $echo = true, $label = null)
    {
        return Debug::dump($var, $echo, $label);
    }
}

if (!function_exists('url')) {
    /**
     * Url生成
     * @param string        $url 路由地址
     * @param string|array  $vars 变量
     * @param bool|string   $suffix 生成的URL后缀
     * @param bool|string   $domain 域名
     * @return string
     */
    function url($url = '', $vars = '', $suffix = true, $domain = false)
    {
        return Url::build($url, $vars, $suffix, $domain);
    }
}

if (!function_exists('session')) {
    /**
     * Session管理
     * @param string|array  $name session名称，如果为数组表示进行session设置
     * @param mixed         $value session值
     * @param string        $prefix 前缀
     * @return mixed
     */
    function session($name, $value = '', $prefix = null)
    {
        if (is_array($name)) {
            // 初始化
            Session::init($name);
        } elseif (is_null($name)) {
            // 清除
            Session::clear('' === $value ? null : $value);
        } elseif ('' === $value) {
            // 判断或获取
            return 0 === strpos($name, '?') ? Session::has(substr($name, 1), $prefix) : Session::get($name, $prefix);
        } elseif (is_null($value)) {
            // 删除
            return Session::delete($name, $prefix);
        } else {
            // 设置
            return Session::set($name, $value, $prefix);
        }
    }
}

if (!function_exists('cookie')) {
    /**
     * Cookie管理
     * @param string|array  $name cookie名称，如果为数组表示进行cookie设置
     * @param mixed         $value cookie值
     * @param mixed         $option 参数
     * @return mixed
     */
    function cookie($name, $value = '', $option = null)
    {
        if (is_array($name)) {
            // 初始化
            Cookie::init($name);
        } elseif (is_null($name)) {
            // 清除
            Cookie::clear($value);
        } elseif ('' === $value) {
            // 获取
            return 0 === strpos($name, '?') ? Cookie::has(substr($name, 1), $option) : Cookie::get($name, $option);
        } elseif (is_null($value)) {
            // 删除
            return Cookie::delete($name);
        } else {
            // 设置
            return Cookie::set($name, $value, $option);
        }
    }
}

if (!function_exists('cache')) {
    /**
     * 缓存管理
     * @param mixed     $name 缓存名称，如果为数组表示进行缓存设置
     * @param mixed     $value 缓存值
     * @param mixed     $options 缓存参数
     * @param string    $tag 缓存标签
     * @return mixed
     */
    function cache($name, $value = '', $options = null, $tag = null)
    {
        if (is_array($options)) {
            // 缓存操作的同时初始化
            Cache::connect($options);
        } elseif (is_array($name)) {
            // 缓存初始化
            return Cache::connect($name);
        }
        if (is_null($name)) {
            return Cache::clear($value);
        } elseif ('' === $value) {
            // 获取缓存
            return 0 === strpos($name, '?') ? Cache::has(substr($name, 1)) : Cache::get($name);
        } elseif (is_null($value)) {
            // 删除缓存
            return Cache::rm($name);
        } elseif (0 === strpos($name, '?') && '' !== $value) {
            $expire = is_numeric($options) ? $options : null;
            return Cache::remember(substr($name, 1), $value, $expire);
        } else {
            // 缓存数据
            if (is_array($options)) {
                $expire = isset($options['expire']) ? $options['expire'] : null; //修复查询缓存无法设置过期时间
            } else {
                $expire = is_numeric($options) ? $options : null; //默认快捷缓存设置过期时间
            }
            if (is_null($tag)) {
                return Cache::set($name, $value, $expire);
            } else {
                return Cache::tag($tag)->set($name, $value, $expire);
            }
        }
    }
}

if (!function_exists('trace')) {
    /**
     * 记录日志信息
     * @param mixed     $log log信息 支持字符串和数组
     * @param string    $level 日志级别
     * @return void|array
     */
    function trace($log = '[think]', $level = 'log')
    {
        if ('[think]' === $log) {
            return Log::getLog();
        } else {
            Log::record($log, $level);
        }
    }
}

if (!function_exists('request')) {
    /**
     * 获取当前Request对象实例
     * @return Request
     */
    function request()
    {
        return Request::instance();
    }
}

if (!function_exists('response')) {
    /**
     * 创建普通 Response 对象实例
     * @param mixed      $data   输出数据
     * @param int|string $code   状态码
     * @param array      $header 头信息
     * @param string     $type
     * @return Response
     */
    function response($data = [], $code = 200, $header = [], $type = 'html')
    {
        return Response::create($data, $type, $code, $header);
    }
}

if (!function_exists('view')) {
    /**
     * 渲染模板输出
     * @param string    $template 模板文件
     * @param array     $vars 模板变量
     * @param array     $replace 模板替换
     * @param integer   $code 状态码
     * @return \think\response\View
     */
    function view($template = '', $vars = [], $replace = [], $code = 200)
    {
        return Response::create($template, 'view', $code)->replace($replace)->assign($vars);
    }
}

if (!function_exists('json')) {
    /**
     * 获取\think\response\Json对象实例
     * @param mixed   $data 返回的数据
     * @param integer $code 状态码
     * @param array   $header 头部
     * @param array   $options 参数
     * @return \think\response\Json
     */
    function json($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'json', $code, $header, $options);
    }
}

if (!function_exists('jsonp')) {
    /**
     * 获取\think\response\Jsonp对象实例
     * @param mixed   $data    返回的数据
     * @param integer $code    状态码
     * @param array   $header 头部
     * @param array   $options 参数
     * @return \think\response\Jsonp
     */
    function jsonp($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'jsonp', $code, $header, $options);
    }
}

if (!function_exists('xml')) {
    /**
     * 获取\think\response\Xml对象实例
     * @param mixed   $data    返回的数据
     * @param integer $code    状态码
     * @param array   $header  头部
     * @param array   $options 参数
     * @return \think\response\Xml
     */
    function xml($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'xml', $code, $header, $options);
    }
}

if (!function_exists('redirect')) {
    /**
     * 获取\think\response\Redirect对象实例
     * @param mixed         $url 重定向地址 支持Url::build方法的地址
     * @param array|integer $params 额外参数
     * @param integer       $code 状态码
     * @param array         $with 隐式传参
     * @return \think\response\Redirect
     */
    function redirect($url = [], $params = [], $code = 302, $with = [])
    {
        if (is_integer($params)) {
            $code   = $params;
            $params = [];
        }
        return Response::create($url, 'redirect', $code)->params($params)->with($with);
    }
}

if (!function_exists('abort')) {
    /**
     * 抛出HTTP异常
     * @param integer|Response      $code 状态码 或者 Response对象实例
     * @param string                $message 错误信息
     * @param array                 $header 参数
     */
    function abort($code, $message = null, $header = [])
    {
        if ($code instanceof Response) {
            throw new HttpResponseException($code);
        } else {
            throw new HttpException($code, $message, null, $header);
        }
    }
}

if (!function_exists('halt')) {
    /**
     * 调试变量并且中断输出
     * @param mixed      $var 调试变量或者信息
     */
    function halt($var)
    {
        dump($var);
        throw new HttpResponseException(new Response);
    }
}

if (!function_exists('token')) {
    /**
     * 生成表单令牌
     * @param string $name 令牌名称
     * @param mixed  $type 令牌生成方法
     * @return string
     */
    function token($name = '__token__', $type = 'md5')
    {
        $token = Request::instance()->token($name, $type);
        return '<input type="hidden" name="' . $name . '" value="' . $token . '" />';
    }
}

if (!function_exists('load_relation')) {
    /**
     * 延迟预载入关联查询
     * @param mixed $resultSet 数据集
     * @param mixed $relation 关联
     * @return array
     */
    function load_relation($resultSet, $relation)
    {
        $item = current($resultSet);
        if ($item instanceof Model) {
            $item->eagerlyResultSet($resultSet, $relation);
        }
        return $resultSet;
    }
}

if (!function_exists('collection')) {
    /**
     * 数组转换为数据集对象
     * @param array $resultSet 数据集数组
     * @return \think\model\Collection|\think\Collection
     */
    function collection($resultSet)
    {
        $item = current($resultSet);
        if ($item instanceof Model) {
            return \think\model\Collection::make($resultSet);
        } else {
            return \think\Collection::make($resultSet);
        }
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @return mixed
     */
    function get_client_ip($type = 0) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
}


if (!function_exists('rand_string')) {
    /**
    +----------------------------------------------------------
     * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
    +----------------------------------------------------------
     * @param string $len 长度
     * @param string $type 字串类型
     * 0 字母 1 数字 其它 混合
     * @param string $addChars 额外字符
    +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    function rand_string($len = 6, $type = '', $addChars = '') {
        $str = '';
        switch ($type) {
            case 0:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 1:
                $chars = str_repeat('0123456789', 3);
                break;
            case 2:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 3:
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 4:
                $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借" . $addChars;
                break;
            default:
                // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }
        if ($len > 10) {
            //位数过长重复字符串一定次数
            $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
        }
        if ($type != 4) {
            $chars = str_shuffle($chars);
            $str   = substr($chars, 0, $len);
        } else {
            // 中文随机字
            for ($i = 0; $i < $len; $i++) {
                $str .= msubstr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1);
            }
        }
        return $str;
    }
}


if (!function_exists('safestr')) {

    function safestr($str,$sid=''){
        $str = trim($str);
        $str = str_replace('　','',$str);
        $str = str_replace(' ','',$str);
        $str = str_replace(',','',$str);
        $str = str_replace('，','',$str);
        //$str = str_replace(' ','',$str);
        //$str = preg_replace('/\s/','',$str);
        $str = str_replace(chr(0x20),'',$str);

        $str=urlencode($str);
        $str=preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|\-|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99|%EF%BD%9E|%EF%BC%8E|%EF%BC%88)+/",'',$str);
        $str=urldecode($str);

        if(!$str||strlen($str)>24)
        {
            return '';
        }

        return $str;

    }
}


if (!function_exists('generate_code')) {
    /**
     * 生成验证码
     */
    /**
     * @param int $len 生成验证码长度
     * @param int $type 生成验证码类型 1纯数字 2字母结合数字
     *
     * @return string
     */
    function generate_code($len = 6,$type = 1){
        if($type == 1){
            $s = [0,1,2,3,5,6,7,8,9];
        }
        else{
            $s = [0,2,3,4,5,6,7,8,'a','b','c','d','e','f','g','h','j','k',
                'l','m','n','p','q','r','s','t','u','v','w','x','y','z'];
        }
        $code = '';
        for($i=0;$i<$len;$i++){
            $code.=$s[rand(0,8)];
        }
        return $code;
    }
}


if (!function_exists('check_string_length')) {
    /**
     * @param int $string 字符串
     * @param int $minLen 字符串一个中文算2个长度时最小限制长度
     * @param int $maxLen 字符串一个中文算2个长度时最大限制长度
     *
     * @return string
     */
    function check_string_length($string='',$minLen = 6,$maxLen = 48){
        //中文长度算2
        $stringLen = 0;
        //正则匹配中文区间
        $pregC = '/[\x{4e00}-\x{9fa5}]/u';
        preg_match_all($pregC,$string,$m);
        $chineseCount = count($m[0]);
        $stringLen +=$chineseCount*2;
        $newString = preg_replace($pregC,'',$string);
        $stringLen += strlen($newString);
        if($stringLen>$maxLen||$stringLen<$minLen){
            return false;
        }
        else{
            return true;
        }
    }
}

if (!function_exists('sub_string_length')) {
    /**
     * @param int $string 字符串
     * @param int $maxLen 字符串一个中文算2个长度时最大限制长度
     * @param int $suffix 字符串超过最大限制长度增加的后缀字符串
     *
     * @return string
     */
    function sub_string_length($string='',$maxLen = 48,$suffix=''){
        //中文长度算2
        //正则匹配中文区间
        $pregC = '/[\x{4e00}-\x{9fa5}]{1}/u';
        $totalCharCount = mb_strlen($string,'utf-8');
        $curTotalLen = 0;
        $newString = '';
        for($i=0;$i<$totalCharCount;$i++){
            $curChar = mb_substr($string,$i,$i+1,'utf-8');
            preg_match($pregC,$curChar,$match);

            if($match){
                $curTotalLen+=2;
            }
            else{
                $curTotalLen+=1;
            }
            if($curTotalLen>$maxLen){
                $newString = $newString.$suffix;
                break;
            }
            else{
                $newString = $newString.$curChar;
            }
        }
        return $newString;
    }
}


/**
 * @param     $lat1     纬度1
 * @param     $lng1     经度1
 * @param     $lat2     纬度2
 * @param     $lng2     经度2
 * @param int $len_type 返回数据单位 1:m 2:km
 * @param int $decimal  小数位数
 *
 * @return float
 */
function getDistance($lat1, $lng1, $lat2, $lng2, $len_type = 1, $decimal = 2)
{
    $radLat1 = $lat1 * PI / 180.0;
    $radLat2 = $lat2 * PI / 180.0;
    $a = $radLat1 - $radLat2;
    $b = ($lng1 * PI / 180.0) - ($lng2 * PI / 180.0);
    $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));

    $s = $s * EARTH_RADIUS;

    $s = round($s ,$decimal);

    if ($len_type > 1)
    {
        $s /= 1000;
    }
    return round(abs($s), $decimal);
}


/**
 * @param $lat 纬度
 * @param $lng 经度
 * @param $dis 指定距离
 *             return array($minlat,$maxlat,$minlng,$maxlng)   返回指定距离的区域范围
 *             array(最小纬度，最大纬度，最小经度，最大经度)
 */
function getRectByPoint($lat,$lng,$dis){

    $minlat=$lat-180*$dis/PI/EARTH_RADIUS;
    $maxlat=$lat+180*$dis/PI/EARTH_RADIUS;

    $minlng=$lng-180*$dis/PI/EARTH_RADIUS;
    $maxlng=$lng+180*$dis/PI/EARTH_RADIUS;

    return array($minlat,$maxlat,$minlng,$maxlng);

}
