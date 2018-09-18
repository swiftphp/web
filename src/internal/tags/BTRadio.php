<?php
namespace swiftphp\web\internal\tags;

/**
 * Bootstrap样式的单选框组
 * @author Tomix
 *
 */
class BTRadio extends Radio
{
    /**
     * BT样式类
     * @var string
     */
    private $m_class;

    /**
     * 设置BT样式类
     * @param string $value
     */
    public function setClass($value)
    {
        $this->m_class=$value;
    }


    /**
     * 实现父类getContent()抽象方法,取得控件呈现给客户端的内容
     * {@inheritDoc}
     * @see \swiftphp\web\tags\TagBase::getContent()
     */
    public function getContent(&$outputParams=[])
    {
        $items=$this->buildDataItems();
        $clientItems=$this->loadClientItems();

        //属性
        $attributeString="";
        $attributes=$this->getAttributes();
        foreach ($attributes as $name => $value){
            $attributeString.=" ".$name."=\"".$value."\"";
        }

        //html
        $builder="";

        //items
        foreach($items as $item){
            $attStr=$attributeString;
            if($item["value"]==$this->m_checkedValue){
                $attStr.=" checked";
            }
            $builder.="<label";
            if(!empty($this->m_class)){
                $builder.=" class=\"".$this->m_class."\"";
            }
            $builder.=">\r\n";
            $builder .= "<input type=\"radio\" name=\"".$this->m_name."\" value=\"".$item["value"]."\"".$attStr.">".$item["text"]."\r\n";
            $builder.="</label>\r\n";
        }

        //client items
        foreach ($clientItems as $item){
            $attStr=$attributeString;
            if($item["value"]==$this->m_checkedValue){
                $attStr.=" checked";
            }
            $builder.="<label";
            if(!empty($this->m_class)){
                $builder.=" class=\"".$this->m_class."\"";
            }
            $builder.=">\r\n";
            $builder .= "<input type=\"radio\" name=\"".$this->m_name."\" value=\"".$item["value"]."\"".$attStr.">".$item["text"]."\r\n";
            $builder.="</label>\r\n";
        }

        return $builder;
    }

}

