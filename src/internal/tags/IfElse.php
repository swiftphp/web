<?php
namespace swiftphp\web\internal\tags;

/**
 * 判断标签
 * @author Tomix
 *
 */
class IfElse extends TagBase
{
    /**
     * 条件表达式
     * @var string
     */
    private $m_exp=true;

    /**
     * 条件表达式
     * @param string $value
     */
    public function setExp($value)
    {
        $this->m_exp=$value;
    }

    /**
     * 获取标签渲染后的内容
     * {@inheritDoc}
     * @see \swiftphp\core\web\tags\TagBase::getContent()
     */
    public function getContent(&$outputParams=[])
    {
        $elseHtml="";
        $elseTemp="";
        $pattern="/<else>(.*)<\/else>/isU";
        $matches=[];
        if(preg_match($pattern, $this->getInnerHtml(),$matches)){
            $elseHtml = $matches[0];
            $elseTemp= $matches[1];
        }

        $compare=true;
        $exp=htmlspecialchars_decode($this->m_exp);
        if(!empty($this->m_exp)){
            $code="if(".$exp.") return true;else return false;";
            $compare=eval($code);
        }

        if($compare){
            return str_replace($elseHtml, "", trim($this->getInnerHtml()));
        }else{
            return trim($elseTemp);
        }
    }


}
