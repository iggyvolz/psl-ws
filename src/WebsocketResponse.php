<?php

namespace iggyvolz\websocket;

use iggyvolz\pslhttp\AfterMessageHandler;
use Psl\Network\StreamSocketInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class WebsocketResponse implements ResponseInterface, AfterMessageHandler
{
    public function __construct(private readonly ResponseInterface $response, private readonly AfterMessageHandler $afterMessageHandler)
    {
    }

    public function handle(StreamSocketInterface $connection): void
    {
        $this->afterMessageHandler->handle($connection);
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion($version): ResponseInterface
    {
        return $this->response->withProtocolVersion($version);
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader($name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader($name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine($name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function withHeader($name, $value): ResponseInterface
    {
        return $this->response->withHeader($name, $value);
    }

    public function withAddedHeader($name, $value): ResponseInterface
    {
        return $this->response->withAddedHeader($name, $value);
    }

    public function withoutHeader($name): ResponseInterface
    {
        return $this->response->withoutHeader($name);
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        return $this->response->withBody($body);
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        return $this->response->withStatus($code, $reasonPhrase);
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }
}