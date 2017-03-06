<?php
namespace Pointwise;
require_once 'PwGridEntity.php';


class PwBlockUns extends PwGridEntity {

    function __toString()
    {
        return __CLASS__ ."::". parent::__toString();
    }
}

?>