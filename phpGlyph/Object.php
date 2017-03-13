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
        $argCnt = count($args);
        if ((0 < $argCnt) && is_a($args[0], 'Pointwise\GlyphClient')) {
            $glf = $args[0];
            $args = array_slice($args, 1);
            --$argCnt;
        }
        elseif (!GlyphClient::getDefaultClient($glf)) {
            throw new \Exception("Default GlyphClient not defined");
        }

        $cls = get_called_class();
        if (0 != strncmp($cls, 'Pointwise\\', 10)) {
            // bad
        }
        else {
            $cls = 'pw::' . substr($cls, 10);
            $cmd = "$cls $funcName ". GlyphClient::tclImplode($args);
            //$cmd = "$cls $funcName". (0 == $argCnt ? "" : ' '.GlyphClient::tclImplode($args));
            //echo __METHOD__ ."# $cmd\n";
            return $glf->cmd($cmd);
        }
        throw new \Exception("Unexpected class '$cls'");
    }


    function __debugInfo()
    {
        // __debugInfo was added in PHP 5.6.0
        $ret['glfObj_'] = $this->glfObj_;
        return $ret;
    }
}

?>