<?php
namespace swiftphp\web\internal\tags;

/**
 * 格式化数字
 * @author Tomix
 *
 */
class DateTimeFormat extends TagBase
{
    /**
     * 时间戳
     * @var int
     */
    private $m_time=-1;

    /**
     * 时间日期字符串
     * @var string
     */
    private $m_string;

    /**
     * DateTime对象
     * @var \DateTime
     */
    private $m_object;

    /**
     * 格式
     * @var integer
     */
    private $m_format="Y-m-d H:i:s";

    /**
     * 时间戳
     * @param int $value
     */
    public function setTime($value)
    {
        $this->m_time=$value;
    }

    /**
     * 时间日期字符串
     * @param string $value
     */
    public function setString($value)
    {
        $this->m_string=$value;
    }

    /**
     * \DateTime对象
     * @param \DateTime $value
     */
    public function setObject($value)
    {
        if(!empty($value)&& $value instanceof \DateTime){
            $this->m_object=$value;
        }
    }

    /**
     * 输出格式
     * @param string $value
     */
    public function setFormat($value="Y-m-d H:i:s")
    {
        $this->m_format=$value;
    }

    /**
     * 获取标签渲染后的内容
     */
    public function getContent(&$outputParams=[])
    {
        $time=null;
        if($this->m_time>0){
            $time=$this->m_time;
        }else if(!empty($this->m_string)){
            $time=strtotime($this->m_string);
        }else if(!is_null($this->m_object)){
            $time=$this->m_object->getTimestamp();
        }
        if(!empty($time)){
            return date($this->m_format,$time);
        }
        return "";
    }
}