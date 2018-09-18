<?php
namespace swiftphp\web\internal\tags;

use swiftphp\web\ITag;

/**
 * 标签基类
 * @author Tomix
 *
 */
abstract class TagBase implements ITag
{
    /**
     * 内部html
     * @var string
     */
    private $m_innerHtml;

    /**
     * 标签属性
     * @var array
     */
    private $m_attributes=[];

    /**
     * 内部html
     * @return string
     */
    protected function getInnerHtml()
    {
        return $this->m_innerHtml;
    }

    /**
     * 标签属性
     * @return array
     */
    protected function getAttributes()
    {
        return $this->m_attributes;
    }

    /**
     * 获取标签渲染后的内容
     * @param array $outputParams 递归输出参数,用于子标签的呈现
     */
    public abstract function getContent(&$outputParams=[]);

    /**
     * 设置标签内部html
     * @param string $value
     */
    public function setInnerHtml($value)
    {
        $this->m_innerHtml=$value;
    }

    /**
     * 设置标签属性
     * @param string $name
     * @param mixed $value
     */
    public function addAttribute($name,$value)
    {
        $setter="set".ucfirst($name);
        if(method_exists($this, $setter)){
            $this->$setter($value);
        }else{
            $this->m_attributes[$name]=$value;
        }
    }

    /**
     * 移除属性
     * @param string $name
     */
    public function removeAttribute($name)
    {
        if(array_key_exists($name, $this->m_attributes)){
            unset($this->m_attributes[$name]);
        }
    }
}

