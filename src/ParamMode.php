<?php
namespace swiftphp\web;

/**
 * 添加参数模式枚举
 * @author Tomix
 *
 */
class ParamMode
{
    /**
     * 同时作为模板与标签输出参数
     * @var integer
     */
    public static $both=0;

    /**
     * 作为模板输出参数
     * @var integer
     */
    public static $view=1;

    /**
     * 作为标签输出参数
     * @var integer
     */
    public static $tag=2;
}

