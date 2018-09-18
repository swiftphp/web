<?php
namespace swiftphp\web;

use swiftphp\http\Context;
use swiftphp\web\IView;

/**
 * 控制器接口
 * @author Tomix
 *
 */
interface IController
{
    /**
     *  上下文
     * @param Context $value
     */
    function setContext(Context $value);

    /**
     * 区域名称
     * @param string $value
     */
    function setAreaName($value);

    /**
     * 区域前缀
     * @param string $value
     */
    function setAreaPrefix($value);

    /**
     * 视图文件
     * @param string $value
     */
    function setViewFile($value);

    /**
     * 初始化参数
     * @param array $value
     */
    function setInitParams($value);

    /**
     * 设置视图引擎
     * @param IView $value
     */
    function setViewEngine(IView $value);

    /**
     * 获取区域名
     */
    function getAreaName();

    /**
     * 获取当前激活的操作名
     */
    function getActionName();

    /**
     * 设置是否调试模式
     * @param bool $value
     */
    function setDebug($value);

    /**
     * 设置运行时目录
     * @param string $value
     */
    function setRuntimeDir($value);

    /**
     * 激活控制器方法
     * @param string $action
     */
    function invoke($action);
}

