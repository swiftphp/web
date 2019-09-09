<?php
namespace swiftphp\web\twig\functions;

class Pagination
{
    public static function pagination(
        $pageIndex
        ,$pageSize
        ,$rowCount
        ,$formatHref=""
        ,$formatClick=""
        ,$showLinks=10
        ,$showNumberLinks=true
        ,$firstText="First"
        ,$previousText="Previous"
        ,$preMultiText="..."
        ,$nextMultiText="..."
        ,$nextText="Next"
        ,$lastText="Last"
        ,$noResultText=""
        ,$class=""
        ,$currentClass=""){
            
            //计算页数
            $pageCount=ceil($rowCount/$pageSize);
            if($pageCount==0){
                $pageCount = 1;
            }
            
            //构造客户端内容，开始div标签
            $content = "<div";
            
            //窗口样式
            if(isset($class) && $class != ""){
                $content .= " class=\"".$class."\"";
            }            
            
            $content .= ">\r\n";
            
            //记录数为0时返回无记录文本
            if($rowCount==0){
                $content .= $noResultText."</div>";
                return $content;
            }            
            
            //info为空,而且只有一页时,返回空字串
            if($pageCount<=1){
                return "";
            }
            
            $content.="<ul>";
            
            //是否显示长导航
            $showMaster=($showNumberLinks && $showLinks>0);
            
            //结果大于1页时显示分页导航
            if($pageCount>1){
                //第一页,上一页链接
                if($pageIndex > 1){
                    if($showMaster){
                        $content .= self::buildLink(1,$firstText,$formatHref,$formatClick)."\r\n";
                    }
                    $content .= self::buildLink($pageIndex-1,$previousText,$formatHref,$formatClick)."\r\n";
                }
                
                if($showMaster){
                    //导航翻页
                    $navPageCount=ceil($pageCount/$showLinks);
                    if($navPageCount <= 0){
                        $navPageCount=1;
                    }
                    $navPageIndex=ceil($pageIndex/$showLinks);
                    
                    //前10页记录
                    if($navPageIndex > 1){
                        $content .=self::buildLink($pageIndex-$showLinks,$preMultiText,$formatHref,$formatClick)."\r\n";
                    }
                    
                    //数字页码
                    $skip=$pageIndex % $showLinks;
                    if($skip==0){
                        $skip=$showLinks;
                    }
                    $start=	$pageIndex-$skip+1;
                    $end=$pageIndex-$skip+$showLinks;
                    for($i=$start;$i<$end+1;$i++){
                        if($i>$pageCount){
                            break;
                        }
                        if($i != $pageIndex){
                            $content .= self::buildLink($i,$i,$formatHref,$formatClick)."\r\n";
                        }else{
                            if(isset($currentClass) && $currentClass != ""){
                                $content .= "<li class=\"".$currentClass."\"><span>".$i."</span></li>\r\n";
                            }else{
                                $content .= "<span><li>".$i."</span></li>\r\n";
                            }
                        }
                    }
                    
                    //后10页记录
                    if($navPageIndex < $navPageCount){
                        $currentIndex=$pageIndex+$showLinks;
                        if($currentIndex>$pageCount){
                            $currentIndex=$pageCount;
                        }
                        if($showMaster){
                            $content .= self::buildLink($currentIndex,$nextMultiText,$formatHref,$formatClick)."\r\n";
                        }
                    }
                }
                
                //后一页,尾页链接
                if($pageIndex < $pageCount){
                    $content .= self::buildLink($pageIndex+1,$nextText,$formatHref,$formatClick)."\r\n";
                    if($showMaster){
                        $content .= self::buildLink($pageCount,$lastText,$formatHref,$formatClick)."\r\n";
                    }
                }
            }
            
            //结束关闭div标签
            $content.="</ul>";
            $content.="</div>";
            
            return $content;
            
            
    }
    
    //构建链接文本
    private static function buildLink($currentPageIndex,$linkText,$formatHref="",$formatClick="")
    {
        if(empty($linkText)){
            return "";
        }
        if($linkText=="{0}"){
            $linkText=$currentPageIndex;
        }
        $returnValue="<li><a";
        if(isset($formatClick) && $formatClick != ""){ //onclick属性
            $returnValue .=" onclick=\"".$formatClick."\"";
        }
        if(isset($formatHref) && $formatHref != ""){ //href属性
            $returnValue .=" href=\"".$formatHref."\"";
        }
        $returnValue .=">".$linkText."</a></li>";
        $returnValue=str_replace("{0}",$currentPageIndex,$returnValue);
        return $returnValue;
    }
}

