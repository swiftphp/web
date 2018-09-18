<?php
namespace swiftphp\web\internal\tags;

/**
 * 单选框
 * @author Tomix
 *
 */
class Radio extends ListItemTagBase
{
    /**
     * 选中值
     * @var string
     */
    protected $m_checkedValue;

    /**
     * 名称
     * @var string
     */
    protected $m_name;

    /**
     * 自定义选项集(类json格式键值对)
     * @var string
     */
    protected $m_items="";

    /**
     * 设置选中值
     * @param string $value
     */
    public function setCheckedValue($value)
    {
        $this->m_checkedValue=$value;
    }

    /**
     * 设置名称
     * @param string $value
     */
    public function setName($value)
    {
        $this->m_name=$value;
    }

    /**
     * 自定义选项集(类json格式键值对)
     * @param string $value
     */
    public function setItems($value)
    {
        $this->m_items=$value;
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
            $builder .= "<input type=\"radio\" name=\"".$this->m_name."\" value=\"".$item["value"]."\"".$attStr." /><label>".$item["text"]."</label>\r\n";
        }

        //client items
        foreach ($clientItems as $item){
            $attStr=$attributeString;
            if($item["value"]==$this->m_checkedValue){
                $attStr.=" checked";
            }
            $builder .= "<input type=\"radio\" name=\"".$this->m_name."\" value=\"".$item["value"]."\"".$attStr." /><label>".$item["text"]."</label>\r\n";
        }

        return $builder;
    }

    /**
     * 加载客户端口定义的选项值，如果客户端有定义，则只呈现客户端的选项
     * 客户端选项标识如:value="{0,A}{1,B}"
     * @return void
     */
    protected function loadClientItems()
    {
        $items=[];
        if(!empty($this->m_items)){
            $string=$this->m_items;
            $string=str_replace("\\,", "######", $string);
            $string=str_replace("\\:", "#######", $string);
            $string=str_replace("\"", "", $string);
            $string=str_replace("'", "", $string);
            $items_array=explode(",", $string);
            foreach ($items_array as $item_string){
                $item_string=str_replace("{","",$item_string);
                $item_string=str_replace("}","",$item_string);
                $item_array=explode(":",$item_string);
                if(count($item_array)==2){
                    $_name=$item_array[0];
                    $_value=$item_array[1];
                    $_value=str_replace("######", ",", $_value);
                    $_value=str_replace("#######", ":", $_value);
                    $items[]=["value"=>$_name,"text"=>$_value];
                }
            }
        }
        return $items;
    }
}

