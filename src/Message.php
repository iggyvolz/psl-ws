<?php

namespace iggyvolz\websocket;

use Psl\Network\StreamSocketInterface;
use Psr\Http\Message\StreamInterface;
use Stringable;

final class Message implements Stringable
{
    public function __construct(
        public readonly Opcode $opcode,
        public readonly bool $masked,
        public readonly string $payload,
    )
    {
    }
    public static function read(StreamSocketInterface $stream): self
    {
        /** @var list<Frame> $frames */
        $frames = [];
        do {
            $frames[] = Frame::read($stream);
        } while(!$frames[array_key_last($frames)]->fin);
        return new self($frames[0]->opcode, $frames[0]->masked, implode("", array_map(fn(Frame $f) => $f->payload, $frames)));
    }

    public function __toString(): string
    {
        // No sense for us to break up the message if we already have the entire message
        return new Frame(true, $this->opcode, $this->masked, $this->payload);
    }
}