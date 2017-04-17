<?php
namespace Pointwise;
require_once 'Entity.php';


class GridEntity extends Entity {
}
GridEntity::setInstanceMethodRetType('int', 'getPointCount');
GridEntity::setInstanceMethodRetType('coord', 'closestCoordinate');
GridEntity::setInstanceMethodRetType('pwent', 'getAutomaticBoundaryCondition');

?>