<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once '../GlyphClient.php';
use Pointwise as pw; // make pw an alias for the Pointwise namespace


function main()
{
    $opts = getopt('da:p:h:r:s:',
                array('debug', 'auth::', 'port::', 'host::', 'retries::', 'retry_seconds::'));
    $debug = getOptFlag($opts, 'd', 'debug');
    $auth = getOptVal($opts, null, 'a', 'auth');
    $port = getOptVal($opts, null, 'p', 'port');
    $host = getOptVal($opts, 'localhost', 'h', 'host');
    $retries = getOptVal($opts, null, 'r', 'retries');
    $retrySeconds = getOptVal($opts, null, 's', 'retry_seconds');
    if ($debug) {
        print "OPTIONS\n";
        var_dump($opts);
        print "\n";
    }

    $glf = new pw\GlyphClient();
    $glf->setDebug($debug);

    if ($glf->connect($port, $auth, $host, $retries, $retrySeconds)) {
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
    print "Errors:\n";
    $glf->printErrors();
}


function runCommands_Application($glf)
{
    print "Application getVersion = " . pw\Application::getVersion() ."\n";
    print "Application getCAESolverNames = " . pw\Application::getCAESolverNames() ."\n";
    print "Application getCAESolver = " . pw\Application::getCAESolver() ."\n";
    print "Application setCAESolver 'ADS/Leo (structured)' = " . pw\Application::setCAESolver('{ADS/Leo (structured)}') ."\n";
    print "Application getCAESolver = " . pw\Application::getCAESolver() ."\n";
    print "Application undo = " . pw\Application::undo() ."\n";
    print "Application getCAESolver = " . pw\Application::getCAESolver() ."\n";
    print "Application getCAESolverDimension = " . pw\Application::getCAESolverDimension() ."\n";
    print "\n";
}


function runCommands_Database($glf)
{
    print "Database getCount = " . pw\Database::getCount() ."\n";
    print "Database getExtents = " . pw\Database::getExtents() ."\n";
    print "\n";
}


function runCommands($glf)
{
    //runCommands_Application($glf);
    //runCommands_Database($glf);

    print "Grid getCount = " . pw\Grid::getCount() ."\n";
    //print "Grid getAll -type pw::Block = ". pw\Grid::getAll('-type pw::Block') ."\n";
    //print "Grid getAll -type pw::Domain = ". pw\Grid::getAll('-type pw::Domain') ."\n";
    //print "Grid getAll -type pw::Connector = ". pw\Grid::getAll('-type pw::Connector') ."\n";
    //print "\n";
    foreach (pw\Grid::getAll() as $ent) {
        print "\n---------------------------------------------------\n";
        print "$ent equals $ent = " . $ent->equals($ent) ."\n";
        print "$ent getType = " . $ent->getType() ."\n";
        print "$ent isOfType pw::Connector = " . $ent->isOfType('pw::Connector') ."\n";
        print "$ent getname = " . $ent->getName() ."\n";
        print "$ent getColor = " . $ent->getColor() ."\n";
        print "$ent getLayer = " . $ent->getLayer() ."\n";
        print "$ent getExtents =\n";
        var_dump($ent->getExtents());
        print "$ent getTimeStamp = " . $ent->getTimeStamp() ."\n";
        print "$ent getGroups =\n";
        var_dump($ent->getGroups());

        $attrs = array('ColorMode', 'SecondaryColor', 'SecondaryColorMode',
            'PointMode', 'FillMode', 'LineMode', 'IsolineCount',
            'TriangleDensity', 'LineDensity', 'LineWidth');
        foreach ($attrs as $attr) {
            print "$ent getRenderAttribute $attr = " . $ent->getRenderAttribute($attr) ."\n";
        }

        print "$ent getIgnoreAllSources = " . $ent->getIgnoreAllSources() ."\n";
        print "$ent getSourceCalculationMethod = " . $ent->getSourceCalculationMethod() ."\n";
        print "$ent getPointCount = " . ($ptCnt = $ent->getPointCount()) ."\n";
        for ($ndx = 1; $ndx <= $ptCnt; ++$ndx) {
            print "  $ent getXYZ $ndx =\n";
            var_dump($ent->getXYZ($ndx));
        }
        print "$ent closestCoordinate =\n";
        var_dump($ent->closestCoordinate('{0 0 0}'));
        print "\n\n%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%\n\n"; break;
        print "$ent getVolumeCondition = " . $ent->getVolumeCondition() ."\n";
        print "$ent getDatabaseEntities = " . $ent->getDatabaseEntities() ."\n";
        print "$ent getExcludedSources = " . $ent->getExcludedSources() ."\n";
        print "$ent getSourcePointCount = " . $ent->getSourcePointCount() ."\n";
        print "$ent getOutOfSyncWithSources = " . $ent->getOutOfSyncWithSources() ."\n";
        print "$ent getOutOfSyncAttributes = " . $ent->getOutOfSyncAttributes() ."\n";
        print "$ent canReExtrude = " . $ent->canReExtrude() ."\n";
        //print "$ent getOversetObjectVisibility = " . $ent->getOversetObjectVisibility() ."\n";

        //print "$ent getDimension = " . $ent->getDimension() ."\n";
        print "$ent getDimensions = " . $ent->getDimensions() ."\n";
        print "$ent getCellCount = " . $ent->getCellCount() ."\n";
        if ($ent->isOfType('pw::Connector')) {
            print "$ent getSegmentCount = " . $ent->getSegmentCount() ."\n";
            print "$ent getSubConnectorCount = " . $ent->getSubConnectorCount() ."\n";
            print "$ent getSubConnectorDimension = " . $ent->getSubConnectorDimension() ."\n";
            print "$ent getTotalLength = " . $ent->getTotalLength() ."\n";
            print "$ent getAverageSpacing = " . $ent->getAverageSpacing() ."\n";
            $node = $glf->doCast("pwent", $ent->getNode('Begin'));
            print "$ent getNode Begin = $node\n";
            print "$node getXYZ =\n";
            var_dump($node->getXYZ());
            print "$node getPointCount =". $node->getPointCount() ."\n";
            print "$node getDimensions =\n";
            var_dump($node->getDimensions());
            print "$node getPoint =\n";
            var_dump($node->getPoint());
            print "$node getConnectors =\n";
            var_dump($node->getConnectors());
            print "\npw\Connector::getAdjacentConnectors($glf, $ent) = ". pw\Connector::getAdjacentConnectors($glf, $ent) ."\n";
        }
        else if (!$ent->isOfType('pw::Block')) {
            print "$ent getAutomaticBoundaryCondition = " . $ent->getAutomaticBoundaryCondition() ."\n";
            print "$ent getRegisterBoundaryConditions = " . $ent->getRegisterBoundaryConditions() ."\n";
            print "$ent getDefaultProjectDirection = " . $ent->getDefaultProjectDirection() ."\n";
        }
        break;
    }

    print "\nSTATIC ACTION TESTS\n";
    print "\n";
    print "Entity::getByName('dom-1') = " . pw\Entity::getByName('dom-1') ."\n";
    print "\n";
    print "GridEntity::getByName('dom-2') = " . pw\GridEntity::getByName('dom-2') ."\n";
    print "\n";

    print "\nCASTING\n";
    var_dump($glf->doCast('vec3', '1.5 2.6 3.7'));
    var_dump($glf->doCast('vec2', '1.2 2.8'));
    var_dump($glf->doCast('uv', '0.55 0.77'));
    var_dump($glf->doCast('idx3', '1 2 3'));
    var_dump($glf->doCast('idx2', '1 2'));
    $vals = array('1', 'yes', 'true', true, 1, '0', 'no', 'FAlse', false, 0, 'xxx');
    foreach ($vals as $v) {
        print "(bool)$v == ". ($glf->doCast('bool', $v) ? 'true' : 'false') ."\n";
    }

    print "\nTCL IMPLODE\n";
    var_dump(pw\GlyphClient::tclImplode(array('word', 'multi word', 'word2')));
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


function dumpArray($arr, $title='array')
{
    print "\n$title = {\n";
    foreach($arr as $v) {
        if (is_array($v)) {
            $v = '['. implode(', ', $v) .']';
        }
        print "   $v\n";
    }
    print "}\n";
}


main();
?>