<?php
namespace swiftphp\web\internal;

use swiftphp\config\IConfigurable;
use swiftphp\config\IConfiguration;
use swiftphp\web\IControllerFactory;
use swiftphp\common\util\ObjectUtil;
use swiftphp\common\BuiltInConst;
use swiftphp\web\IController;

/**
 * 内置控制器工厂
 * @author Tomix
 *
 */
class ControllerFactory implements IControllerFactory,IConfigurable
{
    /**
     * 配置实例
     * @var IConfiguration
     */
    private $m_config=null;

    /**
     * 控制器初始化属性
     * @var array
     */
    private $m_controllerProperties=[];

    /**
     * 注入配置实例
     * @param IConfiguration $value
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
    }

    /**
     * 控制器初始化属性(由配置文件配置的对象不会被注入)
     * @param array $value
     */
    public function setControllerProperties(array $value)
    {
        $this->m_controllerProperties=$value;
    }

    /**
     * 根据控制器名创建控制器实例
     * @param $controllerName 控制器名
     * @return IController 控制器实例
     */
    public function create($controllerName)
    {
        //控制器类型名
        $controllerClass=$controllerName."Controller";
        return $this->createByClass($controllerClass);
    }

    /**
     * 根据控制器类名创建控制器实例
     * @param $controllerClass 控制器类名
     * @return IController,控制器实例
     */
    public function createByClass($controllerClass)
    {
        //控制器实例
        $controller=null;

        //从对象工厂创建
        if(!is_null($this->m_config)){
            try{
                $controller=$this->m_config->getObjectFactory()->create($controllerClass);
            }catch (\Exception $e){}
            if(is_null($controller)){
                try{
                    $controller=$this->m_config->getObjectFactory()->createByClass($controllerClass);
                }catch (\Exception $e){}
            }
        }

        //无法从对象工厂创建时,直接创建
        if(is_null($controller)){
            //类型不存在
            if(!class_exists($controllerClass)){
                throw new \Exception("'".$controllerClass."' controller does not exists");
            }

            //从类型名称创建
            $controller = new $controllerClass();
        }

        //implement IController
        if(!($controller instanceof IController)){
            throw new \Exception("'".$controllerClass."' controller does not implement interface 'swiftphp\\core\\web\\IController'");
        }

        //return
        $this->configController($controller);
        return $controller;
    }

    /**
     * 注入控制器配置
     * @param unknown $obj
     */
    private function configController($controller)
    {
        //注入全局配置(值类型)
        if(!is_null($this->m_config)){
            $globalConfig=$this->m_config->getConfigValues(BuiltInConst::GLOBAL_CONFIG_SESSION);
            foreach ($globalConfig as $name => $value){
                ObjectUtil::setPropertyValue($controller, $name, $value);
            }
        }

        //注入初始化属性
        if(!empty($this->m_controllerProperties)){
            foreach ($this->m_controllerProperties as $name => $value){
                ObjectUtil::setPropertyValue($controller, $name, $value);
            }
        }

        //注入配置
        if(!is_null($this->m_config) && $controller instanceof IConfigurable){
            $controller->setConfiguration($this->m_config);
        }
    }
}

