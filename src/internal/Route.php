<?php
namespace swiftphp\web\internal;

use swiftphp\config\IConfigurable;
use swiftphp\config\IConfiguration;
use swiftphp\web\IRoute;
use swiftphp\common\util\StringUtil;

/**
 * 内置路由
 * 在执行配置注入时自动执行加载
 * @author Tomix
 *
 */
class Route implements IRoute,IConfigurable
{
    /**
     * 当前区域名称
     * @var string
     */
    private $m_areaName;

    /**
     * 区域前缀
     */
    private $m_areaPrefix;

    /**
     * 当前控制器名称
     * @var string
     */
    private $m_controllerName;

    /**
     * 当前操作名称
     * @var string
     */
    private $m_actionName;

    /**
     * 当前视图模板文件
     * @var string
     */
    private $m_viewFile;

    /**
     * 初始化参数
     * @var string
     */
    private $m_initParams=[];

    /**
     * 配置
     * @var IConfiguration
     */
    private $m_config=null;

    /**
     * 配置节
     * @var string
     */
    private $m_cfgSection="route";

    /**
     * 根区域配置键值
     * @var string
     */
    private $m_rootAreaConfigKey="ROOT";

    /**
     * 默认控制器名
     * @var string
     */
    private $m_defaultControllerName="Home";

    /**
     * 默认操作名
     * @var string
     */
    private $m_defaultActionName="index";

    /**
     * 路由配置节点名
     * @param string $value
     */
    public function setConfigSection($value)
    {
        $this->m_cfgSection=$value;
    }

    /**
     * 根区域配置键值
     * @param string $value
     */
    public function setRootAreaConfigKey($value)
    {
        $this->m_rootAreaConfigKey=$value;
    }

    /**
     * 默认控制器名
     * @param string $value
     */
    public function setDefaultControllerName($value)
    {
        $this->m_defaultActionName=$value;
    }

    /**
     * 默认操作名
     * @param string $value
     */
    public function setDefaultActionName($value)
    {
        $this->m_defaultActionName=$value;
    }

    /**
     * 注入配置
     * {@inheritDoc}
     * @see \swiftphp\config\IConfigurable::setConfiguration()
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
        $this->load();
    }

    /**
     * 获取配置实例
     * @return \swiftphp\config\IConfiguration
     */
    public function getConfiguration()
    {
        return $this->m_config;
    }

    //public function

    /**
     * 区域名称
     */
    public function getAreaName()
    {
        return $this->m_areaName;
    }

    /**
     * 区域前缀
     */
    public function getAreaPrefix()
    {
        return $this->m_areaPrefix;
    }

    /**
     * 控制器名称
     */
    public function getControllerName()
    {
        return $this->m_controllerName;
    }

    /**
     * 操作名称
     */
    public function getActionName()
    {
        return $this->m_actionName;
    }

    /**
     * 视图文件
     */
    public function getViewFile()
    {
        return $this->m_viewFile;
    }

    /**
     * 初始化参数
     */
    public function getInitParams()
    {
        return $this->m_initParams;
    }

    /**
     * 加载
     */
    private function load()
    {
        $config=$this->getConfiguration()->getConfigValues($this->m_cfgSection);
        $uri=$_SERVER["REQUEST_URI"];
        if(strpos($uri, "?")>0){
            $uri=substr($uri, 0,strpos($uri, "?"));
        }
        foreach($config as $areaName=>$area){
            $namespace=trim($area["namespace"],"\\");
            $prefix=rtrim($area["prefix"],"/");
            $rules=$area["rules"];
            foreach ($rules as $rule){
                $url=ltrim($rule["url"],"/");
                if(empty($url)){
                    $url=$prefix;
                }else{
                    $url=$prefix."/".$url;
                }
                $url_mode="/".str_replace("/", "\/", $url)."/";
                $matches=[];
                if(preg_match($url_mode,$uri,$matches)){
                    //controller
                    $_controller=$this->m_defaultControllerName;
                    if(array_key_exists("controller", $rule)){
                        $_controller=trim($rule["controller"],"\\");
                    }
                    $controller=$namespace."\\".$_controller;

                    //action
                    $action=array_key_exists("action", $rule) ? $rule["action"] : $this->m_defaultActionName;

                    //view
                    $view=array_key_exists("view", $rule) ? $rule["view"] : "";

                    //params
                    $_params=array_key_exists("params",$rule)?$rule["params"]:[];

                    //replace matches
                    for($index = 1;$index < count($matches);$index++){
                        $match=$matches[$index];
                        if(empty($match)){
                            continue;
                        }
                        //转为驼峰命名法,首字母大小写不变
                        $first=substr($match, 0,1);
                        $_match=StringUtil::toHumpString($match);
                        $_match=$first.substr($_match, 1);
                        $srch="$".(string)$index;
                        $controller=str_replace($srch, ucfirst($_match), $controller);//控制器首字母大写
                        $action=str_replace($srch, $_match, $action);
                        $view=str_replace($srch, $_match, $view);
                        foreach ($_params as $key=>$value){
                            $_params[$key]=str_replace($srch, $match, $value);
                        }
                    }

                    //属性
                    $this->m_areaName=$areaName;
                    $this->m_areaPrefix=rtrim($prefix,"/");
                    $this->m_controllerName=$controller;
                    $this->m_actionName=$action;
                    $this->m_viewFile=$view;
                    $this->m_initParams=$_params;
                    if($this->m_areaName==$this->m_rootAreaConfigKey){
                        $this->m_areaName="/";
                    }
                    if($this->m_areaPrefix==""){
                        $this->m_areaPrefix="/";
                    }
                    return;
                }
            }
        }
    }
}

