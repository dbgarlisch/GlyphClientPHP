<?php
namespace Pointwise;
require_once 'PwEntity.php';


class PwGridEntity extends PwEntity {

    function __toString()
    {
        return __CLASS__ ."::". parent::__toString();
    }
}

?>