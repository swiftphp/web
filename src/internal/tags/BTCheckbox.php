<?php
namespace swiftphp\web\internal\tags;

/**
 * Bootstrap样式的复选框列表标签
 * @author Tomix
 *
 */
class BTCheckbox extends Checkbox
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
     * @see \swiftphp\core\web\tags\TagBase::getContent()
     */
    public function getContent(&$outputParams=[])
    {
        $items=$this->buildDataItems();
        $clientItems=$this->loadClientItems();

        //选中值
        $checkedValues=[];
        if(!empty($this->m_checkedValues)){
            if(is_array($this->m_checkedValues)){
                $checkedValues=$this->m_checkedValues;
            }else{
                $checkedValues=[$this->m_checkedValues];
            }
        }

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
            if(in_array($item["value"], $checkedValues)){
                $attStr.=" checked";
            }
            $builder.="<label";
            if(!empty($this->m_class)){
                $builder.=" class=\"".$this->m_class."\"";
            }
            $builder.=">\r\n";
            $builder .= "<input type=\"checkbox\" name=\"".$this->m_name."\" value=\"".$item["value"]."\"".$attStr.">".$item["text"]."\r\n";
            $builder.="</label>\r\n";
        }

        //client items
        foreach ($clientItems as $item){
            $attStr=$attributeString;
            if(in_array($item["value"], $checkedValues)){
                $attStr.=" checked";
            }
            $builder.="<label";
            if(!empty($this->m_class)){
                $builder.=" class=\"".$this->m_class."\"";
            }
            $builder.=">\r\n";
            $builder .= "<input type=\"checkbox\" name=\"".$this->m_name."\" value=\"".$item["value"]."\"".$attStr.">".$item["text"]."\r\n";
            $builder.="</label>\r\n";
        }

        return $builder;
    }
}