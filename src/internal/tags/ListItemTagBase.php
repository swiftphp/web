<?php
namespace swiftphp\web\internal\tags;

use swiftphp\common\util\ObjectUtil;

/**
 *
 * @author Tomix
 *
 */
abstract class ListItemTagBase extends TagBase
{

	/**
	 * 数据源(如果数据源为二维，必须设置$valueField,$titleField属性)
	 * @var array
	 */
	protected $m_dataSource=[];

	/**
	 * 值字段
	 * @var string
	 */
	protected $m_valueField;

	/**
	 * 文本字段
	 * @var string
	 */
	protected $m_textField;

	/**
	 * 设置数据源
	 * @param array $value
	 */
	public function setDataSource($value)
	{
	    $this->m_dataSource=$value;
	}

	/**
	 * 值字段名
	 * @param string $value
	 */
	public function setValueField($value)
	{
	    $this->m_valueField=$value;
	}

	/**
	 * 文本字段名
	 * @param string $value
	 */
	public function setTextField($value)
	{
	    $this->m_textField=$value;
	}

	/**
	 * 创建列表项
	 * @return array
	 */
	protected function buildDataItems()
	{
	    if(!is_array($this->m_dataSource) || count($this->m_dataSource)==0){
            return [];
        }

        $items=[];
        $firstItem=$this->m_dataSource[array_keys($this->m_dataSource)[0]];

        if(is_array($firstItem)){
            //二维表数组
            foreach ($this->m_dataSource as $item){
                if(array_key_exists($this->m_valueField, $item) && array_key_exists($this->m_textField, $item)){
                    $items[]=["value"=>$item[$this->m_valueField],"text"=>$item[$this->m_textField]];
                }
            }
        }else if(is_object($firstItem)){
            //一维对象数组
            $valueGetter=ObjectUtil::getGetter($firstItem, $this->m_valueField);
            $textGetter=ObjectUtil::getGetter($firstItem, $this->m_textField);
            foreach ($this->m_dataSource as $item){
                $value=null;
                if(!empty($valueGetter)){
                    $value=$item->$valueGetter();
                }else if(property_exists($item, $this->m_valueField)){
                    $field=$this->m_valueField;
                    $value=$item->$field;
                }
                $text=null;
                if(!empty($textGetter)){
                    $text=$item->$textGetter();
                }else if(property_exists($item, $this->m_textField)){
                    $field=$this->m_textField;
                    $text=$item->$field;
                }
                if(!is_null($value) && !is_null($text)){
                    $items[]=["value"=>$value,"text"=>$text];
                }
            }
        }else{
            //一维键值对数组
            foreach ($this->m_dataSource as $key => $value){
                $items[]=["value"=>$key,"text"=>$value];
            }
        }
        return $items;
	}

}

