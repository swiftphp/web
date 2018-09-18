<?php
namespace swiftphp\web\internal\tags;

/**
 * 分页控制标签
 * @author Tomix
 *
 */
class Pagination extends TagBase
{
    //当前页面索引(默认值:1)
    public $pageIndex=1;

    //每页记录数(默认值:100)
    public $pageSize=100;

    //记录计数(默认值:0,空记录)
    public $rowCount=0;

    //显示链接数(默认为10)
    public $showLinks=10;

    //是否以数字显示页码,默认值为true.当赋值为false时不显示数字页码(包含第一页与最后页连接),与$showLinks=0等效;
    public $showNumberLinks=true;

    //第一页链接文字
    public $firstText="First";

    //上一页链接文字
    public $previousText="Previous";

    //上10页链接文字
    public $pre10Text="...";

    //下10页链接文字
    public $next10Text="...";

    //上N页链接文字
    public $preMultiText="...";

    //下N页链接文字
    public $nextMultiText="...";

    //下一页链接文字
    public $nextText="Next";

    //最后一页链接文字
    public $lastText="Last";

    //记录数为0时输出文本
    public $noResultText="No record for this result!";

    //信息顯示格式化文字
    public $formatInfo="Displaying: <strong>{0}</strong>/<strong>{1}</strong> pages(of <strong>{2}</strong> items)\r\n";

    //格式化url,只有一个格式化参数{0}，该参数将会被当前页面索引值代替
    public $formatHref="#";

    //格式化单击事件函数名,只有一个格式化参数{0}，该参数将会被当前页面索引值代替
    public $formatOnclick="";

    public $formatTitle="";

    //样式类
    public $class;

    //消息样式类
    public $infoClass;

    //当前页码样式类
    public $currentClass;


    public function setPageIndex($value)
    {
        $this->pageIndex = $value;
    }
    public function setPageSize($value)
    {
        $this->pageSize = $value;
    }
    public function setRowCount($value)
    {
        $this->rowCount = $value;
    }
    public function setShowLinks($value)
    {
        $this->showLinks = $value;
    }
    public function setShowNumberLinks($value)
    {
        $this->showNumberLinks = $value;
    }
    public function setFirstText($value)
    {
        $this->firstText = $value;
    }
    public function setPreviousText($value)
    {
        $this->previousText = $value;
    }
    public function setPre10Text($value)
    {
        $this->pre10Text = $value;
    }
    public function setNext10Text($value)
    {
        $this->next10Text = $value;
    }
    public function setPreMultiText($value)
    {
        $this->preMultiText = $value;
    }
    public function setNextMultiText($value)
    {
        $this->nextMultiText = $value;
    }
    public function setNextText($value)
    {
        $this->nextText = $value;
    }
    public function setLastText($value)
    {
        $this->lastText = $value;
    }
    public function setNoResultText($value)
    {
        $this->noResultText = $value;
    }
    public function setFormatInfo($value)
    {
        $this->formatInfo = $value;
    }
    public function setFormatHref($value)
    {
        $this->formatHref = $value;
    }
    public function setFormatOnclick($value)
    {
        $this->formatOnclick = $value;
    }
    public function setFormatTitle($value)
    {
        $this->formatTitle = $value;
    }
    public function setClass($value)
    {
        $this->class = $value;
    }
    public function setInfoClass($value)
    {
        $this->infoClass = $value;
    }
    public function setCurrentClass($value)
    {
        $this->currentClass = $value;
    }

    public function getContent(&$outputParams=[])
    {
        //echo $this->formatHref;
        //exit;

        $attrs=$this->getAttributes();

        //计算页数
        $pageCount=ceil($this->rowCount/$this->pageSize);
        if($pageCount==0){
            $pageCount = 1;
        }

        //构造客户端内容，开始div标签
        $content = "<div";

        //窗口样式
        if(isset($this->class) && $this->class != ""){
            $content .= " class=\"".$this->class."\"";
        }

        //客户端属性
        $attributes="";
        foreach ($attrs as $name=>$value){
            $attributes .= " ".$name."=\"".$value."\"";
        }
        $content .= $attributes.">\r\n";

        //记录数为0时返回无记录文本
        if($this->rowCount==0){
            $content .= $this->noResultText."</div>";
            return $content;
        }

        //导航信息
        $info=$this->formatInfo;
        $info=str_replace("{0}",$this->pageIndex,$info);
        $info=str_replace("{1}",$pageCount,$info);
        $info=str_replace("{2}",$this->rowCount,$info);
        $content .= "<span";
        if(isset($this->infoClass) && $this->infoClass != ""){
            $content .= " class=\"".$this->infoClass."\">";
        }else{
            $content .= ">";
        }
        $content .= $info;
        $content .= "</span>";

        //info为空,而且只有一页时,返回空字串
        if(empty($info) && $pageCount<=1){
            return "";
        }

        $content.="<ul>";

        //是否显示长导航
        $showMaster=($this->showNumberLinks && $this->showLinks>0);

        //结果大于1页时显示分页导航
        if($pageCount>1){
            //第一页,上一页链接
            if($this->pageIndex > 1){
                if($showMaster){
                    $content .= $this->buildLink(1,$this->firstText)."\r\n";
                }
                $content .= $this->buildLink($this->pageIndex-1,$this->previousText)."\r\n";
            }

            if($showMaster){
                //导航翻页
                $navPageCount=ceil($pageCount/$this->showLinks);
                if($navPageCount <= 0){
                    $navPageCount=1;
                }
                $navPageIndex=ceil($this->pageIndex/$this->showLinks);

                //前10页记录
                if($navPageIndex > 1){
                    if(empty($this->preMultiText)){
                        $this->preMultiText=$this->pre10Text;
                    }
                    $content .=$this->buildLink($this->pageIndex-$this->showLinks,$this->preMultiText)."\r\n";
                }

                //数字页码
                $skip=$this->pageIndex % $this->showLinks;
                if($skip==0){
                    $skip=$this->showLinks;
                }
                $start=	$this->pageIndex-$skip+1;
                $end=$this->pageIndex-$skip+$this->showLinks;
                for($i=$start;$i<$end+1;$i++){
                    if($i>$pageCount){
                        break;
                    }
                    if($i != $this->pageIndex){
                        $content .= $this->buildLink($i,$i)."\r\n";
                    }else{
                        if(isset($this->currentClass) && $this->currentClass != ""){
                            $content .= "<li class=\"".$this->currentClass."\"><span>".$i."</span></li>\r\n";
                        }else{
                            $content .= "<span><li>".$i."</span></li>\r\n";
                        }
                    }
                }

                //后10页记录
                if($navPageIndex < $navPageCount){
                    $currentIndex=$this->pageIndex+$this->showLinks;
                    if($currentIndex>$pageCount){
                        $currentIndex=$pageCount;
                    }
                    if(empty($this->nextMultiText)){
                        $this->nextMultiText=$this->next10Text;
                    }
                    if($showMaster){
                        $content .= $this->buildLink($currentIndex,$this->nextMultiText)."\r\n";
                    }
                }
            }

            //后一页,尾页链接
            if($this->pageIndex < $pageCount){
                $content .= $this->buildLink($this->pageIndex+1,$this->nextText)."\r\n";
                if($showMaster){
                    $content .= $this->buildLink($pageCount,$this->lastText)."\r\n";
                }
            }
        }

        //结束关闭div标签
        $content.="</ul>";
        $content.="</div>";

        return $content;
    }

    //构建链接文本
    private function buildLink($currentPageIndex,$linkText)
    {
        $returnValue="<li><a";
        if(isset($this->formatTitle) && $this->formatTitle != ""){	//title属性
            $returnValue .=" title=\"".$this->formatTitle."\"";
        }
        if(isset($this->formatOnclick) && $this->formatOnclick != ""){ //onclick属性
            $returnValue .=" onclick=\"".$this->formatOnclick."\"";
        }
        if(isset($this->formatHref) && $this->formatHref != ""){ //href属性
            $returnValue .=" href=\"".$this->formatHref."\"";
        }
        $returnValue .=">".$linkText."</a></li>";
        $returnValue=str_replace("{0}",$currentPageIndex,$returnValue);
        return $returnValue;
    }
}

