<?php
namespace swiftphp\web\internal\tags;

use swiftphp\common\util\Convert;

/**
 * 下拉框列表控件
 * @author Tomix
 *
 */
class Select extends ListItemTagBase
{
    /**
     * 选中值
     * @var string
     */
    private $m_checkedValue;

    /**
     * 是否树状显示数据
     * @var bool
     */
    private $m_showTree=false;

    /**
     * 树状显示数据时,主键字段名
     * @var string
     */
    private $m_idField;

    /**
     * 树状显示数据时,上级字段名
     * @var string
     */
    private $m_pidField;

    /**
     * 树状显示数据时,根字段值
     * @var string
     */
    private $m_rootId;

    /**
     * 树状显示数据时,显示级数
     * @var int
     */
    private $m_level=-1;

    /**
     * 树状显示数据时,缩进间隔文本
     * @var string
     */
    private $m_separator="&nbsp;&nbsp;&nbsp;&nbsp;";

    /**
     * 树状显示数据时,选项文本前缀
     * @var string
     */
    private $m_prefix="";

    /**
     * 是否显示未定义或默认选项
     * @var string
     */
    private $m_showUndefine=false;

    /**
     * 显示未定义选项时的文本
     * @var string
     */
    private $m_undefineText="Undefine";

    /**
     * 显示未定义选项时的值
     * @var string
     */
    private $m_undefineValue="";

    /**
     * 设置选中值
     * @param string $value
     */
	public function setCheckedValue($value)
	{
	    $this->m_checkedValue = $value;
	}

	/**
	 * 是否树状显示数据
	 * @param bool $value
	 */
	public function setShowTree($value)
	{
	    $this->m_showTree = $value;
	}

	/**
	 * 树状显示数据时,主键字段名
	 * @param string $value
	 */
	public function setIdField($value)
	{
	    $this->m_idField = $value;
	}

	/**
	 * 树状显示数据时,上级字段名
	 * @param string $value
	 */
	public function setPidField($value)
	{
	    $this->m_pidField = $value;
	}

	/**
	 * 树状显示数据时,根字段值
	 * @param string $value
	 */
	public function setRootId($value)
	{
	    $this->m_rootId = $value;
	}

	/**
	 * 树状显示数据时,显示级数
	 * @param int $value
	 */
	public function setLevel($value)
	{
	    $this->m_level = $value;
	}

	/**
	 * 树状显示数据时,缩进文本
	 * @param string $value
	 */
	public function setSeparator($value)
	{
	    $this->m_separator = $value;
	}

	/**
	 * 树状显示数据时,选项前缀文本
	 * @param string $value
	 */
	public function setPrefix($value)
	{
	    $this->m_prefix=$value;
	}

	/**
	 * 是否显示未定义选项
	 * @param bool $value
	 */
	public function setShowUndefine($value)
	{
	    $this->m_showUndefine = $value;
	}

	/**
	 * 显示未定义选项时,选项文本
	 * @param string $value
	 */
	public function setUndefineText($value)
	{
	    $this->m_undefineText = $value;
	}

	/**
	 * 显示未定义选项时,选项值
	 * @param string $value
	 */
	public function setUndefineValue($value)
	{
	    $this->m_undefineValue = $value;
	}

	/**
	 * 实现父类getContent()抽象方法,取得控件呈现给客户端的内容
	 * {@inheritDoc}
	 * @see \swiftphp\web\tags\TagBase::getContent()
	 */
	public function getContent(&$outputParams=[])
	{
	    //选项列表
        $items=[];
        if($this->m_showTree){
            $items=$this->buildTreeItems();
        }else{
            $items=$this->buildDataItems();
        }

        //客户端自定义选项列表
		$clientItems=$this->loadClientItems();

		//返回文本值
		$builder="<select";

		//自定义属性值
		$attrs=$this->getAttributes();
		foreach ($attrs as $name=>$val){
		    $builder .= " ".$name."=\"".$val."\"";
		}
		$builder.=">\r\n";

		//未定义选项
		if($this->m_showUndefine){
		    $option="<option value=\"".$this->m_undefineValue."\"";
		    if($this->m_undefineValue==$this->m_checkedValue){
		        $option.=" selected";
		    }
		    $option.=">".$this->m_undefineText."</option>\r\n";
		    $builder.=$option;
		}

		//选项值
		foreach ($items as $item){
		    $option="<option value=\"".$item["value"]."\"";
		    if($item["value"]==$this->m_checkedValue){
		        $option.=" selected";
		    }
		    $option.=">".$item["text"]."</option>\r\n";
		    $builder.=$option;
		}

		//自定义的选项
		foreach ($clientItems as $item){
		    $option=$item["option"];
		    if($item["value"]==$this->m_checkedValue){
		        $option=str_replace("<option","<option selected",$option);
		    }
		    $builder.=$option."\r\n";
		}
		$builder.="</select>";
		return $builder;
	}

	/**
	 * 创建树装结构的选项集
	 * @return void
	 */
	private function buildTreeItems()
	{
	    $items=[];
	    $i=0;
	    $data=$this->m_dataSource;
	    foreach($data as $obj){
	        $rootArray=[];
	        if(isset($this->m_rootId)){
	            $rootArray[0]=$this->m_rootId;
	        }else{
	            $rootArray[0]="";
	            $rootArray[1]="0";
	            $rootArray[2]=null;
	        }

	        $pid=Convert::getFieldValue($obj, $this->m_pidField,true);
	        if(in_array($pid,$rootArray)){
	            //取得根的下一级子数据
	            $item=[];
	            $item["value"]=Convert::getFieldValue($obj, $this->m_valueField,true);
	            $item["text"]=$this->m_prefix.Convert::getFieldValue($obj, $this->m_textField,true);
	            $items[]=$item;
	            //unset($data[$i]);

	            //取所有子選項
	            $id=Convert::getFieldValue($obj, $this->m_idField,true);
	            $this->buildChildItems($data,$id,1,$items);
	        }
	        $i++;
	    }
	    return $items;
	}

	/**
	 * 创建树状结构时创建子选项集
	 * @param $data
	 * @param $pid
	 * @param $level
	 * @param $items
	 */
	private function buildChildItems(&$data,$pid,$level,&$items)
	{
	    if($this->m_level<0){
	        $this->m_level=99999999;
	    }
	    if($level > $this->m_level){
            return;
        }

        //文本缩进符号
        $sep="";
        for($i=0;$i<$level;$i++){
            $sep .= $this->m_separator;
        }
        $sep.=$this->m_prefix;
        $i=0;
        foreach($data as $obj){
            $pidValue=Convert::getFieldValue($obj, $this->m_pidField,true);
            if($pidValue==$pid){
                $item=[];
                $item["value"]=Convert::getFieldValue($obj, $this->m_valueField,true);
                $item["text"]=$sep.Convert::getFieldValue($obj, $this->m_textField,true);
                $items[]=$item;
                //unset($data[$i]);

                //取得所有子选项
                $idValue=Convert::getFieldValue($obj, $this->m_idField,true);
                $this->buildChildItems($data,$idValue,$level+1,$items);
            }
            $i++;
        }
	}

	/**
	 * 加载客户端口定义的选项值，如果客户端有定义，则只呈现客户端的选项
	 * 客户端选项标识如:value="{0,A}{1,B}"
	 * @return void
	 */
	private function loadClientItems()
	{
		$returnItems=[];
		$pattern="/<option[^<>\/]{0,}>[^<>]{0,}<\/option>|<option[^<>\/]{0,}\/>/";
		$matches =[];
		preg_match_all($pattern,$this->getInnerHtml(),$matches,PREG_SET_ORDER);
		foreach($matches as $match){
			$item=$match[0];
			$item=str_replace(" selected=\"selected\"","",$item);
			$pattern="/value=\"([^\"]{0,})\"/";
			$values=[];
			preg_match($pattern,$item,$values);
    		$value = $values[1];
			$arr=["value"=>$value,"option"=>$item];
			$returnItems[]=$arr;
		}
		return $returnItems;
	}
}