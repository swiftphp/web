<?php
namespace swiftphp\web;

use swiftphp\http\Context;
use swiftphp\web\IController;

/**
 * 视图接口
 * @author Tomix
 *
 */
interface IView
{
    /**
     * 设置上下文对象
     * @param Context $context
     */
    function setContext(Context $context);

    /**
     * 设置模板参数
     * @param array $params
     */
    function setViewParams($params=[]);

    /**
     * 设置标签参数
     * @param array $params
     */
    function setTagParams($params=[]);

    /**
     * 设置模板文件
     * @param string $viewFile
     */
    function setViewFile($viewFile="");

    /**
     * 设置是否调试模式
     * @param bool $value
     */
    function setDebug($value);

    /**
     * 设置控制器
     * @param IController $controller
     */
    function setController(IController $controller);

    /**
     * 设置运行时缓冲目录
     * @param string $value
     */
    function setRuntimeDir($value);

    /**
     * 获取视图被渲染后的完整输出内容
     */
    function getContent();
}

