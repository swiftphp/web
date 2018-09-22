<?php
namespace swiftphp\web;

/**
 * 标签接口
 * @author Tomix
 *
 */
interface ITag
{

    /**
     * 获取标签渲染后的内容
     * @param array $outputParams 递归输出参数,用于子标签的呈现
     */
    function getContent(&$outputParams=[]);

    /**
     * 设置标签内部html
     * @param string $value
     */
    function setInnerHtml($value);

    /**
     * 设置标签属性
     * @param string $name
     * @param mixed $value
     */
    function addAttribute($name,$value);

    /**
     * 移除属性
     * @param string $name
     */
    function removeAttribute($name);

    /**
     * 设置变量表达式前缀
     * @param string $value
     */
    function setVarPrefix($value);
}

