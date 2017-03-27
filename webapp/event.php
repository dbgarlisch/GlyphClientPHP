<?php
include 'C:\Users\david\Documents\GitHub\GlyphClientPHP\GlyphClient.php';

// screen dump location.
$png = 'C:/Users/david/apps/xampp/htdocs/pointwise/display.png';

function updateDisplayImage()
{
    //pw::Display saveImage ?-foreground fgOption? ?-background bgOption?
    //                      ?-dpi dpi? ?-size size? ?-format option? filename
    global $png;
    return Pointwise\Display::saveImage('-dpi', 100, '-format', 'PNG', $png);
}

function createCon($pt0, $pt1)
{
    global $glf;
    $seg = $glf->doCast("pwent", Pointwise\SegmentSpline::create());
    $seg->addPoint($pt0);
    $seg->addPoint($pt1);
    $con = $glf->doCast("pwent", Pointwise\Connector::create());
    $con->addSegment($seg);
    $con->calculateDimension();
    return true;
}

function createMultiCon($pts, $closed = true)
{
    $ret = true;
    $cnt = count($pts) - 1;
    if ($closed && (1 < $cnt)) {
        $pts[] = $pts[0]; // make wrap back to first pt
        ++$cnt;
    }
    for ($ii=0; $ii < $cnt; ++$ii) {
        if (!createCon($pts[$ii], $pts[$ii + 1])) {
            $ret = false;
            break;
        }
    }
    return $ret;
}


function createCircle($center, $radius, $normal='0 0 1')
{
    global $glf;
    $seg = $glf->doCast("pwent", Pointwise\SegmentCircle::create());
    $xyz = explode(' ', $center);
    $xyz[0] = doubleval($xyz[0]) + $radius;
    $pt = implode(' ', $xyz);
    $seg->addPoint($pt);
    $seg->addPoint($center);
    $seg->setEndAngle(360, '0 0 1');
    $con = $glf->doCast("pwent", Pointwise\Connector::create());
    $con->addSegment($seg);
    $con->calculateDimension();
    return true;
}

/*
set mode [pw::Application begin Create]
    set edges [pw::Edge createFromConnectors [list $con]]
    set edge [lindex $edges 0]
    set sdom [pw::DomainStructured create]
    $sdom addEdge $edge
$mode end
set solver [pw::Application begin ExtrusionSolver [list $sdom]]
    $solver setKeepFailingStep true
    $sdom setExtrusionSolverAttribute NormalMarchingVector {-0 -0 -1}
    $sdom setExtrusionSolverAttribute NormalInitialStepSize 0.05
    $solver run 30
$solver end

*/

function setShape($which)
{
    global $png;
    global $glf;
    //$glf->setDebug(1);
    Pointwise\Application::reset();
    Pointwise\Display::resetView('-Z');
    Pointwise\Display::resetRotationPoint();
    Pointwise\Application::markUndoLevel("New Shape");
    Pointwise\Application::clearModified();
    $ret = true;
    $mode = $glf->doCast("pwent", Pointwise\Application::begin('Create'));
    switch ($which) {
    case 'square':
        $ret = createMultiCon(array('-0.5 -0.5 0', '-0.5 0.5 0',
                                    '0.5 0.5 0', '0.5 -0.5 0'));
        break;
    case 'circle':
        $ret = createCircle('0 0 0', 1.0);
        break;
    case 'triangle':
        $ret = createMultiCon(array('-0.5 -0.5 0', '0.5 -0.5 0', '0.0 0.5 0'));
        break;
    default:
        $ret = false;
        break;
    }
    if ($ret) {
        $mode->end();
        Pointwise\Application::markUndoLevel("Create 2 Point Connector");
        Pointwise\Display::zoomToFit();
        //Pointwise\Display::update();
        updateDisplayImage();
    }
    else {
        $mode->abort();
    }
    return $ret;
}


//function arrayToString($arr)
//{
//    $ret = array();
//    foreach ($arr as $key => $val) {
//        if (is_array($key)) {
//            $key = "[" + arrayToString($key) + "]";
//        }
//        if (is_array($val)) {
//            $val = "[" + arrayToString($val) + "]";
//        }
//        $ret[] = "$key=$val";
//    }
//    return join(",", $ret);
//}


$glf = new Pointwise\GlyphClient();
$glf->setDebug(0);
if (!$glf->connect()) {
    if ($glf->is_busy()) {
        echo "Pointwise is busy\n";
    }
    elseif ($glf->auth_failed()) {
        echo "Pointwise connection not authenticated\n";
    }
    else {
        echo "Pointwise connection failed\n";
    }
    return 0;
}
//var_dump($_POST);
//$w = $_POST['cmd'];

$ret = array();
$ret['ret'] = null;
//$ret['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
//$ret['_POST'] = print_r($_POST, true);
$ret['id'] = $_POST['id'];
switch($_POST['id']) {
case 'GET_VERSION':
    $ret['ret'] = Pointwise\Application::getVersion();
    break;
case 'SET_VIEW':
    Pointwise\Display::resetView($_POST['optSelected']);
    Pointwise\Display::zoomToFit();
    updateDisplayImage();
    $ret['ret'] = $_POST['optSelected'];
    break;
case 'IMAGE_FORMATS':
    $ret['ret'] = Pointwise\Display::getImageFormats();
    break;
case 'REFRESH':
    $ret['ret'] = updateDisplayImage();
    break;
case 'GET_VIEW':
    $ret['ret'] = Pointwise\Display::getCurrentView();
    break;
case 'SHAPE':
    $ret['ret'] = setShape($_POST['optSelected']);
    break;
}
$glf->disconnect();
$ret['mtime'] = filemtime($png);
echo json_encode($ret);
?>