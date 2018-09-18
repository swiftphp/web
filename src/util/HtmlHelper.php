<?php
namespace swiftphp\web\util;

use swiftphp\common\util\ObjectUtil;

/**
 * HTML相关帮助类
 * @author Tomix
 *
 */
class HtmlHelper
{
    /**
     * 读取标签属性
     * @param string $outerHtml
     * @return mixed[]
     */
    public static function getTagAttributes($outerHtml="")
    {
        $arr_array=[];
        $outerHtml=substr($outerHtml,0,strpos($outerHtml,">")+1);
        $pattern="/\s[a-zA-Z0-9_]{1,}=\"[^\"]{0,}\"/";
        $matches =[];
        preg_match_all($pattern,$outerHtml,$matches,PREG_SET_ORDER);
        foreach($matches as $match){
            $att=$match[0];
            $pos=strpos($att,"=");
            $key=trim(substr($att,0,$pos));
            $value=substr($att,$pos+1);
            $value=str_replace("\"","",$value);
            $arr_array[$key]=$value;
        }
        return $arr_array;
    }

    /**
     * 获取标签内部内容
     * @param string $outerHtml
     * @param string $tagName
     * @return string
     */
    public static function getTagInnerHtml($outerHtml,$tagName)
    {
        $pattern="/<".$tagName." [^>]*>(.*)<\/".$tagName.">/s";
        $matches=[];
        if(preg_match($pattern, $outerHtml,$matches)){
            return $matches[1];
        }
        return "";
    }

    /**
     * 获取参数值,多维参数用点号(.)分隔
     * @param array|object $inputParam 输入参数
     * @param string $paramKey         参数键
     * @param mixed  $paramValue       引用输出参数值
     * @return boolean
     */
    public static function getUIParams($inputParam, $paramKey,&$paramValue)
    {
        if(is_object($inputParam)){
            return self::getUIParamsFromObject($inputParam, $paramKey,$paramValue);
        }else if(is_array($inputParam)){
            return self::getUIParamsFromArray($inputParam,$paramKey,$paramValue);
        }
        return false;
    }

    /**
     * 从对象属性取值
     * @param object $inputParam
     * @param string $paramKey
     * @param mixed  $paramValue       引用输出参数值
     * @return boolean
     */
    private static function getUIParamsFromObject($inputParam, $paramKey,&$paramValue)
    {
        //分段取参数:key1.key2.key3...
        if(strpos($paramKey, ".")>0){
            $keys=explode(".", $paramKey);
            $value=null;
            $hasValue=self::getUIParamsFromObject($inputParam,$keys[0],$value);//根据第一段取得对象或数组值
            if($hasValue){
                unset($keys[0]);
                return self::getUIParams($value, implode(".", $keys),$paramValue);
            }
        }else{
            //getter取值
            $getter=ObjectUtil::getGetter($inputParam, $paramKey);
            if($getter!=null){
                $paramValue = $inputParam->$getter();
                return true;
            }

            //直接从属性取
            if(property_exists($inputParam, $paramKey)){
                $paramValue = $inputParam->$paramKey;
                return true;
            }
        }

        //匹配不到参数,返回false
        return false;
    }

    /**
     * 从对象属性取值
     * @param array $inputParam
     * @param string $paramKey
     * @param mixed  $paramValue       引用输出参数值
     * @return boolean
     */
    private static function getUIParamsFromArray($inputParam, $paramKey,&$paramValue)
    {
        if(array_key_exists($paramKey, $inputParam)){
            $paramValue=$inputParam[$paramKey];
            return true;
        }

        //分段取参数:key1.key2.key3...
        if(strpos($paramKey, ".")>0){
            $keys=explode(".", $paramKey);
            $value=null;
            $hasValue=self::getUIParamsFromArray($inputParam,$keys[0],$value);//根据第一段取得对象或数组值
            if($hasValue){
                unset($keys[0]);
                return self::getUIParams($value, implode(".", $keys),$paramValue);
            }
        }

        return false;
    }
}

