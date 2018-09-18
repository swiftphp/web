<?php
namespace swiftphp\web\internal;


use swiftphp\config\IConfigurable;
use swiftphp\config\IConfiguration;
use swiftphp\http\Context;
use swiftphp\web\IView;
use swiftphp\io\File;
use swiftphp\http\Request;
use swiftphp\http\IOutput;
use swiftphp\web\IController;
use swiftphp\web\ParamMode;
use swiftphp\web\internal\out\Redirect;
use swiftphp\web\internal\out\Json;
use swiftphp\web\internal\out\Jsonp;
use swiftphp\web\internal\out\HtmlView;

/**
 * 控制器内置基本类型
 * @author Administrator
 *
 */
class Controller implements IController,IConfigurable
{
    /**
     * 配置实例
     * @var IConfiguration
     */
    private $m_config;

    /**
     * 上下文实例
     * @var Context
     */
    private $m_context;

    /**
     * 区域名称
     * @var string
     */
    private $m_areaName;

    /**
     * 区域前缀
     * @var string
     */
    private $m_areaPrefix;

    /**
     * 默认视图文件
     * @var string
     */
    private $m_viewFile;

    /**
     * 初始化参数
     * @var array
     */
    private $m_initParams=[];

    /**
     * 视图引擎
     * @var IView
     */
    private $m_viewEngine=null;

    /**
     * 当前Action
     * @var string
     */
    private $m_action;

    /**
     * 模板参数集
     * @var array
     */
    private $m_viewParams=[];

    /**
     * 标签参数集
     * @var array
     */
    private $m_tagParams=[];

    /**
     * 是否调试模式
     * @var string
     */
    private $m_debug=false;

    /**
     * 运行时目录
     * @var string
     */
    private $m_runtimeDir;

    /**
     * 输出模型
     * @var IOutput
     */
    protected $m_outputModel=null;

    /**
     * 是否为调试模式
     * @param bool $value
     */
    public function setDebug($value)
    {
        $this->m_debug=$value;
    }

    /**
     * 是否为调试模式
     * @return string
     */
    public function getDebug()
    {
        return $this->m_debug;
    }


    /**
     * 注入配置实例
     * @param IConfiguration $value
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
    }

    /**
     * 获取配置实例
     * @return \swiftphp\config\IConfiguration
     */
    public function getConfiguration()
    {
        return $this->m_config;
    }

    /**
     *  上下文
     * @param Context $value
     */
    public function setContext(Context $value)
    {
        $this->m_context=$value;
    }

    /**
     * 区域名称
     * @param string $value
     */
    public function setAreaName($value)
    {
        $this->m_areaName=$value;
    }

    /**
     * 区域前缀
     * @param string $value
     */
    public function setAreaPrefix($value)
    {
        $this->m_areaPrefix=$value;
    }

    /**
     * 视图文件
     * @param string $value
     */
    public function setViewFile($value)
    {
        $this->m_viewFile=$value;
    }

    /**
     * 初始化参数
     * @param array $value
     */
    public function setInitParams($value)
    {
        $this->m_initParams=$value;
    }

    /**
     * 设置视图引擎
     * @param IView $value
     */
    public function setViewEngine(IView $value)
    {
        $this->m_viewEngine=$value;
    }

    /**
     * 获取区域名
     */
    public function getAreaName()
    {
        //return $this->m_areaName;
        return $this->m_areaName=="/"?"":$this->m_areaName;
    }

    /**
     * 获取区域前缀
     * @return string
     */
    public function getAreaPrefix()
    {
        //return $this->m_areaPrefix;
        return $this->m_areaPrefix=="/"?"":$this->m_areaPrefix;
    }

    /**
     * 获取当前激活的操作名
     */
    public function getActionName()
    {
        return $this->m_action;
    }

    /**
     * 设置运行时目录
     * @param string $value
     */
    public function setRuntimeDir($value)
    {
        $this->m_runtimeDir=$value;
    }

    /**
     * 构造器
     */
    public function __construct()
    {

    }

    /**
     * 激活控制器方法
     * @param string $action
     */
    function invoke($action)
    {
        //请求参数映射到属性
        foreach ($this->m_initParams as $name=>$value){
            if(property_exists($this, $name)){
                $this->$name=$value;
            }
            $this->addParameter($name, $value);
        }
        foreach ($_POST as $name=>$value){
            if(property_exists($this, $name)){
                $this->$name=$value;
            }
            $this->addParameter($name, $value);
        }
        foreach ($_GET as $name=>$value){
            if(property_exists($this, $name)){
                $this->$name=$value;
            }
            $this->addParameter($name, $value);
        }

        //激活控制器方法
        $methodName=$action."Action";
        if(!method_exists($this,$methodName)){
            throw new \Exception("Fail to load method '".get_class($this)."::".$methodName."()'");
        }
        $this->m_action=$action;
        $model = $this->$methodName();
        if($model==null){
            $model=$this->m_outputModel;
        }
        return $model;
    }

    /**
     * 添加输出参数
     * @param $name 参数名
     * @param $value 参数值
     * @return void
     */
    public function addParameter($name,$value,$paramMode=0)
    {
        if($paramMode==ParamMode::$view){
            $this->m_viewParams[$name]=$value;
        }else if($paramMode==ParamMode::$tag){
            $this->m_tagParams[$name]=$value;
        }else{
            $this->m_viewParams[$name]=$value;
            $this->m_tagParams[$name]=$value;
        }
    }

    /**
     * 当前请求是否为POST
     * @return boolean
     */
    public function isPost()
    {
        return strtoupper($this->getRequest()->method)=="POST";
    }

    /**
     * 添加输出头部
     * @param unknown $name
     * @param string $value
     */
    public function addHeader($name,$value="")
    {
        $this->getResponse()->addHeader($name,$value);
    }

    /**
     * 设置响应码
     * @param number $value
     */
    public function setResponseCode($value=200)
    {
        $this->getResponse()->setCode($value);
    }

    /**
     * 获取经过视图引擎渲染后的模板内容
     * @param string $viewFile
     * @return string
     */
    public function getView($viewFile="")
    {
        $viewEngine=$this->getViewEngine(!empty($viewFile) ? $viewFile : $this->m_viewFile);
        return $viewEngine->getContent();
    }

    /**
     * 调用视图引擎渲染模板
     * @param string $viewFile
     * @return IOutput
     */
    public function view($viewFile="")
    {
        $this->m_outputModel=$this->getViewEngine(!empty($viewFile) ? $viewFile : $this->m_viewFile);
        return $this->m_outputModel;
    }

    /**
     * 重定向
     * @param string $url
     * @return IOutput
     */
    public function redirect($url)
    {
        $this->m_outputModel =new Redirect($url);
        return $this->m_outputModel;
    }

    /**
     * 301重定向
     * @return IOutput
     */
    public function redirect301($url)
    {
        $this->getResponse()->addHeader("HTTP/1.1 301 Moved Permanently");
        $this->m_outputModel =new Redirect($url);
        return $this->m_outputModel;
    }

    /**
     * 响应json
     * @param mixed $data
     * @return IOutput
     */
    public function responseJson($data="")
    {
        $this->m_outputModel =new Json($data);
        return $this->m_outputModel;
    }

    /**
     * 响应jsonp
     * @param string $data
     * @param string $callbackParamName
     * @return IOutput
     */
    public function responseJsonp($data="",$callbackParamName="callback")
    {
        $callback="callback";
        if(!empty($callbackParamName)){
            $callback=$this->getRequestParameter($callbackParamName,$callback);
        }
        $this->m_outputModel =new Jsonp($data,$callback);
        return $this->m_outputModel;
    }

    /**
     * 取得请求参数(搜索顺序:get,post,initParam)
     * @param string $name
     * @param string $default
     * @return Ambigous <unknown, string>
     */
    public function getRequestParameter($name,$default="")
    {
        $value=$this->getRequest()->getRequestParameter($name);
        if($value=="" && array_key_exists($name, $this->m_initParams)){
            $value=$this->m_initParams[$name];
        }
        if($value==""){
            $value=$default;
        }
        return $value;
    }

    /**
     * 获取响应对象
     * @return Response
     */
    protected function getResponse()
    {
        return $this->m_context->getResponse();
    }

    /**
     * 获取请求对象
     * @return Request
     */
    protected function getRequest()
    {
        return $this->m_context->getRequest();
    }

    /**
     * 获取view引擎实例
     */
    protected function getViewEngine($viewFile="")
    {
        if(empty($this->m_viewEngine)){
            $this->m_viewEngine=new HtmlView();
            $this->m_viewEngine->setConfiguration($this->m_config);
        }
        $this->m_viewEngine->setContext($this->m_context);
        $this->m_viewEngine->setTagParams($this->m_tagParams);
        $this->m_viewEngine->setViewParams($this->m_viewParams);
        $this->m_viewEngine->setController($this);
        $this->m_viewEngine->setDebug($this->m_debug);
        $this->m_viewEngine->setRuntimeDir($this->getRuntimeDir());
        $this->m_viewEngine->setViewFile($viewFile);
        return $this->m_viewEngine;
    }


    /**
     * 运行时目录
     * @return string
     */
    private function getRuntimeDir()
    {
        $runtimeDir=$this->m_runtimeDir;
        if(empty($runtimeDir)){
            $runtimeDir=rtrim($this->m_config->getBaseDir(),"/")."/_runtime";
        }
        if(!file_exists($runtimeDir)){
            File::createDir($runtimeDir);
        }
        return $runtimeDir;
    }
}

