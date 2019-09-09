<?php
namespace swiftphp\web\twig\filters;

/**
 * Twig格式化过滤器
 * @author Tomix
 *
 */
class NumberFormat
{
    /**
     * 格式化数字
     * @param double $value         值
     * @param integer $decimals     小数位
     * @param string $decimalpoint  小数点
     * @param string $separator     千分符号
     * @return string
     */
    public static function formatNumber($value,$decimals=0,$decimalpoint=".",$separator=","){
        $value=str_replace(",", "", $value);
        if(empty($value)){
            $value=0;
        }
        return number_format($value,$decimals,$decimalpoint,$separator);
    }
}

