<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once '../GlyphClient.php';


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

    $glf = new Pointwise\GlyphClient();
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


function runCommands($glf)
{
    print "Application getVersion = " . Pointwise\Application::getVersion() ."\n";
    print "Application getCAESolverNames = " . Pointwise\Application::getCAESolverNames() ."\n";
    print "Application getCAESolver = " . Pointwise\Application::getCAESolver() ."\n";
    print "Application setCAESolver 'ADS/Leo (structured)' = " . Pointwise\Application::setCAESolver('ADS/Leo (structured)') ."\n";
    print "Application getCAESolver = " . Pointwise\Application::getCAESolver() ."\n";
    print "Application undo = " . Pointwise\Application::undo() ."\n";
    print "Application getCAESolver = " . Pointwise\Application::getCAESolver() ."\n";
    print "\n";

    print "Database getCount = " . Pointwise\Database::getCount() ."\n";
    print "Database getExtents = " . Pointwise\Database::getExtents() ."\n";
    print "\n";

    print "Grid getCount = " . Pointwise\Grid::getCount() ."\n";
    print "Grid getAll -type pw::Block = ". Pointwise\Grid::getAll('-type',  'pw::Block') ."\n";
    print "Grid getAll -type pw::Domain = ". Pointwise\Grid::getAll('-type',  'pw::Domain') ."\n";
    print "Grid getAll -type pw::Connector = ". Pointwise\Grid::getAll('-type',  'pw::Connector') ."\n";
    print "Grid getAll = " . ($ents = Pointwise\Grid::getAll()) ."\n";
    print "\n";

    foreach ($glf->doCast("pwent[]", $ents) as $ent) {
        print "\n---------------------------------------------------\n";
        print "$ent equals $ent = " . $ent->equals($ent) ."\n";
        print "$ent getType = " . $ent->getType() ."\n";
        print "$ent isOfType pw::Connector = " . $ent->isOfType('pw::Connector') ."\n";
        print "$ent getname = " . $ent->getName() ."\n";
        print "$ent getColor = " . $ent->getColor() ."\n";
        print "$ent getLayer = " . $ent->getLayer() ."\n";
        print "$ent getExtents = " . ($xts = $ent->getExtents()) ."\n";
        $xts = $glf->doCast('vec3[]', $xts);
        var_dump($xts);
        print "$ent getTimeStamp = " . $ent->getTimeStamp() ."\n";
        print "$ent getGroups = {" . $ent->getGroups() ."}\n";
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
            print "  $ent getXYZ $ndx = {" . ($xyz = $ent->getXYZ($ndx)) ."}\n";
            $xyz = $glf->doCast('vec3', $xyz);
            var_dump($xyz);
        }
        //print "$ent closestCoordinate = " . $ent->closestCoordinate() ."\n";
        //print "$ent getAutomaticBoundaryCondition = " . $ent->getAutomaticBoundaryCondition() ."\n";
        //print "$ent getRegisterBoundaryConditions = " . $ent->getRegisterBoundaryConditions() ."\n";
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
            print "$ent getNode Begin = " . $ent->getNode('Begin') ."\n";
            print "\nPointwise\Connector::getAdjacentConnectors($ent) = ". Pointwise\Connector::getAdjacentConnectors($ent) ."\n";
        }
        if (!$ent->isOfType('pw::Block')) {
            print "$ent getDefaultProjectDirection = " . $ent->getDefaultProjectDirection() ."\n";
        }

        break;
    }

    print "\nSTATIC ACTION TESTS\n";
    print "\n";
    print "Entity::getByName('dom-1') = " . Pointwise\Entity::getByName('dom-1') ."\n";
    print "\n";
    print "GridEntity::getByName('dom-2') = " . Pointwise\GridEntity::getByName('dom-2') ."\n";
    print "\n";

    var_dump($glf->doCast('vec3', '1.5 2.6 3.7'));
    var_dump($glf->doCast('vec2', '1.2 2.8'));
    var_dump($glf->doCast('uv', '0.55 0.77'));
    var_dump($glf->doCast('idx3', '1 2 3'));
    var_dump($glf->doCast('idx2', '1 2'));
    var_dump(Pointwise\GlyphClient::tclImplode(array('word', 'multi word', 'word2')));
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