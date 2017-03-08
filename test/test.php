<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once '../GlyphClient.php';

function main()
{
    $opts = getopt('da:p:h:r:',
                array('debug', 'auth::', 'port::', 'host::', 'retries::'));
    $debug = getOptFlag($opts, 'd', 'debug');
    $auth = getOptVal($opts, null, 'a', 'auth');
    $port = getOptVal($opts, null, 'p', 'port');
    $host = getOptVal($opts, 'localhost', 'h', 'host');
    $retries = getOptVal($opts, null, 'r', 'retries');
    if ($debug) {
        print "OPTIONS\n";
        var_dump($opts);
        print "\n";
    }

    $glf = new Pointwise\GlyphClient();
    $glf->setDebug($debug);

    if (doConnect($glf, $auth, $port, $host, $retries)) {
        runCommands($glf);
    }
    elseif ($glf->is_busy()) {
        print "Pointwise is busy\n";
    }
    elseif ($glf->auth_failed()) {
        print "Pointwise connection not authenticated\n";
    }
    else {
        print "Pointwise connection failed\n";
    }
}


function runCommands($glf)
{
    $cmd = "pw::Application getVersion";
    $result = $glf->cmd($cmd);
    print "\n$cmd = $result\n";

    //$cmd = "pw::Application getCAESolverNames";
    //$result = $glf->cmd($cmd, "str[]");
    //dumpArray($result, $cmd);

    $cmd = "pw::Grid getCount";
    $result = $glf->cmd($cmd, "int");
    print "\n$cmd = $result\n\n";

    // static actions are not auto-routed because we don't have a GlyphClient!
    // Would be nice to figure this out without having to add static methods
    // to every class in the hierarchy as method($glf, args).
    //
    // This experiment tries
    // where,
    //
    // Pointwise\CLASS::ACTION(args) invokes:
    //    Pointwise\Object::__callStatic(ACTION, $args)
    //
    // $glf->CLASS->ACTION(args) invokes the call chain:
    //    Pointwise\GlyphClient::__get('CLASS')  >returns $glf
    //    Pointwise\GlyphClient::__call('ACTION', args)
    print "Entity::getByName('dom-2A') = " . Pointwise\Entity::getByName('dom-2A') ."\n";
    $glf->Entity->getByName('dom-2B');

    //$cmd = "pw::Grid getAll -type pw::Block";
    $cmd = "pw::Grid getAll";
    $ents = $glf->cmd($cmd, "pwent[]");
    //dumpArray($ents, $cmd);
    foreach ($ents as $ent) {
        print "\n---------------------------------------------------\n";
        print "$ent getname = " . $ent->getName() ."\n";
        print "$ent equals $ent = " . $ent->equals($ent) ."\n";
        print "$ent getType = " . $ent->getType() ."\n";
        print "$ent isOfType pw::Connector = " . $ent->isOfType('pw::Connector') ."\n";
        // static actions not working yet :(
        //Pointwise\Connector::getAdjacentConnectors($ent);
        print "Pointwise\Connector getAdjacentConnectors $ent = " .
            Pointwise\Connector($glf, 'getAdjacentConnectors', $ent) ."\n";
        break;

        $result = $ent->getPointCount();
        print $ent->getGlyphObj() ." getPointCount = $result\n";
        $ptCnt = $result;

        $cmd = 'getXYZ ' . intval($ptCnt / 2);
        $result = $ent->cmd($cmd, 'double[]');
        dumpArray($result, $ent->getGlyphObj() .' '. $cmd);

        $result = $ent->getType();
        print $ent->getGlyphObj() ." getType = $result\n";

        $result = $ent->isOfType('pw::Block');
        print $ent->getGlyphObj() ." isOfType pw::Block = $result\n";

        $result = $ent->getColor();
        print $ent->getGlyphObj() ." getColor = $result\n";

        $result = $ent->getLayer();
        print $ent->getGlyphObj() ." getLayer = $result\n";

        $result = $ent->getExtents();
        print $ent->getGlyphObj() ." getExtents = $result\n";

        $result = $ent->getTimeStamp();
        print $ent->getGlyphObj() ." getTimeStamp = $result\n";

        $result = $ent->getRenderAttribute("ColorMode");
        print $ent->getGlyphObj() ." getRenderAttribute ColorMode = $result\n";

        $result = $ent->getRenderAttribute("TriangleDensity");
        print $ent->getGlyphObj() ." getRenderAttribute TriangleDensity = $result\n";

        $result = $ent->getGroups();
        print $ent->getGlyphObj() ." getGroups = $result\n";
    }
}

function doConnect($glfClient, $auth=null, $port=null, $host=null, $retries=null)
{
    // try connection using auth provided by caller
    $ret = $glfClient->connect($port, $auth, $host, $retries);
    if (!$ret && $glfClient->auth_failed() && null !== $auth) {
        // try connection using default auth
        $auth = null;
        $ret = $glfClient->connect($port, $auth, $host, $retries);
    }
    return $ret;
}


function getOptFlag($opts, $sKey, $lKey=null)
{
    return array_key_exists($sKey, $opts) || array_key_exists($lKey, $opts);
}


function getOptVal($opts, $defValue, $sKey, $lKey=null)
{
    if (array_key_exists($sKey, $opts)) {
        return $opts[$sKey];
    }
    if ((null !== $lKey) && array_key_exists($lKey, $opts)) {
        return $opts[$lKey];
    }
    return $defValue;
}


function dumpArray($arr, $title)
{
    print "\n$title = {\n";
    foreach($arr as $v) {
        print "   $v\n";
    }
    print "}\n";
}


main();
?>