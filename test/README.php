<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once 'GlyphClient.php';

$glf = new Pointwise\GlyphClient();
if ($glf->connect()) {
    $result = $glf->cmd("pw::Application getVersion");
    print "Pointwise version is " . $result . "\n";
}
else if ($glf->is_busy()) {
    print "Pointwise is busy\n";
}
else if ($glf->auth_failed()) {
    print "Pointwise connection not authenticated\n";
}
else {
    print "Pointwise connection failed\n";
}
?>