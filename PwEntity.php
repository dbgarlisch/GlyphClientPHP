<?php
namespace Pointwise;
require_once 'PwObject.php';


class PwEntity extends PwObject {

    function __toString()
    {
        return __CLASS__ ."::". parent::__toString();
    }
}

?>