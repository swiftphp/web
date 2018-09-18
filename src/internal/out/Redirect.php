<?php
namespace swiftphp\web\internal\out;

/**
 * 重定向
 * @author Tomix
 *
 */
class Redirect extends Base
{
    /**
     * 构造
     * @param mixed $content
     */
    public function __construct($url="")
    {
        parent::__construct($url);
    }

    /**
     *
     * {@inheritDoc}
     * @see \swiftphp\core\web\out\Base::output()
     */
    public function output()
    {
        if(headers_sent()){
            echo "<meta http-equiv=\"refresh\" content=\"0;url=".$this->m_content."\">\r\n";
        }else{
            header("Location: ".$this->m_content);
        }
    }
}

