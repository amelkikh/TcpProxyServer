<?php
namespace Protocol;

use Evenement\EventEmitterInterface;

interface TransportInterface extends EventEmitterInterface
{
    public function sendString($string);
}