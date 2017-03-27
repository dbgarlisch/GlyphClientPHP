<?php
namespace Pointwise;

class GlyphClient {
    private static $defaultClient_ = null;
    private $debug_ = false;
    private $busy_ = false;
    private $auth_failed_;
    private $socket_;
    private $errors_;


    function __construct() {
        $this->in(__METHOD__, func_get_args());
        if (null === self::$defaultClient_) {
            self::$defaultClient_ = $this;
        }
        $this->busy_ = false;
        $this->auth_failed_ = false;
        $this->socket_ = false;
        $this->clearErrors();
        spl_autoload_register(array(__NAMESPACE__ .'\\GlyphClient', 'autoLoader'));
    }


    function __destruct() {
        $this->in(__METHOD__, func_get_args());
        $this->disconnect();
    }


    function connect($port=null, $auth=null, $host=null, $retries=null, $retrySeconds=null) {
        $this->in(__METHOD__, func_get_args());
        $this->resolveSetting($port, 'PWI_GLYPH_SERVER_PORT', 2807);
        $this->resolveSetting($auth, 'PWI_GLYPH_SERVER_AUTH', '');
        $this->resolveSetting($host, 'PWI_GLYPH_SERVER_HOST', 'localhost');
        $this->resolveSetting($retries, 'PWI_GLYPH_SERVER_RETRIES', 5);
        $this->resolveSetting($retrySeconds, 'PWI_GLYPH_SERVER_RETRYDELAY', 0.1); // seconds
        $host2 = gethostbyname($host);
        $this->debug("CONNECT(port=$port, auth='$auth', host='$host($host2)', ".
                     "retries='$retries')");
        $this->disconnect();
        $this->clearErrors();
        $ret = false;
        if (!$this->doConnect($host2, $port, $retries, $retrySeconds)) {
            $this->disconnect();
            $this->pushError("socket_connect(\$socket_, '$host2', $port) failed");
        }
        elseif (!$this->send('AUTH', $auth)) {
            $this->disconnect();
            $this->pushError("AUTH failed");
        }
        elseif (!$this->recv($type, $payload)) {
            $this->disconnect();
            $this->pushError("connect::recv failed");
        }
        elseif ('READY' != $type) {
            $this->auth_failed_ = ($type == 'AUTHFAIL');
            $this->busy_ = ($type == 'BUSY');
            $this->disconnect();
            $this->pushError("Not READY ($type)");
        }
        else {
            $ret = true;
        }
        return $ret;
    }


    function cmd($c, $castTo=null) {
        $this->in(__METHOD__, func_get_args());
        $this->clearErrors();
        $payload = null;
        if (false === $this->socket_) {
            throw new \Exception("The client is not connected to a Glyph Server for command '$c'");
        }
        elseif (!$this->send('EVAL', $c)) {
            $this->disconnect();
            throw new \Exception("Could not send command '$c'");
        }
        elseif (!$this->recv($type, $payload)) {
            $this->disconnect();
            throw new \Exception("No response from the Glyph Server for command '$c'");
        }
        elseif ('OK' != $type) {
            throw new \Exception("Command '$c' failed with:\n$payload");
            $payload = null;
        }
        elseif (null !== $castTo && null !== $payload) {
            $payload = $this->doCast($castTo, $payload);
        }
        return $payload;
    }


    function disconnect() {
        $this->in(__METHOD__, func_get_args());
        if (false !== $this->socket_) {
            socket_close($this->socket_);
            $this->socket_ = false;
        }
        return true;
    }


    function setDebug($enable = true) {
        return $this->debug_ = $enable;
    }


    function is_busy() {
        $this->in(__METHOD__, func_get_args());
        return $this->busy_;
    }


    function auth_failed() {
        $this->in(__METHOD__, func_get_args());
        return $this->auth_failed_;
    }


    function doCast($castTo, $payload)
    {
        $this->in(__METHOD__, func_get_args());
        $castTo = str_replace(' ', '', $castTo);
        if ('[]' == substr($castTo, -2)) {
            $castTo = trim(substr($castTo, 0, -2));
            $isArray = true;
        }
        else {
            $isArray = false;
        }
        $ret = null;
        if (!GlyphClient::getCastFunc($castTo, $castFunc)) {
            // could not find a callable cast function
            if (is_callable(__NAMESPACE__."\GlyphClient::unknownCast_", false, $castFunc)) {
                $ret = call_user_func($castFunc, $client, $castTo, $payload, $isArray);
            }
        }
        elseif ($isArray) {
            $ret = $this->arrayCast_($castFunc, $payload);
        }
        else {
            $ret = call_user_func($castFunc, $this, $payload);
        }
        if (null === $ret) {
            throw new \Exception("Invalid cast $castTo($payload)");
        }
        return $ret;
    }


    function isDebug()
    {
        return $this->debug_;
    }


    function debug($msg, $newline=true)
    {
        if ($this->debug_) {
            print '   | ' . $msg . ($newline ? "\n" : "");
        }
    }


    function hasErrors()
    {
        return 0 < count($this->errors_);
    }


    function printErrors()
    {
        if (0 < count($this->errors_)) {
            foreach ($this->errors_ as $msg) {
                print "$msg\n";
            }
        }
        else {
            print "No errors\n";
        }
    }


    function __toString()
    {
        return __CLASS__;
    }


    public static
    function tclImplode($arr)
    {
        //print "in ". __METHOD__ ."('". implode('|', $arr) ."')\n";
        // Convert $arr to a Tcl list string wrapping multiword values in {}
        $cnt = count($arr);
        for ($i = 0; $i < $cnt; ++$i) {
            //print "  ## '$i: $arr[$i]' pos=". strpos($arr[$i], ' ') ."\n";
            if (false !== strpos($arr[$i], ' ')) {
                $arr[$i] = '{'. $arr[$i] .'}';
            }
        }
        //print "  ## result ('". implode(' ', $arr) ."')\n";
        return implode(' ', $arr);
    }


    public static
    function tclExplode($tclListStr)
    {
        //print "in ". __METHOD__ ."('$tclListStr')\n";
        // tokenizes a Tcl list string allowing for nested {}.
        // for example,
        //   "word {two words} final"
        // is split into 3 tokens,
        //   "word", "two words", and "final"
        // TODO: Does not handle escaped { } chars
        preg_match_all("/{([^{]+)}|(\S+)/", $tclListStr, $matches, PREG_SET_ORDER);
        $ret = array();
        foreach ($matches as $match) {
            // $match is itself an array of 2 or 3 items.
            //   $match[0] = full match. e.g. "word" or "{two words}"
            //   $match[1] = empty or match sans {}. e.g. "two words"
            //   $match[2] = undefined or "word"
            // last item in match[] is the one we want
            $ret[] = $match[count($match) - 1];
        }
        //var_dump($ret);
        return $ret;
    }


    public static
    function getDefaultClient(&$glf) {
        $glf = self::$defaultClient_;
        return null !== $glf;
    }


    private static
    function autoLoader($className) {
        //print "in ". __METHOD__ ."('$className')\n";
        //debug_print_backtrace();
        if (0 == strncmp($className, __NAMESPACE__.'\\', 10)) {
            $inc = realpath(__DIR__.'/phpGlyph/'. substr($className, 10) .'.php');
            if (file_exists($inc)) {
                //print __METHOD__ ."('$inc')\n";
                require_once $inc;
            }
        }
    }


    private static
    function getCastFunc($castTo, &$castFunc)
    {
        return is_callable(__NAMESPACE__."\GlyphClient::{$castTo}Cast", false, $castFunc);
    }


    private
    function arrayCast_($castFunc, $payload)
    {
        // $payload is a Tcl list as a string
        $ret = array();
        foreach (GlyphClient::tclExplode($payload) as $item) {
            $tmp = call_user_func($castFunc, $this, $item);
            if (null === $tmp) {
                // cast failed - fatal
                $ret = null;
                break;
            }
            $ret[] = $tmp;
        }
        return $ret;
    }


    private static
    function unknownCast_($castTo, $payload, $isArray)
    {
        print "   | unknownCast_(castTo=$castTo, payload=$payload, isArray=$isArray)\n";
        return null;
    }


    private static
    function pwentCast($client, $payload)
    {
        // expecting $payload == '::pw::EntType_n'
        if (null === $client) {
            $payload = null;
        }
        elseif ('::pw::' !== substr($payload, 0, 6)) {
            $payload = null;
        }
        else {
            // explode EntType_n
            list($cls, $id) = explode('_', substr($payload, 6), 2);
            $cls = __NAMESPACE__.'\\'.$cls;
            if (class_exists($cls)) {
                $payload = new $cls($client, $payload);
            }
            else {
                $payload = null;
            }
        }
        return $payload;
    }


    private static
    function numericNCaster($client, $payload, $cnt, $func)
    {
        //print "in ". __METHOD__."($client, '$payload', $cnt, $func)\n";
        // expecting "numeric numeric ... numeric" of length $cnt
        $ret = explode(' ', $payload);
        if ($cnt != count($ret)) {
            $ret = null;
        }
        else {
            for($i = 0; $i < $cnt; ++$i) {
                if (!is_numeric($ret[$i])) {
                    $ret = null;
                    break;
                }
                $ret[$i] = $func($ret[$i]);
            }
        }
        return $ret;
    }


    private static
    function vec3Cast($client, $payload)
    {
        // expecting "float float float"
        return GlyphClient::numericNCaster($client, $payload, 3, 'doubleval');
    }


    private static
    function vec2Cast($client, $payload)
    {
        // expecting "float float"
        return GlyphClient::numericNCaster($client, $payload, 2, 'doubleval');
    }


    private static
    function uvCast($client, $payload)
    {
        // expecting "float float"
        return GlyphClient::numericNCaster($client, $payload, 2, 'doubleval');
    }


    private static
    function idx3Cast($client, $payload)
    {
        // expecting "int int int"
        return GlyphClient::numericNCaster($client, $payload, 3, 'intval');
    }


    private static
    function idx2Cast($client, $payload)
    {
        // expecting "int int"
        return GlyphClient::numericNCaster($client, $payload, 2, 'intval');
    }


    private static
    function strCast($client, $payload)
    {
        return strval($payload);
    }


    private static
    function intCast($client, $payload)
    {
        return intval($payload);
    }


    private static
    function floatCast($client, $payload)
    {
        return floatval($payload);
    }


    private static
    function doubleCast($client, $payload)
    {
        return doubleval($payload);
    }


    private
    function resolveSetting(&$val, $envVar, $def)
    {
        $this->in(__METHOD__, func_get_args());
        if (null === $val) {
            // $val not set
            $val = getenv($envVar);
            if (false === $val) {
                $val = $def;
            }
        }
    }


    private
    function pushError($msg)
    {
        if (0 < strlen($msg)) {
            $this->errors_[] = $msg;
        }
    }


    private
    function clearErrors()
    {
        $this->errors_ = array();
    }


    private
    function doConnect($host, $port, $retries, $retrySeconds)
    {
        $ret = false;
        $this->socket_ = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (false === $this->socket_) {
            $this->pushError("socket_create failed");
        }
        else {
            $usecDelay = (int)($retrySeconds * 1000000.0);
            $ret = @socket_connect($this->socket_, $host, $port);
            while (!$ret && ($retries > 0) && ($usecDelay > 0)) {
                $this->debug("retry $retries (delay $retrySeconds sec, $usecDelay usec)");
                --$retries;
                usleep($usecDelay);
                $ret = @socket_connect($this->socket_, $host, $port);
            }
        }
        return $ret;
    }


    private
    function send($type, $payload)
    {
        $this->in(__METHOD__, func_get_args());
        $data = sprintf('%-8s%s', $type, $payload);
        $len =  pack('N', strlen($data));
        $this->debug(sprintf("send: %u[%s]",
            base_convert(bin2hex($len), 16, 10), $data));
        return (4 == socket_write($this->socket_, $len, 4)) &&
          (strlen($data) == socket_write($this->socket_, $data));
    }


    private
    function recv(&$type, &$payload)
    {
        $this->in(__METHOD__, func_get_args());
        $len = unpack('Nlen', socket_read($this->socket_, 4));
        $len = $len['len'];
        if ($len == 0) {
            return false;
        }
        $data = socket_read($this->socket_, $len);
        $this->debug(sprintf("recv: %u[%s]", $len, $data));
        if (strlen($data) < 8) {
            return false;
        }
        $type = trim(substr($data, 0, 8));
        $payload = substr($data, 8);
        return true;
    }


    private
    function in($method, $args)
    {
        if (!$this->debug_) {
            return;
        }
        $half = 30;
        $max = ($half * 2) + 5;
        $locArgs = array();
        foreach ($args as $arg) {
            if ($max < strlen($arg)) {
                $arg = substr($arg, 0, $half) . ' ... ' . substr($arg, -$half);
            }
            $locArgs[] = $arg;
        }
        $this->debug("$method('" . implode("', '", $locArgs) . "')");
    }
}

?>