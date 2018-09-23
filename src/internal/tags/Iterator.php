<?php
namespace swiftphp\web\internal\tags;

use swiftphp\common\util\Convert;
use swiftphp\web\util\HtmlHelper;

/**
 * 遍历数据集合标签
 * 有已知的BUG:多个Iterator标签套嵌时,父标签会把子标签的相同字段覆盖;按现时的视图引擎渲染方式,此BUG无法消除.
 * @author Tomix
 *
 */
class Iterator extends TagBase
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

    /**
     * 是否显示树型数据
     * @var boolean
     */
    protected $showTree="false";

    /**
     * 显示树型数据时,主键字段名
     * @var string
     */
    protected $primaryKey;

    /**
     * 显示树型数据时,父数据字段名
     * @var string
     */
    protected $parentKey;

    /**
     * 显示树型数据时,根字段值
     * @var string
     */
    protected $rootKey="";

    /**
     * 设置数据源
     * @param array $value
     */
    public function setDataSource($value)
    {
        $this->dataSource=$value;
    }

    /**
     * 设置数据源(setDataSource的别名)
     * @param array $value
     */
    public function setData($value)
    {
        $this->dataSource=$value;
    }

    /**
     * 设置状态标识,默认为status
     * @param string $value
     */
    public function setStatus($value)
    {
        $this->status=$value;
    }

    /**
     * 是否显示树型数据
     * @param boolean $value
     */
    public function setShowTree($value)
    {
        $this->showTree=$value;
    }

    /**
     * 显示树型数据时,主键字段名
     * @param string $value
     */
    public function setPrimaryKey($value)
    {
        $this->primaryKey=$value;
    }

    /**
     * 显示树型数据时,父数据字段名
     * @param string $value
     */
    public function setParentKey($value)
    {
        $this->parentKey=$value;
    }

    /**
     * 显示树型数据时,根字段值
     * @param string $value
     */
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
        //数据
        $data=[];
        $showTree=strtolower($this->showTree);
        if($showTree=="true"||$showTree=="1"){
            foreach ($this->dataSource as $item){
                if(Convert::getFieldValue($item, $this->parentKey,true)==$this->rootKey){
                    $data[]=$item;
                }
            }
        }else{
            $data=$this->dataSource;
        }

        //根据数据源替换变量
        $builder=$this->buildTemplate($this->getInnerHtml(),$data,null,$outputParams);

        //清空多余的#占位
        $pattern="/#\{[\w\.]+\}/isU";
        $builder=preg_replace($pattern, "", $builder);
        return $builder;
    }

    /**
     * 替换模板占位
     * @param string $template
     * @param array $data
     * @param array|object $parentRow
     * @param array $outParams
     * @return string
     */
    private function buildTemplate($template,$data,$parentRow,&$outParams=[])
    {
        if(empty($data)){
            return "";
        }

        $child=$this->getChildTemplate($template);
        $childAttr=[];
        if($child){
            $childAttr=HtmlHelper::getTagAttributes($child["outerHtml"]);
        }

        //匹配参数
        $builder="";
        for($i=0;$i<count($data);$i++){
            $line=$data[$i];
            $_template=$template;

            //树状下一级数据
            if($child){
                $childData=$this->getChildData($line, $childAttr);
                $childTemplate=$this->buildTemplate($child["innerHtml"], $childData, $line,$outParams);
                $_template=str_replace($child["outerHtml"], $childTemplate, $_template);
            }

            //内置#号表达式
            $_template=$this->buildTemplateRow($_template, $line, "/#{[\s]{0,}([^\s]{1,})[\s]{0,}}/isU",$outParams);

            //系统表达式
            $_template=$this->buildTemplateRow($_template, $line, "/\\\${".$this->getVarPrefix().":[\s]{0,}([^\s]{1,})[\s]{0,}}/isU",$outParams);

            //状态信息行
            if(!empty($this->status)){
                $arr=["index"=>(string)$i
                    ,"count"=>(string)($i+1)
                    ,"first"=>($i==0)?"true":"false"
                    ,"odd"=>($i/2==0)?"true":"false"
                    ,"last"=>($i==count($data)-1)?"true":"false"];
                $pattern="/#".$this->status."\.(index|count|first|odd|last)/isU";
                $matches=[];
                if(preg_match_all($pattern, $_template,$matches,PREG_PATTERN_ORDER)){
                    for($j=0;$j<count($matches[0]);$j++){
                        $key=$matches[1][$j];
                        $search=$matches[0][$j];
                        if(array_key_exists($key, $arr)){
                            $_template=str_replace($search, $arr[$key], $_template);
                        }
                    }
                }
            }

            //匹配上级字段${parent.[key]}
            if(!empty($parentRow)){
                $pattern="/[#|\\\$]\{[\s]*parent\.([^\{\}]{1,})[\s]*\}/";
                $matches=[];
                if(preg_match_all($pattern, $_template,$matches,PREG_PATTERN_ORDER)){
                    for($j=0;$j<count($matches[0]);$j++){
                        $key=$matches[1][$j];
                        $search=$matches[0][$j];
                        $value=null;
                        if(HtmlHelper::getUIParams($parentRow, $key,$value)){
                            $_template=str_replace($search, $value, $_template);
                        }
                    }
                }
            }

            $builder.=$_template;
        }
        return $builder;
    }

    /**
     *
     * @param unknown $parentRow
     * @param unknown $childAttr
     * @return mixed|boolean|array|unknown[]
     */
    private function getChildData($parentRow,$childAttr)
    {
        $key=array_key_exists("data", $childAttr)?$childAttr["data"]:"";
        if(empty($key)){
            $key=array_key_exists("parent", $childAttr)?$childAttr["parent"]:"";
        }
        $key=str_replace("#{", "", $key);
        $key=str_replace("\${".$this->getVarPrefix().":", "", $key);
        $key=str_replace("}", "", $key);
        $key=trim($key);
        $value=null;
        if(HtmlHelper::getUIParams($parentRow, $key,$value)){
            if(array_key_exists("data", $childAttr)){
                //直接从属性返回
                return $value;
            }else if(array_key_exists("parent", $childAttr)){
                //从数据源搜索
                $values=[];
                foreach ($this->dataSource as $item){
                    if(Convert::getFieldValue($item, $this->parentKey,true)==$value){
                        $values[]=$item;
                    }
                }
                return $values;
            }
        }
    }

    /**
     * 替换每数据行的模板
     * @param string $template
     * @param array|object $dataRow
     * @param string $pattern
     * @param array $outParams
     * @return mixed
     */
    private function buildTemplateRow($template,$dataRow,$pattern,&$outParams=[])
    {
        $matches=[];
        if(preg_match_all($pattern,$template,$matches)){
            for($i=0;$i<count($matches[0]);$i++){
                $search=$matches[0][$i];
                $paramKey=$matches[1][$i];
                $value=null;
                if(HtmlHelper::getUIParams($dataRow, $paramKey,$value)){
                    if(is_array($value)||is_object($value)){
                        //数组或对象,附加到输出参数
                        $key=uniqid()."-".$paramKey;//唯一key
                        $outParams[$key]=$value;
                        //转为${key}表达式的全局参数
                        $template=str_replace($search, "\${".$this->getVarPrefix().":".$key."}", $template);
                    }else if(is_numeric($value)||is_string($value)||is_bool($value)){
                        //数字或字符串,替换
                        $template=str_replace($search, $value, $template);
                    }
                }
            }
        }
        return $template;
    }

    /**
     * 取得子模板内容
     * <template parent="parent"></template>||<template data="parent"></template>
     */
    protected function getChildTemplate($parentHtml)
    {
        //返回值数组:parent,outerHtml,innerHtml,data
        $tagName="template";
        $matches=[];
        $pattern="/<".$tagName."[^>]+>(.*)<\/".$tagName.">/is";//贪婪模式
        if(preg_match($pattern,$parentHtml,$matches)){
            return ["outerHtml"=>$matches[0],"innerHtml"=>$matches[1]];
        }
        return false;
    }
}

