<?php
namespace swiftphp\web\twig;

use swiftphp\web\IView;
use swiftphp\http\Context;
use swiftphp\web\IController;
use swiftphp\http\IOutput;
use Twig\Loader\FilesystemLoader;
use swiftphp\config\IConfigurable;
use swiftphp\config\IConfiguration;
use Twig\Environment;
use swiftphp\common\util\StringUtil;
use swiftphp\io\File;
use swiftphp\io\Path;
use Twig\TwigFilter;
use JSMin\JSMin;
use Twig\TwigFunction;
use swiftphp\web\twig\functions\Pagination;
use swiftphp\web\twig\filters\NumberFormat;

class View implements IView, IOutput,IConfigurable
{
    /**
     * 默认扩展过滤器
     */
    private const DEFAULT_EXT_FILTERS=[
        "formatNumber"=>NumberFormat::class."::formatNumber",
    ];
    
    /**
     * 默认扩展函数
     */
    private const DEFAULT_EXT_FUNCTIONS=[
        "pagination"=>Pagination::class."::pagination",
    ];
    
    /**
     * 是否调试模式
     * @var boolean
     */
    private $m_debug=false;

    /**
     * 默认模板基本目录
     * @var string
     */
    private $m_defaultViewDir="views";

    /**
     * 控制器实例
     * @var IController
     */
    private $m_controller=null;

    /**
     * 配置实例
     * @var IConfiguration
     */
    private $m_config=null;
    
    /**
     * 上下文
     * @var Context
     */
    private $m_context=null;

    /**
     * 模板文件
     * @var string
     */
    private $m_viewFile;

    /**
     * 输出参数
     * @var array
     */
    private $m_params=[];

    /**
     * 运行时缓存
     * @var string
     */
    private $m_runtimeDir;

    /**
     * 模板目录(当前视图所在的目录自动识别,无需添加)
     * @var array
     */
    private $m_paths=[];

    /**
     * 支持文件名牛后缀
     * @var array
     */
    private $m_extends=["php","html","twig"];
    
    /**
     * Twig过滤器
     * @var array
     */
    private $m_filters=[];
    
    /**
     * Twig函数
     * @var array
     */
    private $m_functions=[];

    /**
     *
     * {@inheritDoc}
     * @see \swiftphp\config\IConfigurable::setConfiguration()
     */
    public function setConfiguration(IConfiguration $value){
        $this->m_config=$value;
    }

    /**
     * 默认模板基本目录
     * @param string $value
     */
    public function setDefaultViewDir($value){
        $this->m_defaultViewDir=$value;
    }

    /**
     * 设置上下文对象
     * @param Context $context
     */
    public function setContext(Context $value){
        $this->m_context=$value;
    }

    /**
     * 设置模板参数
     * @param array $params
     */
    public function setViewParams($params=[]){
        $this->m_params=array_merge($this->m_params,$params);
    }

    /**
     * 设置标签参数
     * @param array $params
     */
    public function setTagParams($params=[]){
        $this->m_params=array_merge($this->m_params,$params);
    }

    /**
     * 设置模板文件
     * @param string $viewFile
     */
    public function setViewFile($viewFile=""){
        $this->m_viewFile=$viewFile;
    }

    /**
     * 设置是否调试模式
     * @param bool $value
     */
    public function setDebug($value){
        $this->m_debug=$value;
    }

    /**
     * 设置控制器
     * @param IController $controller
     */
    public function setController(IController $controller){
        $this->m_controller=$controller;
    }

    /**
     * 设置运行时缓冲目录
     * @param string $value
     */
    public function setRuntimeDir($value){
        $this->m_runtimeDir=$value;
    }

    /**
     * 设置模板目录(当前视图所在的目录自动识别,无需添加)
     * @param array $value
     */
    public function setPaths(array $value){
        $this->m_paths=$value;
    }

    /**
     * 设置支持的文件扩展
     * @param array $value
     */
    public function setExtends(array $value){
        $this->m_extends=$value;
    }
    
    /**
     * 设置Twig过滤器扩展
     * @param array $value
     */
    public function setFilters(array $value){
        $this->m_filters=$value;
    }
    
    /**
     * 设置Twig函数扩展
     * @param array $value
     */
    public function setFunctions(array $value){
        $this->m_functions=$value;
    }

    /**
     * 获取视图被渲染后的完整输出内容
     */
    public function getContent(){
        //template file
        $viewFile=$this->searchView();
        $path=Path::getDirName($viewFile);
        $file=Path::getFileBaseName($viewFile);

        //twig路径
        $paths=$this->m_paths;
        if(!in_array($path, $paths)){
            $paths[]=$path;
        }

        //当前默认基本目录
        $rootDir=$this->getAbsoluteDefaultViewDir();
        if(!in_array($rootDir, $paths)){
            $paths[]=$rootDir;
        }
        $areaDir=$rootDir."/".trim($this->m_controller->getAreaName(),"/");
        if(!in_array($areaDir, $paths)){
            $paths[]=$areaDir;
        }

        //twig配置
        $loader = new FilesystemLoader($paths);
        $twig=new Environment($loader,["debug"=>$this->m_debug,
            "cache"=>(!$this->m_debug && !empty($this->m_runtimeDir))?$this->m_runtimeDir:false,
        ]);
        $this->addExtFunctions($twig);        
        
        //twig过滤器扩展
        $filter=array_merge(self::DEFAULT_EXT_FILTERS,$this->m_filters);
        foreach ($filter as $name=>$value){
            $twig->addFilter(new TwigFilter($name,$value));
        }
        
        //twig函数扩展
        $functions=array_merge(self::DEFAULT_EXT_FUNCTIONS,$this->m_functions);
        foreach ($functions as $name=>$value){
            $twig->addFunction(new TwigFunction($name,$value));
        }

        $template = $twig->load($file);

        //return
        return $template->render($this->m_params);
    }

    /**
     * 输出方法
     */
    public function output(){
        //获取渲染后的文档
        $content=$this->getContent();
                
        //非调试模式时,压缩输出
        if(!$this->m_debug){
            $contentType=$this->m_context->getResponse()->getContentType();
            if($contentType=="application/x-javascript"){
                $content=JSMin::minify($content);
                //$content=JShrink::minify($content);
            }else if($contentType=="text/css"){
                $content=\Minify_CSSmin::minify($content);
            }else {
                $content=$this->compressHtml($content);
                //$content = \Minify_HTML::minify($content);
            }
        }
        
        //输出
        echo $content;
    }
    
    /**
     * 添加扩展函数
     * @param Environment $twig
     */
    private function addExtFunctions(Environment $twig){
        //静态成员访问
        $func=new TwigFunction("static",function($propertyOrMethod,...$params){
            $info=explode("::", $propertyOrMethod);
            $class=$info[0];
            $member=$info[1];
            if(StringUtil::startWith($member, "$")){
                $property=substr($member, 1);
                if(property_exists($class, $property)){
                    return $class::$$property;
                }
                return null;
            }else if(method_exists($class, $member)){
                $method=$class."::".$member;
                return call_user_func_array($method,$params);
            }            
            return null;
        });
        $twig->addFunction($func);
    }

    /**
     * 搜索模板文件
     */
    private function searchView()
    {
        /*
         * 根目录: 相对于配置文件位置定义为根目录.
         * 搜索顺序: 区域根目录->根目录
         *1:以/起的路径:相对于根目录,不需要搜索.
         *2:不以/起的带路径:按搜索顺序.
         */

        //根目录: 相对于配置文件位置定义为根目录.
        $baseDir=rtrim($this->m_config->getBaseDir(),"/");

        //以/起的路径:相对于根目录,不需要搜索.
        if(strpos($this->m_viewFile, "/")===0){
            return $baseDir.$this->m_viewFile;
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
            $searchFiles[]=$controllerBaseName."/".$actionName;
            $searchFiles[]=$controllerBaseName."/".StringUtil::toUnderlineString($actionName);
            $searchFiles[]=lcfirst($controllerBaseName)."/".$actionName;
            $searchFiles[]=lcfirst($controllerBaseName)."/".StringUtil::toUnderlineString($actionName);
            $searchFiles[]=StringUtil::toUnderlineString($controllerBaseName)."/".$actionName;
            $searchFiles[]=StringUtil::toUnderlineString($controllerBaseName)."/".StringUtil::toUnderlineString($actionName);
        }else if(strpos($this->m_viewFile, "/")===false){
            //不包含目录时,添加{控制器}作为目录
            $searchFiles[]=$controllerBaseName."/".$this->m_viewFile;
            $searchFiles[]=lcfirst($controllerBaseName)."/".$this->m_viewFile;
            $searchFiles[]=StringUtil::toUnderlineString($controllerBaseName)."/".$this->m_viewFile;
        }else{
            $searchFiles[]=$this->m_viewFile;
        }

        //去掉重复值,并添加后缀名
        $_searchFiles=array_unique($searchFiles);
        $searchFiles=[];
        foreach ($_searchFiles as $fn){
            $ext=Path::getFileExtName($fn);
            if(empty($ext)){
                foreach ($this->m_extends as $_ext){
                    $searchFiles[]=$fn.".".$_ext;
                }
            }else{
                $searchFiles[]=$fn;
            }
        }

        //搜索顺序
        $rootDir=$this->getAbsoluteDefaultViewDir();
        foreach ($searchFiles as $file){
            if(!empty($areaDir)){
                $_file=$rootDir."/".$areaDir."/".$file;
                if(file_exists($_file)){
                    return $_file;
                }
            }
            $_file=$rootDir."/".$file;
            if(file_exists($_file)){
                return $_file;
            }
        }
    }

    /**
     * 默认视图目录(绝对位置)
     */
    private function getAbsoluteDefaultViewDir(){
        //视图配置目录
        $viewDir=$this->m_defaultViewDir;
        if(empty($viewDir)){
            $viewDir="views";
        }
        $viewDir=trim($viewDir,"/");

        //返回绝对目录
        $baseDir=rtrim($this->m_config->getBaseDir(),"/");
        return $baseDir."/".$viewDir;
    }
    
    
    /**
     * 压缩html(测试版)
     * @param unknown $html_source
     * @return string|mixed
     */
    private function compressHtml($html_source)
    {
        $chunks   = preg_split('/(<!--<nocompress>-->.*?<!--<\/nocompress>-->|<nocompress>.*?<\/nocompress>|<pre.*?\/pre>|<textarea.*?\/textarea>|<script.*?\/script>)/msi', $html_source, -1, PREG_SPLIT_DELIM_CAPTURE);
        $compress = '';
        foreach ($chunks as $c) {
            if (strtolower(substr($c, 0, 19)) == '<!--<nocompress>-->') {
                $c        = substr($c, 19, strlen($c) - 19 - 20);
                $compress .= $c;
                continue;
            } elseif (strtolower(substr($c, 0, 12)) == '<nocompress>') {
                $c        = substr($c, 12, strlen($c) - 12 - 13);
                $compress .= $c;
                continue;
            } elseif (strtolower(substr($c, 0, 4)) == '<pre' || strtolower(substr($c, 0, 9)) == '<textarea') {
                $compress .= $c;
                continue;
            } elseif (strtolower(substr($c, 0, 7)) == '<script' && strpos($c, '//') != false && (strpos($c, "\r") !== false || strpos($c, "\n") !== false)) { // JS代码，包含“//”注释的，单行代码不处理
                $tmps = preg_split('/(\r|\n)/ms', $c, -1, PREG_SPLIT_NO_EMPTY);
                $c    = '';
                foreach ($tmps as $tmp) {
                    if (strpos($tmp, '//') !== false) { // 对含有“//”的行做处理
                        if (substr(trim($tmp), 0, 2) == '//') { // 开头是“//”的就是注释
                            continue;
                        }
                        $chars   = preg_split('//', $tmp, -1, PREG_SPLIT_NO_EMPTY);
                        $is_quot = $is_apos = false;
                        foreach ($chars as $key => $char) {
                            if ($char == '"' && !$is_apos && $key > 0 && $chars[$key - 1] != '\\') {
                                $is_quot = !$is_quot;
                            } elseif ($char == '\'' && !$is_quot && $key > 0 && $chars[$key - 1] != '\\') {
                                $is_apos = !$is_apos;
                            } elseif ($char == '/' && $chars[$key + 1] == '/' && !$is_quot && !$is_apos) {
                                $tmp = substr($tmp, 0, $key); // 不是字符串内的就是注释
                                break;
                            }
                        }
                    }
                    $c .= $tmp;
                }
            }
            
            $c        = preg_replace('/[\\n\\r\\t]+/', ' ', $c); // 清除换行符，清除制表符
            $c        = preg_replace('/\\s{2,}/', ' ', $c); // 清除额外的空格
            $c        = preg_replace('/>\\s</', '> <', $c); // 清除标签间的空格
            $c        = preg_replace('/\\/\\*.*?\\*\\//i', '', $c); // 清除 CSS & JS 的注释
            $c        = preg_replace('/<!--[^!]*-->/', '', $c); // 清除 HTML 的注释
            $compress .= $c;
        }
        return $compress;
    }
}