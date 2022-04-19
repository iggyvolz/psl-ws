<?php

namespace iggyvolz\websocket;

use Psl\Network\StreamSocketInterface;
use Stringable;

final class Frame implements Stringable
{
    public function __construct(
        public readonly bool $fin,
        public readonly Opcode $opcode,
        public readonly bool $masked,
        public readonly string $payload,
    )
    {
    }

    private static function readByte(StreamSocketInterface $stream): int
    {
        return ord($stream->readFixedSize(1));
    }
    private static function readBytes(StreamSocketInterface $stream, int $nBytes): int
    {
        $val = 0;
        foreach (str_split($stream->readFixedSize($nBytes)) as $char) {
            $val <<= 8;
            $val |= ord($char);
        }
        return $val;
    }


    public static function read(StreamSocketInterface $stream): self
    {
        $firstByte = self::readByte($stream);
        $fin = $firstByte & 0b10000000;
        $opcode = Opcode::tryFrom($firstByte & 0b00001111);
        if(is_null($opcode)) {
            throw new InvalidFrameException();
        }
        $secondByte = self::readByte($stream);
        $payloadLength = ($secondByte & 0b01111111);
        $payloadLength = match ($payloadLength) {
            126 => self::readBytes($stream, 2),
            127 => self::readBytes($stream, 8),
            default => $payloadLength
        };
        $mask = (($secondByte & 0b10000000) !== 0) ? $stream->readFixedSize(4) : null;
        $payload = $stream->readFixedSize($payloadLength);
        if(!is_null($mask)) {
            $payload = self::mask($mask, $payload);
        }
        return new self($fin, $opcode, !is_null($mask), $payload);
    }

    private static function mask(string $mask, string $payload): string
    {
        $mask = array_map(ord(...), str_split($mask));
        $encoded = array_map(ord(...), str_split($payload));
        $decoded = array_map(fn(int $byte, int $which) => $byte ^ $mask[$which % 4], $encoded, array_keys($encoded));
        return implode("", array_map(chr(...), $decoded));
    }

    public function __toString(): string
    {
        $frame = [$this->opcode->value, strlen($this->payload)];
        if($this->fin) $frame[0] |= 0b10000000;
        if($frame[1] > 65535) {
            $num = $frame[1];
            $frame[1] = 127;
            $frame = array_merge($frame, array_fill(2, 8, 0));
            for($i = 9; $i > 1; $i--) {
                $frame[$i] = $num % 256;
                $num >>= 8;
            }
        } elseif($frame[1] > 125) {
            $num = $frame[1];
            $frame[1] = 126;
            $frame = array_merge($frame, array_fill(2, 2, 0));
            for($i = 3; $i > 1; $i--) {
                $frame[$i] = $num % 256;
                $num >>= 8;
            }
        }
        if($this->masked) {
            $mask = "";
            for($i = 0; $i < 4; $i++) $mask .= chr(random_int(0, 255));
            $payload = $mask . self::mask($mask, $this->payload);
        } else {
            $payload = $this->payload;
        }
        return implode("", array_map(chr(...), $frame)) . $payload;
    }
}