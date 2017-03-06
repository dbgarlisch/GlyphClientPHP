<?php
namespace Pointwise;

class PwObject {
    protected $client_; // the controlling glyph client
    protected $glfObj_; // tcl/Glyph object function name

    function __construct($client, $obj) {
        $this->client_ = $client;
        $this->glfObj_ = $obj;
    }


    function __destruct() {
        $this->client_ = null;
        $this->glfObj_ = null;
    }


    function getGlyphObj()
    {
        return $this->glfObj_;
    }


    function getGlyphClient()
    {
        return $this->client_;
    }


    function __toString()
    {
        return __CLASS__ . "(obj=". $this->glfObj_ .")";
    }


    function cmd($cmd, $retType=null)
    {
        return $this->client_->cmd($this->glfObj_ . ' ' . $cmd, $retType);
    }


    function __get($name)
    {
        echo "Getting '$name'\n";
        return null;
    }


    function __set($name, $value)
    {
        echo "Setting '$name' to '$value'\n";
    }


    function __call($name, $args)
    {
        return $this->cmd("$name ". implode(' ', $args));
    }


    static
    function __callStatic($name, $args)
    {
        echo "Calling static method '$name' ". implode(', ', $args) ."\n";
    }


    function __debugInfo()
    {
        // This feature was added in PHP 5.6.0
        $ret['glfObj_'] = $this->glfObj_;
        return $ret;
    }
}

?>