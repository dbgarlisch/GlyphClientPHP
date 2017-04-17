<?php
namespace Pointwise;
require_once 'Object.php';


class Entity extends Object {
}
Entity::setInstanceMethodRetType('vec3[]', 'getExtents');
Entity::setInstanceMethodRetType('bool', 'getEnabled');
Entity::setInstanceMethodRetType('int', 'getLayer');
Entity::setInstanceMethodRetType('pwent[]', 'getGroups');
Entity::setInstanceMethodRetType('pwent', 'getByName');

?>