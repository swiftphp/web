<?php
namespace swiftphp\web\internal\out;

use swiftphp\http\IOutput;

/**
 * 原始数据输出代理
 * @author Tomix
 *
 */
class Base implements IOutput
{
    /**
     * 需要输出的内容
     * @var mixed
     */
    protected $m_content=null;

    /**
     * 构造函数
     * @param mixed $content
     */
    public function __construct($content="")
    {
        $this->m_content=$content;
    }

    /**
     * 设置输出内容
     * @param mixed $value
     */
    public function setContent($value)
    {
        $this->m_content=$value;
    }

    /**
     * 输出
     * {@inheritDoc}
     * @see \swiftphp\core\http\IOutput::output()
     */
    public function output()
    {
        echo $this->m_content;
    }
}