<?php

namespace WSSC\Contracts;

/**
 *
 * @author Arthur Kushman
 */
interface ConnectionContract
{
    public function send(string $data): void;

    public function close(): void;

    public function getUniqueSocketId(): int;

    public function getPeerName(): string;

    public function broadCast(string $data): void;

    public function broadCastMany(array $data, int $delay): void;
}
