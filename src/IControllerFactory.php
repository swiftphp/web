<?php
namespace swiftphp\web;

/**
 * 控制器工厂接口
 * @author Tomix
 *
 */
interface IControllerFactory
{
    /**
     * 根据控制器名创建控制器实例
     * @param $controllerName 控制器名
     * @return IController 控制器实例
     */
    function create($controllerName);

    /**
     * 根据控制器类名创建控制器实例
     * @param $controllerClass 控制器类名
     * @return IController,控制器实例
     */
    function createByClass($controllerClass);

    /**
     * 控制器初始化属性
     * @param array $value
     */
    function setControllerProperties(array $value);
}

