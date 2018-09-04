<?php

namespace WSSC\Contracts;

/**
 *
 * @author Arthur Kushman
 */
interface ConnectionContract
{

    public function send(string $data);

    public function close();

    public function getUniqueSocketId(): int;

    public function getPeerName(): string;

    public function broadCast(string $data);
}
