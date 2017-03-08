<?php
namespace Pointwise;
require_once 'GridEntity.php';


class Connector extends GridEntity {
}

function Connector($glf, $action)
{
    //echo __METHOD__ ."($glf, $action)\n";
    $args = array_slice(func_get_args(), 2);
    //var_dump($args);
    //echo "### pw::Connector $action {". implode('} {', $args) ."}\n";
    return (0 == count($args)) ? $glf->cmd("pw::Connector $action", $castTo)
        : $glf->cmd("pw::Connector $action {". implode('} {', $args) . '}');
}
?>