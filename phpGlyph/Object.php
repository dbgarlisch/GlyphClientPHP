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
        // This is invoked anytime a string rep of the Object is needed.
        // When composing glyph commands, the auto command dispatching depends
        // on this to return the underlying glyph object function name.
        //print "   #   ". __CLASS__ ."\__toString::glfObj_(". $this->glfObj_ .")\n";
        return $this->glfObj_;
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


    static
    function argsToStr($args)
    {
        $argCnt = count($args);
        if (0 == $argCnt) {
            return '';
        }
        else if ((1 < $argCnt) || is_object($args[0])) {
            return ' '. GlyphClient::tclImplode($args);
        }
        // Return single arg without modification. Caller is responsible to make
        // the arg Tcl compliant. Multi word values must be // enclosed in {}.
        // For example, "-flag {multi word value}"
        return ' '. trim($args[0]);
    }


    function __call($funcName, $args)
    {
        // An undefined PHP object method was called somewhere in this object's
        // subclass hierarchy. Map the call to a glyph object and execute it.
        // If a subclass object needs to do special processing for an action,
        // the PHP subclass must implement the method. See subclass impl in
        // folder phpGlyph/CLASS.php. These subclasses are autoloaded by
        // GlyphClient::autoLoader($className).
        // For example The PHP call to
        //   $ent->getRenderAttribute('ColorMode')
        // maps to the glyph call
        //   ::pw::Connector_1 getRenderAttribute ColorMode

        // exec glyph object command:
        //   "$funcName[ arg ...]"
        return $this->cmd("$funcName". Object::argsToStr($args));
    }


    static
    function __callStatic($funcName, $args)
    {
        // An undefined PHP static method was called somewhere in this class'
        // subclass hierarchy. Map the call to a glyph class and execute it.
        // If a subclass needs to do special processing for an action, the
        // PHP subclass must implement the static method. See subclass impl in
        // directory "phpGlyph/CLASS.php". These subclasses are autoloaded by
        // GlyphClient::autoLoader($className).
        // For example The PHP call to
        //   Pointwise\Grid::getCount()
        // maps to the glyph call
        //   pw::Grid getCount

        // expecting:
        //   $funcName [$glfClient] [arg ...]
        $argCnt = count($args);
        if ((0 < $argCnt) && is_a($args[0], 'Pointwise\GlyphClient')) {
            // caller provided glfClient
            $glf = $args[0];
            // remove glfClient from args
            $args = array_slice($args, 1);
            --$argCnt;
        }
        elseif (!GlyphClient::getDefaultClient($glf)) {
            throw new \Exception("Default GlyphClient not defined");
        }

        $cls = get_called_class();
        if (0 != strncmp($cls, 'Pointwise\\', 10)) {
            throw new \Exception("Expected 'Pointwise\CLASS' got '$cls'");
        }
        else {
            // map PHP class to glyph object type
            //   Pointwise\CLASS --> pw::CLASS
            return $glf->cmd('pw::'. substr($cls, 10). " $funcName". Object::argsToStr($args));
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