<?php
namespace swiftphp\web;

use swiftphp\http\IFilter;
use swiftphp\config\IConfigurable;
use swiftphp\config\IConfiguration;
use swiftphp\http\Context;
use swiftphp\http\FilterChain;
use swiftphp\web\IView;
use swiftphp\http\IOutput;
use swiftphp\web\internal\ControllerFactory;
use swiftphp\web\internal\out\HtmlView;
use swiftphp\web\internal\out\Base;

/**
 * MVC模型入口,Web过滤器
 * @author Tomix
 *
 */
class WebFilter implements IFilter,IConfigurable
{

    /**
     * 运行时缓存目录
     * @var string
     */
    private $m_runtimeDir;

    /**
     * 路由实例
     * @var IRoute
     */
    private $m_route;

    /**
     * 视图引擎
     * @var IView
     */
    private $m_viewEngine;

    /**
     * 配置实例
     * @var IConfiguration
     */
    private $m_config;

    /**
     * 是否调试模式
     * @var string
     */
    private $m_debug=false;

    /**
     * 控制器工厂
     * @var IControllerFactory
     */
    private $m_controllerFactory=null;

    /**
     * 错误控制器类型名
     * @var IController
     */
    private $m_errorController;

    /**
     * 设置运行时缓存目录
     * @param string $value
     */
    public function setRuntimeDir($value)
    {
        $this->m_runtimeDir=$value;
    }

    /**
     * 设置运行时缓存目录
     */
    public function getRuntimeDir()
    {
        $dir=$this->m_runtimeDir;
        if(empty($dir)){
            $dir=$this->m_config->getBaseDir()."/_runtime";
        }
        return $dir;
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
     * 控制器工厂实例
     * @param IControllerFactory $value
     */
    public function setControllerFactory(IControllerFactory $value)
    {
        $this->m_controllerFactory=$value;
    }

    /**
     * 设置错误控制器
     * @param IController $value
     */
    public function setErrorController(IController $value)
    {
        $this->m_errorController=$value;
    }

    /**
     * 获取控制器工厂(如果当前没有配置,则创建一个内置的默认工厂)
     * @return IControllerFactory
     */
    public function getControllerFactory()
    {
        //外部注入的控制器工厂
        $factory=$this->m_controllerFactory;

        //如果外部没有注入,从对象工厂创建默认的控制器工厂
        if(!$factory){
            $factory=$this->m_config->getObjectFactory()->createByClass(ControllerFactory::class);
        }

        //返回工厂实例
        return $factory;
    }


    /**
     * 注入路由实例
     * @param IRoute $value
     */
    public function setRoute(IRoute $value)
    {
        $this->m_route=$value;
    }

    /**
     * 获取路由实例
     * @return IRoute
     */
    public function getRoute()
    {
        return $this->m_route;
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
     * 注入配置实例
     * @param IConfiguration $value
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
    }

    /**
     * 获取配置实例
     * @return IConfiguration
     */
    public function getConfiguration()
    {
        return $this->m_config;
    }

    /**
     * 执行过滤方法
     * @param Context $context
     */
    public function filter(Context $context,FilterChain $chain)
    {
        //不应拦截过滤链
        //找不到控制器或激活不了方法时，转发到错误控制器的_404方法
        //找不到错误控制器或激活不了_404方法时,直接输出错误模板

        try{
            //         echo "area:".$this->getRoute()->getAreaName()."\r\n";
            //         echo "areaPrefix:".$this->getRoute()->getAreaPrefix()."\r\n";
            //         echo "controller:".$this->getRoute()->getControllerName()."\r\n";
            //         echo "action:".$this->getRoute()->getActionName()."\r\n";
            //         echo "view:".$this->getRoute()->getViewFile()."\r\n";
            //         var_dump($this->getRoute()->getInitParams());

            //视图引擎
            if(empty($this->m_viewEngine)){
                $this->m_viewEngine=new HtmlView();
                $this->m_viewEngine->setConfiguration($this->m_config);
            }

            //控制器工厂
            $controllerFactory=$this->getControllerFactory();

            //创建控制器实例
            $controller = $controllerFactory->create($this->getRoute()->getControllerName());

            //注入控制器属性
            $controller->setContext($context);
            $controller->setViewEngine($this->m_viewEngine);
            $controller->setRuntimeDir($this->getRuntimeDir());
            $controller->setDebug($this->m_debug);

            //注入控制器属性(路由)
            $controller->setAreaName($this->getRoute()->getAreaName());
            $controller->setAreaPrefix($this->getRoute()->getAreaPrefix());
            $controller->setViewFile($this->getRoute()->getViewFile());
            $controller->setInitParams($this->getRoute()->getInitParams());

            //激活控制器方法后，返回一个IOutput代理对象，并让response的输出代理指向该对象
            $model = $controller->invoke($this->getRoute()->getActionName());
            if(!empty($model)){
                if($model instanceof IOutput){
                    $context->getResponse()->setOutput($model);
                }else{
                    $context->getResponse()->setOutput(new Base($model));
                }
            }
        }catch (\Exception $ex){
            //调试状态下,直接向外抛出异常;否则调用错误控制器输出404
            if(!$this->m_debug && !is_null($this->m_errorController)){

                //注入控制器属性
                $this->m_errorController->setContext($context);
                $this->m_errorController->setViewEngine($this->m_viewEngine);
                $this->m_errorController->setRuntimeDir($this->getRuntimeDir());
                $this->m_errorController->setDebug($this->m_debug);
                try{
                    //激活404方法
                    $actionName="_404";
                    $model = $this->m_errorController->invoke($actionName);
                    if(!empty($model)){
                        if($model instanceof IOutput){
                            $context->getResponse()->setOutput($model);
                        }else{
                            $context->getResponse()->setOutput(new Base($model));
                        }
                    }
                }catch (\Exception $e){
                    throw $e;
                }
            }else {
                throw $ex;
            }
        }

        $chain->filter($context);
    }
}