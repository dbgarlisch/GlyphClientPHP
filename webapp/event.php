<?php
include 'lib/GlyphClientPHP/GlyphClient.php';

// screen dump location.
define('DISPLAY_FILENAME_PHP', '/var/www/html/pointwise/webgui/display.png');
define('DISPLAY_FILENAME_GLF', 'W:/html/pointwise/webgui/display.png');
define('CNXN_PORT', 2807);
define('CNXN_AUTH', 'WebGui');
define('CNXN_HOST', '192.168.62.104');


function updateDisplayImage()
{
    //pw::Display saveImage ?-foreground fgOption? ?-background bgOption?
    //                      ?-dpi dpi? ?-size size? ?-format option? filename
    return Pointwise\Display::saveImage('-dpi', 72, '-format', 'PNG',
                                        DISPLAY_FILENAME_GLF);
}


function createCon($glf, $pt0, $pt1)
{
    $seg = $glf->doCast("pwent", Pointwise\SegmentSpline::create());
    $seg->addPoint("{ $pt0 }");
    $seg->addPoint("{ $pt1 }");
    $con = $glf->doCast("pwent", Pointwise\Connector::create());
    $con->addSegment($seg);
    $con->calculateDimension();
    return true;
}


function createMultiCon($glf, $pts, $closed = true)
{
    $ret = true;
    $cnt = count($pts) - 1;
    if ($closed && (1 < $cnt)) {
        $pts[] = $pts[0]; // make wrap back to first pt
        ++$cnt;
    }
    for ($ii=0; $ii < $cnt; ++$ii) {
        if (!createCon($glf, $pts[$ii], $pts[$ii + 1])) {
            $ret = false;
            break;
        }
    }
    return $ret;
}


function createCircle($glf, $center, $radius, $normal='0 0 1')
{
    $seg = $glf->doCast("pwent", Pointwise\SegmentCircle::create());
    $xyz = explode(' ', $center);
    $xyz[0] = doubleval($xyz[0]) + $radius;
    $pt = implode(' ', $xyz);
    $seg->addPoint("{ $pt }");
    $seg->addPoint("{ $center }");
    $seg->setEndAngle(360, '0 0 1');
    $con = $glf->doCast("pwent", Pointwise\Connector::create());
    $con->addSegment($seg);
    $con->calculateDimension();
    return true;
}


function createCube($glf)
{
    $shape = $glf->doCast("pwent", Pointwise\Shape::create());
    $shape->box('-width 5 -height 5 -length 5');
    //$shape setTransform [list 1 0 0 0 0 1 0 0 0 0 1 0 0 0 0 1]
    //$shape setPivot Base
    //$shape setSectionMinimum 0
    //$shape setSectionMaximum 360
    //$shape setSidesType Plane
    //$shape setBaseType Plane
    //$shape setTopType Plane
    //$shape setEnclosingEntities {}
    $models = $shape->createModels();
    foreach($models as $model) {
        $model->setRenderAttribute('FillMode', 'Shaded');
    }
    Pointwise\Entity::delete($shape);
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


function setShape($glf, $which)
{
    Pointwise\Application::reset('-keep [list Display]');
    //Pointwise\Display::resetView('-Z');
    //Pointwise\Display::resetRotationPoint();
    Pointwise\Application::markUndoLevel("{New Shape}");
    Pointwise\Application::clearModified();
    $ret = true;
    $mode = $glf->doCast("pwent", Pointwise\Application::begin('Create'));
    switch ($which) {
    case 'square':
        $ret = createMultiCon($glf,
            array('-0.5 -0.5 0', '-0.5 0.5 0', '0.5 0.5 0', '0.5 -0.5 0'));
        break;
    case 'circle':
        $ret = createCircle($glf, '0 0 0', 1.0);
        break;
    case 'triangle':
        $ret = createMultiCon($glf,
            array('-0.5 -0.5 0', '0.5 -0.5 0', '0.0 0.5 0'));
        break;
    case 'cube':
        $ret = createCube($glf);
        break;
    default:
        $ret = false;
        break;
    }
    if ($ret) {
        $mode->end();
        //Pointwise\Application::markUndoLevel("{Create 2 Point Connector}");
        Pointwise\Display::zoomToFit();
        Pointwise\Display::update();
        updateDisplayImage();
    }
    else {
        $mode->abort();
    }
    return $ret;
}


function SET_VIEW($which)
{
    $ret = true;
    switch ($which) {
    case '-X':
    case '+X':
    case '-Y':
    case '+Y':
    case '-Z':
    case '+Z':
        Pointwise\Display::resetView($which);
        Pointwise\Display::zoomToFit();
        break;
    case 'iso':
        Pointwise\Display::setCurrentView('[list {-2.5 1.717 1.166} {-0.307 0.848 0.0} {0.073 0.990 0.126} 115.162 17.844]');
        Pointwise\Display::zoomToFit();
        break;
    default:
        break;
    }
    updateDisplayImage();
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


function puts($msg)
{
    global $glf;
    //$msg = '\[' . $_SERVER['REMOTE_ADDR'] . '\]: ' . $msg;
    $glf->cmd('puts "\[' . $_SERVER['REMOTE_ADDR'] . '\]: ' . $msg . '"');
}


$glf = new Pointwise\GlyphClient();
$glf->setDebug($_POST['debugGlyph']);
if (!$glf->connect(CNXN_PORT, CNXN_AUTH, CNXN_HOST)) {
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


$ret = array();
$ret['ret'] = null;
//$ret['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
//$ret['_POST'] = print_r($_POST, true);
$ret['id'] = $_POST['id'];
switch($_POST['id']) {
case 'GET_VERSION':
    puts($_POST['id']);
    $ret['ret'] = Pointwise\Application::getVersion();
    break;
case 'SET_VIEW':
    puts($_POST['id'] . ' ' . $_POST['optSelected']);
    $ret['ret'] = SET_VIEW($_POST['optSelected']);
    break;
case 'IMAGE_FORMATS':
    puts($_POST['id']);
    $ret['ret'] = Pointwise\Display::getImageFormats();
    break;
case 'REFRESH':
    puts($_POST['id']);
    $ret['ret'] = updateDisplayImage();
    break;
case 'GET_VIEW':
    puts($_POST['id']);
    $ret['ret'] = Pointwise\Display::getCurrentView();
    break;
case 'SHAPE':
    puts($_POST['id'] . ' ' . $_POST['optSelected']);
    $ret['ret'] = setShape($glf, $_POST['optSelected']);
    break;
}
$ret['mtime'] = filemtime(DISPLAY_FILENAME_PHP);
$ret['glyph'] = $glf->getLog();

//$glf->disconnect();
unset($glf);

echo json_encode($ret);
?>