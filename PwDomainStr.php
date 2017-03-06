<?php
namespace Pointwise;
require_once 'PwGridEntity.php';


class PwDomainStr extends PwGridEntity {

    function __toString()
    {
        return __CLASS__ ."::". parent::__toString();
    }
}

?>