<?php
namespace Pointwise;
require_once 'Object.php';


class Node extends Object {
}
Node::setInstanceMethodRetType('vec3',    'getXYZ');
Node::setInstanceMethodRetType('point',   'getPoint');
Node::setInstanceMethodRetType('pwent[]', 'getConnectors');
Node::setInstanceMethodRetType('int[]',   'getDimensions');
Node::setInstanceMethodRetType('int',     'getPointCount');

?>