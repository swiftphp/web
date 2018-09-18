<?php
namespace swiftphp\web\internal\tags;

/**
 * 格式化数字
 * @author Tomix
 *
 */
class NumberFormat extends TagBase
{
    /**
     * 数值
     * @var double
     */
    private $m_value=0;

    /**
     * 小数位数
     * @var integer
     */
    private $m_decimals=0;

    /**
     * 小数点符号
     * @var string
     */
    private $m_decimalpoint=".";

    /**
     * 千位分隔符
     * @var string
     */
    private $m_separator=",";

    /**
     * 数值
     * @param double $value
     */
    public function setValue($value)
    {
        $this->m_value=$value;
    }

    /**
     * 小数位数
     * @param integer $value
     */
    public function setDecimals($value)
    {
        $this->m_decimals=$value;
    }

    /**
     * 小数点符号
     * @param string $value
     */
    public function setDecimalpoint($value)
    {
        $this->m_decimalpoint=$value;
    }

    /**
     * 千位分隔符
     * @param string $value
     */
    public function setSeparator($value)
    {
        $this->m_separator=$value;
    }

    /**
     * 获取标签渲染后的内容
     */
    public function getContent(&$outputParams=[])
    {
        $exp=trim($this->m_value);
        $code="return (".$exp.");";
        $value=eval($code);
        return number_format($value,$this->m_decimals,$this->m_decimalpoint,$this->m_separator);
    }
}