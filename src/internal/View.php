<?php
namespace swiftphp\web\internal;

use swiftphp\config\IConfigurable;
use swiftphp\config\IConfiguration;
use swiftphp\http\Context;
use swiftphp\web\IController;
use swiftphp\io\File;
use swiftphp\web\IView;

/**
 * 视图实现抽像基类
 * @author Tomix
 *
 */
abstract class View implements IView, IConfigurable
{
    /**
     * 配置实例
     * @var IConfiguration
     */
    protected $m_config;

    /**
     * 上下文对象
     * @var Context
     */
    protected $m_context=null;

    /**
     * 模板参数
     * @var array
     */
    protected $m_viewParams=[];

    /**
     * 标签参数
     * @var array
     */
    protected $m_tagParams=[];

    /**
     * 模板文件
     * @var string
     */
    protected $m_viewFile="";

    /**
     * 控制器
     * @var IController
     */
    protected $m_controller=null;

    /**
     * 是否调试模式
     * @var bool
     */
    protected $m_debug=false;

    /**
     * 运行时缓冲目录
     * @var string
     */
    protected $m_runtimeDir;

    /**
     * 输出参数索引器
     * @param string $name
     */
    public function __get($name)
    {
        if(array_key_exists($name, $this->m_viewParams)){
            return $this->m_viewParams[$name];
        }else if(array_key_exists($name, $this->m_tagParams)){
            return $this->m_tagParams[$name];
        }
        return "";
    }

    /**
     * 是否为调试模式
     * @param bool $value
     */
    public function setDebug($value)
    {
        $this->m_debug=$value;
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
     * 设置上下文对象
     * @param Context $context
     */
    public function setContext(Context $context)
    {
        $this->m_context=$context;
    }

    /**
     * 设置模板参数
     * @param array $params
     */
    public function setViewParams($params=[])
    {
        $this->m_viewParams=$params;
    }

    /**
     * 设置标签参数
     * @param array $params
     */
    public function setTagParams($params=[])
    {
        $this->m_tagParams=$params;
    }

    /**
     * 设置模板文件
     * @param string $viewFile
     */
    public function setViewFile($viewFile="")
    {
        $this->m_viewFile=$viewFile;
    }

    /**
     * 设置控制器
     * @param IController $controller
     */
    public function setController(IController $controller)
    {
        $this->m_controller=$controller;
    }

    /**
     * 获取当前绑定的响应对象
     * @return \swiftphp\http\Response
     */
    public function getResponse()
    {
        return $this->m_context->getResponse();
    }

    /**
     * 设置运行时缓冲目录
     * @param string $value
     */
    public function setRuntimeDir($value)
    {
        $this->m_runtimeDir=$value;
    }

    /**
     * 运行时目录
     * @return string
     */
    public function getRuntimeDir()
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

    /**
     * 获取视图被渲染后的完整输出内容
     */
    public abstract function getContent();
}

