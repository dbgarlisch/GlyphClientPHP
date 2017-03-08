<?php
namespace Pointwise;

class Object {
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
        return $this->glfObj_;
        //return __CLASS__ . "(obj=". $this->glfObj_ .")";
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


    function __call($funcName, $args)
    {
        return (0 == count($args)) ? $this->cmd("$funcName")
            : $this->cmd("$funcName {". implode('} {', $args) . '}');
    }


    static
    function __callStatic($funcName, $args)
    {
        // NEED the GlyphClient connection for this static action!!
        $bt = debug_backtrace();
        $cls = $bt[1]['class'];
        if (0 == strncmp($cls, 'Pointwise\\', 10)) {
            $cls = 'pw::' . substr($cls, 10);
            echo __METHOD__ ." $cls $funcName {". implode('} {', $args) ."}\n";
            return "$cls $funcName {". implode('} {', $args) ."}";
        }
        //var_dump($bt);
        //return $this->cmd("$funcName ". implode(' ', $args));
        return __METHOD__ .'UNKNOWN';
    }


    function __debugInfo()
    {
        // __debugInfo was added in PHP 5.6.0
        $ret['glfObj_'] = $this->glfObj_;
        return $ret;
    }
}

?>