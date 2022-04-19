<?php

namespace iggyvolz\websocket;

use iggyvolz\pslhttp\AfterMessageHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class WebsocketHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly AfterMessageHandler $afterMessageHandler,
    )
    {
    }

    private static function getAcceptKey(string $websocketKey): string
    {
        return base64_encode(hash("sha1", "{$websocketKey}258EAFA5-E914-47DA-95CA-C5AB0DC85B11"));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if($request->getHeaderLine("Upgrade") !== "websocket" || $request->getHeaderLine("Connection") !== "Upgrade" || $request->getHeaderLine("Sec-Websocket-Version") !== "13") {
            return $this->responseFactory->createResponse(400);
        }
        $websocketKey = $request->getHeaderLine("Sec-Websocket-Key");
        return new WebsocketResponse($this->responseFactory->createResponse(101)->withHeader("Upgrade", "websocket")->withHeader("Connection", "Upgrade")->withHeader("Sec-Websocket-Accept", self::getAcceptKey($websocketKey)), $this->afterMessageHandler);
    }
}