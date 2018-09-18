<?php
namespace swiftphp\web\internal\tags;

/**
 * 计算输出值标签
 * @author Tomix
 *
 */
class EvalValue extends TagBase
{
    /**
     * 计算表达式
     * @var string
     */
    private $m_exp=true;

    /**
     * 设置计算表达式
     * @param string $value
     */
    public function setExp($value)
    {
        $this->m_exp=$value;
    }

    /**
     * 获取标签渲染后的内容
     */
    public function getContent(&$outputParams=[])
    {
        if(empty($this->m_exp)){
            return "";
        }
        $exp=trim($this->m_exp);
        $code="return (".$exp.");";
        $value="";
        try{
            $value=eval($code);
        }catch (\Exception $ex){}
        return $value;
    }

}