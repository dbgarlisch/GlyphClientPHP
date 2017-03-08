<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once './GlyphClient.php';

function doTest($glf, $payload, $castTo)
{
    print "\n-------------------\n";
    print "castTo=$castTo payload='$payload'\n";
    $glf->doCast($castTo, $payload);
    var_dump($payload);
}

$glf = new Pointwise\GlyphClient();
print "\nTESTS:\n";
doTest($glf, 'Word {Multi Word} Word2', 'str');
doTest($glf, 'Word {Multi Word} Word2', 'str[]');
doTest($glf, '99', 'int');
doTest($glf, '11 22 33', 'int[]');
doTest($glf, '7.8900', 'float');
doTest($glf, '1.10 2.20 3.30', 'float[]');
doTest($glf, '997.8900', 'double');
doTest($glf, '991.10 992.20 993.30', 'double[]');
print "\nEND TESTS\n\n";

?>