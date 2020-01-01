<?php
namespace swiftphp\web\internal\out;

use swiftphp\http\IOutput;
use swiftphp\web\ITag;
use swiftphp\web\internal\View;
use swiftphp\common\util\StringUtil;
use swiftphp\web\util\HtmlHelper;
use swiftphp\common\util\ObjectUtil;
use swiftphp\common\util\Convert;

/**
 * 视图引擎输出
 * @author Tomix
 *
 */
class HtmlView extends View implements IOutput
{
    /**
     * 默认模板基本目录
     * @var string
     */
    protected $m_defaultViewDir="views";

    /**
     * 注册的标签库
     * @var array
     */
    protected $m_taglibs=["php"=>"swiftphp\\web\\internal\\tags"];

    /**
     * 标签统计处理标记
     * @var string
     */
    protected $m_tagPlaceHolder="run-on-server-tag";

    /**
     * 输出内容
     * @var string
     */
    protected $m_outputContent="";

    /**
     * 模板需要的参数
     * @var array
     */
    protected $m_onlyViewParams=[];

    /**
     * 变量表达式前缀
     * @var string
     */
    protected $m_varPrefix="";

    /**
     * 是否启用视图php脚本
     * @var bool
     */
    protected $m_phpEnable=true;

    /**
     * 设置是否启用视图php脚本
     * @param bool $value
     */
    public function setPhpEnable($value)
    {
        if($value=="1"||strtolower($value)=="true"){
            $this->m_phpEnable=true;
        }else if($value=="0" || strtolower($value)=="false"){
            $this->m_phpEnable=false;
        }
    }

    /**
     * 输出
     * {@inheritDoc}
     * @see \swiftphp\http\IOutput::output()
     */
    public function output()
    {
        //渲染后的视图
        if(empty($this->m_outputContent)){
            $this->m_outputContent=$this->getContent();
        }

        //根据是否启用视图php脚本判断输出
        if($this->m_phpEnable){
            $cacheFile=$this->getRuntimeDir()."/".md5($this->m_outputContent);
            if(!file_exists($cacheFile)){
                if(!file_exists($this->getRuntimeDir())){
                    mkdir($this->getRuntimeDir(),755);
                }
                file_put_contents($cacheFile, $this->m_outputContent);
            }
            require_once $cacheFile;
        }else{
            echo $this->m_outputContent;
        }
    }

    /**
     * 获取视图被渲染后的完整输出内容
     */
    public function getContent()
    {
        //读取视图内容;注意模板或部件文件的更新不会自动清空缓存
        $viewFile=$this->searchView();
        if(!file_exists($viewFile)){
            throw new \Exception("View file '".$this->m_viewFile."' does not exist.");
        }
        $view=file_get_contents($viewFile);
        $view=StringUtil::removeUtf8Bom($view);

        //合并视图的模板与部件,预处理标签
        $md5Key=md5($view);
        $this->m_varPrefix=$md5Key;
        $viewCacheFile=$this->getRuntimeDir()."/".$md5Key;
        $md5KeyKey=md5($md5Key);
        $tagLibInfoCacheFile=$this->getRuntimeDir()."/".$md5KeyKey;
        $paramCacheFile=$this->getRuntimeDir()."/".md5($md5KeyKey);
        $customParams=[];
        if(!$this->m_debug && file_exists($viewCacheFile)){
            //从缓存文件读取
            $view=file_get_contents($viewCacheFile);
            //taglibs
            if(file_exists($tagLibInfoCacheFile)){
                $this->m_taglibs=unserialize(file_get_contents($tagLibInfoCacheFile));
            }
            //定制参数
            if(file_exists($paramCacheFile)){
                $customParams=unserialize(file_get_contents($paramCacheFile));
            }
        }else{
            //重新处理,并写到缓存文件
            $view=$this->loadView($view,dirname($viewFile),$this->m_taglibs,$customParams);            
            if(!file_exists($this->getRuntimeDir())){
                mkdir($this->getRuntimeDir(),755);
            }
            file_put_contents($viewCacheFile, $view);
            file_put_contents($tagLibInfoCacheFile, serialize($this->m_taglibs));
            file_put_contents($paramCacheFile, serialize($customParams));
        }

        //定制参数通过setter注入当前实例(原始注入,类型转换需要setter实现)
        foreach ($customParams as $name => $value){
            $setter="set".ucfirst($name);
            if(method_exists($this, $setter)){
                $this->$setter($value);
            }
        }

        //替换参数与标签
        $view=$this->applyView($view);

        //返回
        $this->m_outputContent=$view;
        return $view;
    }

    /**
     * 替换变量,标签
     * @param string $view
     */
    protected function applyView($view)
    {
        //预处理视图参数(抽取模板需要的参数),为后面替换参数的过程加速
        $this->m_onlyViewParams = $this->preLoadViewParams($view);

        //替换标签
        $view=$this->loadTags($view);

        //替换变量
        $view=$this->loadParams($view);

        //清除空参数与标签
        $view=preg_replace("/\\\$\{".$this->m_varPrefix.":[^}]*\}/U","",$view);

        return $view;
    }

    /**
     * 替换标签内容
     * @param string $view 视图模板
     * @return mixed
     */
    protected function loadTags($view)
    {
        $tagOutHtml=$this->findTag($view);
        while ($tagOutHtml){
            $tagHtml=$this->getTagHtml($tagOutHtml);
            $view=str_replace($tagOutHtml, $tagHtml, $view);
            $tagOutHtml=$this->findTag($view);
        }
        return $view;
    }

    /**
     * 查询标签,返回标签内容
     * @param string $view
     * @return boolean|string
     */
    protected function findTag($view)
    {
        //$start="/<".$this->m_tagPlaceHolder." _tag=\"([a-zA-Z]{1,}[\w-]*):([a-zA-Z]{1,}[\w]*)\"/";
        $start="<".$this->m_tagPlaceHolder." ";
        $end="</".$this->m_tagPlaceHolder.">";

        //不套嵌的标签起止位置,搜索不到位置直接返回
        $startPos=strpos($view, $start);
        $endPos=strpos($view, $end);
        if(!$startPos || !$endPos || $endPos<=$startPos){
            return false;
        }

        //假设当前找到的结束标签为结束标签,那么要满足:
        //1.下一个起始标签比当前结束位置更后,或者找不到下一个起始标签
        //2,如果不满足,则继续向下找起始与结束标签
        $nextStartPos=$startPos;
        while(true){
            $nextStartPos=strpos($view, $start,$nextStartPos+1);

            //确认结束位置:下一个起始标签比当前结束位置更后,或者找不到下一个起始标签
            if($nextStartPos>$endPos||$nextStartPos===false){
                break;
            }
            $endPos=strpos($view, $end,$endPos+1);
        }
        $outerHtml=substr($view, $startPos,$endPos-$startPos+strlen($end));
        return $outerHtml;
    }

    /**
     * 获取标签替换后的内容
     * @param string $outerHtml
     * @return mixed
     */
    protected function getTagHtml($outerHtml,&$outputParams=[])
    {
        $tagHtml=$this->getTagContent($outerHtml,$outputParams);
        $childTagOutHtml=$this->findTag($tagHtml);
        while ($childTagOutHtml){
            $childTagHtml=$this->getTagHtml($childTagOutHtml,$outputParams);
            $tagHtml=str_replace($childTagOutHtml, $childTagHtml, $tagHtml);
            $childTagOutHtml=$this->findTag($tagHtml);
        }
        return $tagHtml;
    }

    /**
     * 获取标签内容
     * @param string $tagOuterHtml
     * @param array $outputParams
     * @throws \Exception
     */
    protected function getTagContent($tagOuterHtml,&$outputParams=[])
    {
        $attributes=HtmlHelper::getTagAttributes($tagOuterHtml);
        $innerHtml=HtmlHelper::getTagInnerHtml($tagOuterHtml, $this->m_tagPlaceHolder);

        //创建标签对象
        $types=explode(":",$attributes["_tag"]);
        $class=$this->m_taglibs[$types[0]]."\\".ucfirst($types[1]);
        if(!class_exists($class)){
            throw new \Exception("Call to undefined tag '".$attributes["_tag"]."'");
        }
        $obj=new $class();
        if(!($obj instanceof ITag)){
            throw new \Exception("Tag '".$attributes["_tag"]."' not implements swiftphp\\web\\ITag");
        }
        $obj->setInnerHtml($innerHtml);
        $obj->setVarPrefix($this->m_varPrefix);

        //注入标签属性
        foreach ($attributes as $name=>$value){
            //系统保留属性
            if($name=="_tag" || $name=="_id"){
                continue;
            }
            //bool类型转换
            if(strtolower($value)=="true"){
                $value=1;
            }else if(strtolower($value)=="false"){
                $value=0;
            }
            //匹配动态参数(可以匹配多个参数)
            $matches=[];
            if(preg_match_all("/\\\$\{".$this->m_varPrefix.":([^\s]{1,})\}/U",$value,$matches)){
                $holders=$matches[0];
                $keys=$matches[1];
                for($i=0;$i<count($keys);$i++){
                    $key=$keys[$i];
                    $holder=$holders[$i];
                    $_value=null;
                    $hasValue=HtmlHelper::getUIParams($outputParams, $key,$_value);//先从递归的参数取值
                    if(!$hasValue){
                        $hasValue=HtmlHelper::getUIParams($this->m_tagParams, $key,$_value);//从全局参数取值
                    }
                    if($hasValue){
                        if(!is_array($_value) && !is_object($_value)){
                            $value=str_replace($holder, $_value, $value);
                        }else{
                            //取出来的值为对象或数组,则忽略后面的参数
                            $value=$_value;
                            break;
                        }
                    }else{
                        //取不到值,以空替代
                        $value=str_replace($holder, "", $value);
                    }
                }
            }

            //注入属性
            if(ObjectUtil::hasSetter($obj, $name)){
                ObjectUtil::setPropertyValue($obj, $name, $value);
            }else{
                $obj->addAttribute($name, $value);
            }
        }
        return $obj->getContent($outputParams);
    }

    /**
     * 替换变量
     * @param string $view
     */
    protected function loadParams($view)
    {
        //从视图参数取值填充占位符
        foreach ($this->m_onlyViewParams as $param){
            $keys=explode(".", $param);
            $value=null;

            //取第一个值(第一个值必定是索引数组)
            $key=$keys[0];
            if(array_key_exists($key, $this->m_viewParams)){
                $value=$this->m_viewParams[$key];
            }

            //迭代取值
            if(count($keys)>1){
                for($i=1;$i<count($keys);$i++){
                    $key=$keys[$i];
                    if(!is_null($value)){
                        if(is_object($value)){
                            //优先使用getter取值
                            $val = Convert::getPropertyValue($value, $key);

                            //空值时,从属性取值
                            if(is_null($val)){
                                if(property_exists($value, $key)){
                                    $value=$value->$key;
                                }
                            }else{
                                $value=$val;
                            }
                        }else if(is_array($value) && array_key_exists($key, $value)){
                            $value=$value[$key];
                        }
                    }
                }
            }

            //替换占位符(非空&&值类型||空字符串)
            if((!is_array($value) && !is_object($value))){
                //$value=str_replace("$", "\\\$", $value);
                //$view=preg_replace("/\\\$\{".$this->m_varPrefix.":".$param."\}/",$value,$view);//<原代码>

                //Modified@2019/2/12,修改页面无法输出美元符号的问题; 如果修改后出现其它BUG,则撤回此修改,使用<原代码>标签的语句
                $view=str_replace("\${".$this->m_varPrefix.":".$param."}",$value, $view);
            }
        }
        return $view;
    }

    /**
     * 预处理视图参数(把模板需要的参数另存到临时变量,为后面的变量反向匹配做准备)
     * @param string $view
     */
    protected function preLoadViewParams($view)
    {
        $pattern="/\\\${([^}]{1,})}/";
        $pattern="/\\\$\{".$this->m_varPrefix.":([^\}\s]{1,})\}/U";
        $matches=[];
        preg_match_all($pattern, $view,$matches);
        $params=[];
        if(count($matches)>0){
            foreach ($matches[1] as $param){
                $param=trim($param);
                if(!in_array($param, $params)){
                    $params[]=$param;
                }
            }
        }
        return $params;
    }

    /**
     * 预处理视图:混合视图的模板与部件,预处理标签信息
     * @param string $view           视图内容
     * @param string $relDir         视图相对目录
     * @param array  $taglibs        视图用到的标签解析库,键为前缀,值为命名空间
     * @param array  $customParams   视图通过标签实现的定制参数
     */
    protected function loadView($view,$relDir,&$taglibs=[],&$customParams=[])
    {
        //定制参数:<page:param name="key" value="value"/>
        //标签库:<taglib prefix="php" namespace="swiftphp\web\tags" />
        //模板标签:<page:template file="" />
        //部件标签:<page:part file="" />
        //占位标签:<page:contentHolder id="" />
        //内容标签:<page:content id="" />

        //读取页面定制参数标签:<page:param name="key" value="value"/>
        //定制参数只能在主视图
        $matches=[];
        $pattern="/<page:param[\s]{1,}name[\s]*=[\s]*[\"|\']([^\s<>\"\']{1,})[\"|\'][\s]{1,}value[\s]*=[\s]*[\"|\']([^\s<>\"\']{1,})[\"|\'][^>]*>/i";
        if(preg_match_all($pattern, $view,$matches)>0){
            $holders=$matches[0];
            $keys=$matches[1];
            $values=$matches[2];
            for($i=0;$i<count($holders);$i++){
                $customParams[$keys[$i]]=$values[$i];
            }
        }
        //清空定制参数标签
        $view=preg_replace($pattern, "", $view);

        //模板标签:<page:template file="" />;一个视图最多只存在一个模板
        $view=$this->loadTemplate($view, $relDir,$taglibs);

        //部件标签:<page:part file="" />
        $view=$this->loadParts($view, $relDir);

        //<taglib prefix="php" namespace="swiftphp\web\tags" />
        //读取标签库后,清空标签库标签
        $view=$this->loadTagLibs($view, $taglibs);

        //标签预处理.单标签转为双标签;用占位符统一标记为通用的标签前缀
        $view=$this->preloadTags($view, $taglibs);

        //保存转义表达式
        $search="\\\${";
        $replace=uniqid();
        $view=str_replace($search, $replace, $view);

        //表达式前缀
        $pattern="/\\\$\{[\s]{0,}([^\}\s]{1,})[\s]{0,}\}/U";
        $view=preg_replace($pattern, "\${".$this->m_varPrefix.":\$1}", $view);

        //恢复转义表达式
        $search=$replace;
        $replace="\${";
        $view=str_replace($search, $replace, $view);

        return $view;
    }

    /**
     * 标签预处理.单标签转为双标签;用占位符统一标记为通用的标签前缀
     * @param string $view
     * @param string $taglibs
     * @return string
     */
    protected function preloadTags($view,$taglibs)
    {
        //标签预处理
        foreach (array_keys($this->m_taglibs) as $prefix){
            //单标签转为双标签
            $pattern="/<".$prefix.":([\\w]{1,})\s[^>]*\/>/isU";
            $matches=[];
            if(preg_match_all($pattern,$view,$matches,PREG_SET_ORDER)>0){
                foreach($matches as $match){
                    $html=$match[0];
                    $tag=$match[1];
                    $_html=trim(substr($html, 0,strrpos($html, "/>")))."></".$prefix.":".$tag.">";
                    $view=str_replace($html, $_html, $view);
                }
            }

            //统一处理为通用的标签前缀
            $pattern="/<".$prefix.":([\\w]{1,})\s[^>]*>/isU";
            $matches=[];
            if(preg_match_all($pattern,$view,$matches,PREG_SET_ORDER)>0){
                foreach($matches as $match){
                    $html=$match[0];
                    $tag=$match[1];

                    $search="/<".$prefix.":".$tag."/";
                    //$replace="<".$this->m_tagPlaceHolder." _tag=\"".$prefix.":".$tag."\" _id=\"".SecurityUtil::newGuid()."\"";
                    $replace="<".$this->m_tagPlaceHolder." _tag=\"".$prefix.":".$tag."\"";
                    $view=preg_replace($search, $replace, $view,1);

                    $search="</".$prefix.":".$tag.">";
                    $replace="</".$this->m_tagPlaceHolder.">";
                    $view=str_replace($search, $replace, $view);
                }
            }
        }
        return $view;
    }

    /**
     * 读取视图用到的标签解析库,并清空标签库标签
     * @param string $view           视图内容
     * @param array  $taglibs        视图用到的标签解析库,键为前缀,值为命名空间
     * @return mixed
     */
    protected function loadTagLibs($view,&$taglibs)
    {
        //<taglib prefix="php" namespace="swiftphp\web\tags" />
        //读取标签库后,清空标签库标签
        $pattern="/<taglib[^>]{1,}\/>/i";
        $matches=[];
        if(preg_match_all($pattern,$view,$matches,PREG_SET_ORDER)>0){
            foreach($matches as $match){
                $outHtml=$match[0];
                $view=str_replace($outHtml, "", $view);
                $attrs=HtmlHelper::getTagAttributes($match[0]);
                $libPrifix=trim($attrs["prefix"]);
                if(!array_key_exists($libPrifix, $taglibs)){
                    $taglibs[$libPrifix]=trim($attrs["namespace"]);
                }
            }
        }
        return $view;
    }

    /**
     * 合并到母板视图(一个视图最多只存在一个模板)
     * @param string $view      视图内容
     * @param string $relDir    视图所在目录
     * @param array  $taglibs   标签库
     * @throws \Exception
     */
    protected function loadTemplate($view,$relDir,&$taglibs)
    {
        //母板标签:<page:template file="" />;一个视图最多只存在一个母板
        $pattern="/<page:template[^>]{1,}file[\s]*=[\s]*[\"|\']([^\s<>\"\']{1,})[\"|\'][^>]*>/i";
        $matches=[];
        if(preg_match($pattern,$view,$matches)>0){
            //有母板的视图需要预先加载标签库
            $view=$this->loadTagLibs($view, $taglibs);

            //搜索文件
            $templateFile=$matches[1];
            $templateFile=$relDir."/".$templateFile;
            if(!file_exists($templateFile) || !is_file($templateFile)){
                throw new \Exception("Template file '".$matches[1]."' does not exist");
            }
            $template=file_get_contents($templateFile);
            $template=StringUtil::removeUtf8Bom($template);

            //合并母板里的部件
            $template=$this->loadParts($template, dirname($templateFile));

            //读取模板的标签库
            $template=$this->loadTagLibs($template, $taglibs);

            //合并母板到视图
            $view=$this->addTemplateToView($template,$view);
        }
        return $view;
    }

    /**
     * 合并部件到视图模板
     * @param string $view      视图内容
     * @param string $relDir    视图所在目录
     * @return mixed
     */
    protected function loadParts($view,$relDir)
    {
        //部件标签:<page:part file="" />
        $pattern="/<page:part[^>]{1,}file[\s]*=[\s]*[\"|\']([^\s<>\"\']{1,})[\"|\'][^>]*>/i";
        $matches=[];
        if(preg_match_all($pattern,$view,$matches)>0){
            $parts=$matches[0];
            $tpls=$matches[1];
            for($i=0;$i<count($parts);$i++){
                $part=$parts[$i];
                $tplFile=$tpls[$i];
                $tplFile=$relDir."/".$tplFile;

                //部件内容
                $tplHtml="";
                if(file_exists($tplFile) && is_file($tplFile)){
                    $tplHtml=file_get_contents($tplFile);
                }
                $tplHtml=StringUtil::removeUtf8Bom($tplHtml);
                //$tplHtml=$this->applyView($tplHtml);

                //递归合并部件模板的部件
                $dir=dirname($tplFile);
                $tplHtml=$this->loadParts($tplHtml, $dir);

                //合并部件到模板
                $view=str_replace($part, $tplHtml, $view);
            }
        }
        return $view;
    }

    /**
     * 把母板内容合并到视图
     * @param $template 模板内容
     * @param $view	视图内容
     * @return void
     */
    protected function addTemplateToView($template,$view)
    {
        $holders = $this->getTemplateContentHolders($template);
        $contents=$this->getViewContents($view);
        foreach(array_keys($contents) as $id){
            if(!empty($holders[$id])){
                $template=str_replace($holders[$id],trim($contents[$id]),$template);
                unset($holders[$id]);
            }
        }
        foreach($holders as $holder){
            $template=str_replace($holder,"",$template);
        }
        return $template;
    }

    /**
     * 取模板内容占位模板
     * 占位标签:<page:contentHolder id="" />
     * @param $template 模板内容
     * @return array
     */
    protected function getTemplateContentHolders($template)
    {
        $holders=[];
        $pattern="/<page:contentHolder[^>]{1,}id[\s]*=[\s]*[\"|\']([^\s]{1,})[\"|\'][^>]*(\/>|>[^>]*<\/php:contentHolder>)/i";
        $matches=[];
        if(preg_match_all($pattern,$template,$matches,PREG_SET_ORDER)>0){
            foreach($matches as $match){
                if(count($match)>=2){
                    $holders[$match[1]]=$match[0];
                }
            }
        }
        return $holders;
    }

    /**
     * 取得视图占位内容
     * 内容标签:<page:content id="" />
     * 注:内容控件不能这样写<page:content id="header" />
     * @param $view 视图
     * @return array
     */
    protected function getViewContents($view)
    {
        $contents=[];
        $pattern="/<page:content[^>]{1,}id[\s]*=[\s]*[\"|\']([^\s]{1,})[\"|\'][^>]*>(.*)<\/page:content>/isU";
        $matches=[];
        if(preg_match_all($pattern,$view,$matches,PREG_SET_ORDER)>0){
            foreach($matches as $match){
                //$match:0,所有内容;1,id;2,内部内容
                if(count($match)>=2){
                    $contents[$match[1]]=$match[2];
                }
            }
        }
        return $contents;
    }


    /**
     * 搜索模板文件
     */
    protected function searchView()
    {
        /*
         * 根目录: 相对于配置文件位置定义为根目录.
         * 搜索顺序: 区域根目录->根目录
         *1:以/起的路径:相对于根目录,不需要搜索.
         *2:不以/起的带路径:按搜索顺序.
         */

        //根目录: 相对于配置文件位置定义为根目录.
        $rootDir=rtrim($this->m_config->getBaseDir(),"/");

        //以/起的路径:相对于根目录,不需要搜索.
        if(strpos($this->m_viewFile, "/")===0){
            return $rootDir.$this->m_viewFile;
        }

        //区域,控制器,操作
        $areaDir=trim($this->m_controller->getAreaName(),"/");
        $controllerBaseName=get_class($this->m_controller);
        $controllerBaseName=substr($controllerBaseName,strrpos($controllerBaseName, "\\")+1);
        $controllerBaseName=substr($controllerBaseName, 0,strpos($controllerBaseName, "Controller"));
        $actionName=$this->m_controller->getActionName();

        //需要搜索文件
        $searchFiles=[];
        if(empty($this->m_viewFile)){
            //如果没有定义,则按{控制器}/{操作}搜索
            $searchFiles[]=$controllerBaseName."/".$actionName.".html";
            $searchFiles[]=$controllerBaseName."/".StringUtil::toUnderlineString($actionName).".html";
            $searchFiles[]=lcfirst($controllerBaseName)."/".$actionName.".html";
            $searchFiles[]=lcfirst($controllerBaseName)."/".StringUtil::toUnderlineString($actionName).".html";
            $searchFiles[]=StringUtil::toUnderlineString($controllerBaseName)."/".$actionName.".html";
            $searchFiles[]=StringUtil::toUnderlineString($controllerBaseName)."/".StringUtil::toUnderlineString($actionName).".html";
        }else if(strpos($this->m_viewFile, "/")===false){
            //不包含目录时,添加{控制器}作为目录
            $searchFiles[]=$controllerBaseName."/".$this->m_viewFile;
            $searchFiles[]=lcfirst($controllerBaseName)."/".$this->m_viewFile;
            $searchFiles[]=StringUtil::toUnderlineString($controllerBaseName)."/".$this->m_viewFile;
        }else{
            $searchFiles[]=$this->m_viewFile;
        }

        //搜索顺序
        foreach ($searchFiles as $file){
            if(!empty($areaDir)){
                $_file=$rootDir."/".$areaDir."/".$this->m_defaultViewDir."/".$file;
                if(file_exists($_file)){
                    return $_file;
                }
            }
            $_file=$rootDir."/".$this->m_defaultViewDir."/".$file;
            if(file_exists($_file)){
                return $_file;
            }
        }
    }

    /**
     * 获取参数值
     * @param array $paramValues
     * @param string $paramKey
     * @return mixed
     */
    private function getUIParams($inputValues, $paramKey)
    {
        if(array_key_exists($paramKey, $inputValues)){
            return $inputValues[$paramKey];
        }else if(strpos($paramKey, ".")>0){
            $keys=explode(".", $paramKey);
            $value=$this->getUIParams($inputValues,$keys[0]);//根据第一段取得对象或数组值
            //key1.key2.key3...
            for($i=1;$i<count($keys);$i++){
                $key=$keys[$i];
                //对象或数组
                if(is_object($value)){
                    //优先使用getter取值
                    $val = Convert::getPropertyValue($value, $key);

                    //空值时,从属性取值
                    if(is_null($val) && property_exists($value, $key)){
                        $value=$value->$key;
                    }else{
                        $value=$val;
                    }
                }else if(is_array($value)){
                    $key=$keys[$i];
                    $value=$value[$key];
                }
            }
            return $value;
        }
        return false;
    }
}