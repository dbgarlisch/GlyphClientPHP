<?php
namespace Pointwise;
require_once 'PwBlockStr.php';
require_once 'PwBlockUns.php';
require_once 'PwDomainStr.php';
require_once 'PwDomainUns.php';
require_once 'PwConnector.php';


class GlyphClient {
    private $debug_ = false;
    private $busy_ = false;
    private $auth_failed_;
    private $socket_;


    function __construct() {
        $this->in(__METHOD__, func_get_args());
        $this->busy_ = false;
        $this->auth_failed_ = false;
        $this->socket_ = false;
    }


    function __destruct() {
        $this->in(__METHOD__, func_get_args());
        $this->disconnect();
    }


    function connect($port=null, $auth=null, $host=null, $retries=null) {
        $this->in(__METHOD__, func_get_args());
        $this->resolveSetting($port, 'PWI_GLYPH_SERVER_PORT', 2807);
        $this->resolveSetting($auth, 'PWI_GLYPH_SERVER_AUTH', '');
        $this->resolveSetting($host, 'PWI_GLYPH_SERVER_HOST', 'localhost');
        $this->resolveSetting($retries, 'PWI_GLYPH_SERVER_RETRIES', 5);
        $host2 = gethostbyname($host);
        $this->debug("CONNECT(port=$port, auth='$auth', host='$host($host2)', ".
                     "retries='$retries')\n");
        $this->disconnect();
        $ret = false;
        $this->socket_ = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (false === $this->socket_) {
            // bad
            print "socket_create failed\n";
        }
        elseif (!socket_connect($this->socket_, $host2, $port)) {
            $this->disconnect();
            print "socket_connect(\$socket_, '$host2', $port) failed\n";
        }
        elseif (!$this->send('AUTH', $auth)) {
            $this->disconnect();
            print "AUTH failed\n";
        }
        elseif (!$this->recv($type, $payload)) {
            $this->disconnect();
            print "connect::recv failed\n";
        }
        elseif ('READY' != $type) {
            $this->auth_failed_ = ($type == 'AUTHFAIL');
            $this->busy_ = ($type == 'BUSY');
            $this->disconnect();
            print "Not READY ($type)\n";
        }
        else {
            $ret = true;
        }
        return $ret;
    }


    function cmd($c, $castTo=null) {
        $this->in(__METHOD__, func_get_args());
        $payload = null;
        if (false !== $this->socket_) {
            if (!$this->send('EVAL', $c)) {
                $this->disconnect();
                print "EVAL '$c' failed\n";
            }
            elseif (!$this->recv($type, $payload)) {
                $this->disconnect();
                print "cmd::recv failed\n";
            }
            elseif ('' == $type) {
                $this->disconnect();
            }
            elseif ('OK' != $type) {
                trigger_error("Glyph '$c' failed with $payload", E_USER_NOTICE);
                $payload = null;
            }
            elseif (null !== $castTo && null !== $payload) {
                $this->doCast($castTo, $payload);
            }
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


    function doCast($castTo, &$payload)
    {
        $this->in(__METHOD__, func_get_args());
        if ('[]' == substr($castTo, -2)) {
            $castTo = trim(substr($castTo, 0, -2));
            $isArray = true;
        }
        else {
            $isArray = false;
        }
        if (!GlyphClient::getCastFunc($castTo, $castFunc)) {
            // could not find a callable cast function
            if (is_callable("Pointwise\GlyphClient::unknownCast_", false, $castFunc)) {
                call_user_func($castFunc, $client, $castTo, $payload, $isArray);
            }
        }
        elseif ($isArray) {
            $this->arrayCast_($castFunc, $payload);
        }
        else {
            $payload = call_user_func($castFunc, $this, $payload);
        }
    }


    function isDebug()
    {
        return $this->debug_;
    }


    function debug($msg)
    {
        if ($this->debug_) {
            print '   | ' . $msg;
        }
    }


    function __toString()
    {
        return __CLASS__;
    }


    private static
    function getCastFunc($castTo, &$castFunc)
    {
        return is_callable("Pointwise\GlyphClient::{$castTo}Cast", false, $castFunc);
    }


    private
    function arrayCast_($castFunc, &$payload)
    {
        // tokenizes a Tcl list string allowing for nested {}.
        // for example,
        //   "word {two words} final"
        // is split into 3 tokens,
        //   "word", "two words", and "final"
        preg_match_all("/{([^{]+)}|(\S+)/", $payload, $matches, PREG_SET_ORDER);
        $payload = array();
        foreach ($matches as $match) {
            // $match is itself an array of 2 or 3 items.
            //   $match[0] = full match. e.g. "word" or "{two words}"
            //   $match[1] = empty or match sans {}. e.g. "two words"
            //   $match[2] = undefined or "word"
            // attempt to cast each matched token string using $castFunc
            // last item in match[] is the one we want to cast
            $tmp = call_user_func($castFunc, $this, $match[count($match) - 1]);
            if (null !== $tmp) {
                // cast worked - keep it
                $payload[] = $tmp;
            }
        }
    }


    private static
    function unknownCast_($castTo, &$payload, $isArray)
    {
        print "   | unknownCast_(castTo=$castTo, payload=$payload, isArray=$isArray)\n";
    }


    private static
    function pwentCast($client, $payload)
    {
        // expecting $payload == 'pw::EntType_n'
        $key = substr($payload, 6, 7);
        if (null === $client) {
            $payload = null;
        }
        elseif ('::pw::' !== substr($payload, 0, 6)) {
            $payload = null;
        }
        elseif ('BlockUn' == $key) {
            $payload = new PwBlockUns($client, $payload);
        }
        elseif ('BlockSt' == $key) {
            $payload = new PwBlockStr($client, $payload);
        }
        elseif ('DomainU' == $key) {
            $payload = new PwDomainUns($client, $payload);
        }
        elseif ('DomainS' == $key) {
            $payload = new PwDomainStr($client, $payload);
        }
        elseif ('Connect' == $key) {
            $payload = new PwConnector($client, $payload);
        }
        else {
            $payload = null;
        }
        return $payload;
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
    function send($type, $payload)
    {
        $this->in(__METHOD__, func_get_args());
        $data = sprintf('%-8s%s', $type, $payload);
        $len =  pack('N', strlen($data));
        $this->debug(sprintf("send: %u[%s]\n",
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
        $this->debug(sprintf("recv: %u[%s]\n", $len, $data));
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
        $this->debug("$method('" . implode("', '", $locArgs) . "')\n");
    }
}

?>