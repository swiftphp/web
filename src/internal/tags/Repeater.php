<?php
namespace swiftphp\web\internal\tags;

use swiftphp\web\util\HtmlHelper;

/**
 * 遍历数据集合标签
 * @author Tomix
 * @deprecated 使用Iterator代替
 *
 */
class Repeater extends TagBase
{
	/**
	 * 数据源
	 * @var array
	 */
    protected $dataSource=[];

	/**
	 * 状态标识
	 * @var string
	 */
    protected $status="status";

    protected $showTree="false";
    protected $primaryKey;
    protected $parentKey;
    protected $rootKey="";

	/**
	 * 模板行标签
	 * @var string
	 */
	protected $templateTag="template";

    public function setDataSource($value)
    {
        $this->dataSource=$value;
    }
    public function setData($value)
    {
        $this->dataSource=$value;
    }

    public function setStatus($value)
    {
        $this->status=$value;
    }
    public function setShowTree($value)
    {
        $this->showTree=$value;
    }
    public function setPrimaryKey($value)
    {
        $this->primaryKey=$value;
    }
    public function setParentKey($value)
    {
        $this->parentKey=$value;
    }
    public function setRootKey($value)
    {
        $this->rootKey=$value;
    }

	/**
	 * 获取标签渲染后的内容
	 * @return string
	 */
    public function getContent(&$outputParams=[])
	{
		if(!is_array($this->dataSource)){
			return "";
		}

		//模板html
		$template=$this->getInnerHtml();
		$pattern="/<".$this->templateTag.">(.*)<\/".$this->templateTag.">/isU";
		$matches=[];
		if(preg_match($pattern, $template,$matches)){
		    $template=$matches[1];
		}
		//根据数据源替换变量
		$data=[];
		$this->showTree=strtolower($this->showTree);
		$this->showTree=($this->showTree=="true" || $this->showTree =="1"?true:false);
		if($this->showTree){
			foreach ($this->dataSource as $dr){
    		    if(is_object($dr)){
    		        $dr=get_object_vars($dr);
    		    }
				if($dr[$this->parentKey]==$this->rootKey){
					$data[]=$dr;
				}
			}
		}else{
			$data=$this->dataSource;
		}
		$builder=$this->buildTemplate($data, $template);
		$builder=$this->getIfTemplateContent($builder);

		//清空多余的占位
		$pattern="/#\{[\w\.]+\}/isU";
		$builder=preg_replace($pattern, "", $builder);
		return $builder;
	}

	protected function buildTemplate($data,$template,$parentRow=null,$level=0)
	{
		$builder="";
		for($i=0;$i<count($data);$i++){
		    $_template=$template;

			//树状数据,这里构建子模板
			if($this->showTree){
			    $child=$this->getTemplateChild($_template);
				$innerHtml=$child["innerHtml"];
				$outerHtml=$child["outerHtml"];
				$_parentKey=$child["parent"];
				$_parentKey=str_replace("#{", "", $_parentKey);
				$_parentKey=str_replace("}", "", $_parentKey);
				$_parentKey=trim($_parentKey);

				$template_child="";
				if(array_key_exists($_parentKey,$data[$i])){
				    if(is_object($data[$i])){
				        $data[$i]=get_object_vars($data[$i]);
				    }
					$parentId=$data[$i][$_parentKey];
					$target=[];
					foreach ($this->dataSource as $dr){
					    if(is_object($dr)){
					        $dr=get_object_vars($dr);
					    }
						if($dr[$this->parentKey]==$parentId){
							$target[]=$dr;
						}
					}
					$template_child=$this->buildTemplate($target, $innerHtml,$data[$i],$level+1);
				}

				$_template=str_replace($outerHtml, $template_child, $_template);
			}

			//状态信息行
			if(!empty($this->status)){
				$arr=["index"=>(string)$i
						,"count"=>(string)($i+1)
						,"first"=>($i==0)?"true":"false"
						,"odd"=>($i/2==0)?"true":"false"
						,"last"=>($i==count($data)-1)?"true":"false"];
				$statusTemplate="#".$this->status;
				foreach ($arr as $k=>$v){
				    $_template=str_replace($statusTemplate.".".$k, $v, $_template);
				}
			}

			//匹配上级字段#{_parent.[key]}
			if($parentRow!=null && is_array($parentRow))
			{
				foreach ($parentRow as $field=>$value){
				    $_template=preg_replace("/#{[\s]{0,}_parent.".$field."[\s]{0,}}/",$value,$_template);
				}

			}

			//当前行
			$row=$data[$i];
			if(is_object($row)){
			    $row=get_object_vars($row);
			}
			if(is_array($row)){
				foreach ($row as $field=>$value){
					if(!is_array($value) && !is_object($value)){
						$value=str_replace("$", "\\\$", $value);//保护系统表达式
						$_template=preg_replace("/#{[\s]{0,}".$field."[\s]{0,}}/",$value,$_template);
					}else if(is_array($value)){
					    $_template=$this->addArrayParams($_template, $value, $field);
					}else if(is_object($value)){
						$value=get_object_vars($value);
						$_template=$this->addArrayParams($_template, $value,$field);
					}
				}
			}
			$_template=preg_replace("/#{([0-9a-zA-Z_ ]{0,})}/","",$_template);
			$builder .= trim($_template);
		}
		return $builder;
	}

	protected function getIfTemplateContent($template)
	{
		$beans=[];
		$pattern="/<ifTemplate[^>]*>(.*)<\/ifTemplate>/isU";
		$matches=[];
		if(preg_match_all($pattern,$template,$matches,PREG_SET_ORDER)>0){
			foreach($matches as $match){
				if(count($match)>=2){
					$outerHtml=$match[0];
					$innerHtml=$match[1];
					$atts=HtmlHelper::getTagAttributes($outerHtml);
					$properties=["outerHtml"=>$outerHtml,"innerHtml"=>$innerHtml,"attributes"=>$atts];
					$beans[]=$properties;
				}
			}
		}
		foreach ($beans as $bean){
            $template=$this->replaceIfTemplate($bean, $template);
		}
		return trim($template);
	}

	protected function replaceIfTemplate($bean,$template)
	{
	    $atts=$bean["attributes"];
	    $outerHtml=$bean["outerHtml"];
	    $innerHtml=$bean["innerHtml"];
	    $compare="";
	    if(array_key_exists("compare",$atts)){
	        $compare=$atts["compare"];
	    }
	    if(empty($compare)){
	        $compare="=";
	    }
	    $compare=htmlspecialchars_decode($compare);

	    if(isset($atts["exp"])){
	        $atts["exp"]=htmlspecialchars_decode($atts["exp"]);

	        //使用表达式
	        $code="if(".$atts["exp"].")\$template=str_replace(\$outerHtml, \$innerHtml, \$template);
						else \$template=str_replace(\$outerHtml, \"\", \$template);";
	        eval($code);
	    }else if(isset($atts["value"]) && isset($atts["test"])){
	        //兼容旧版本
	        if($this->ifTemplateCompare($atts["value"], $atts["test"],$compare)){
	            $template=str_replace($outerHtml, $innerHtml, $template);
	        }else{
	            $template=str_replace($outerHtml, "", $template);
	        }
	    }
	    return $template;
	}

	protected function ifTemplateCompare($value,$test,$compare="==")
	{
		if($compare=="=" || $compare == ""){
			$compare="==";
		}
		if($compare=="<>"){
			$compare="!=";
		}

		return ($compare=="<" && $value < $test)
		||($compare=="<=" && $value <= $test)
		||($compare=="==" && $value == $test)
		||($compare==">=" && $value >= $test)
		||($compare==">" && $value > $test)
		||($compare=="!=" && $value != $test);
	}

	/**
	 * 取得子模板内容
	 * 子模板没有隔行模板标签,所以只有一个模板行
	 */
	protected function getTemplateChild($parentHtml)
	{
		//返回值数组:0,parent属性;1,outerHtml,2,innerHtml
		$tagName="template";
		$pattern="/<".$tagName."[^>]{1,}parent[\s]*=[\s]*[\"|\']([^\s]{1,})[\"|\'][^>]*>(.*)<\/".$tagName.">/is";//贪婪模式
		$matches=[];
		if(preg_match_all($pattern,$parentHtml,$matches,PREG_SET_ORDER)>0){
			$match=$matches[0];
			return ["outerHtml"=>$match[0],"parent"=>$match[1],"innerHtml"=>$match[2]];
		}
		return null;

	}


	protected function addArrayParams($template,$array,$prefix="")
	{
		foreach ($array as $key=>$value){
			$_prefix=$prefix.".".$key;
			if(is_array($value)){
				$template=$this->addArrayParams($template, $value,$_prefix);
			}else if(is_object($value)){
				$_value=get_object_vars($value);
				$template=$this->addArrayParams($template, $_value,$_prefix);
			}else{
				$value=str_replace("$", "\\\$", $value);
				$template=preg_replace("/#{[\s]{0,}".$prefix.".".(string)$key."[\s]{0,}}/",$value,$template);
			}
		}
		return $template;
	}
}