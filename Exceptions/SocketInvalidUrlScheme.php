<?php
namespace Exceptions;

use Exception;

class SocketInvalidUrlScheme extends Exception
{

    public function __construct()
    {
        parent::__construct("Only 'tcp://' urls are supported!");
    }

}