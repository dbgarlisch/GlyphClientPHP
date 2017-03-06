<?php
namespace Pointwise;
require_once 'PwGridEntity.php';


class PwDomainUns extends PwGridEntity {

    function __toString()
    {
        return __CLASS__ ."::". parent::__toString();
    }
}

?>