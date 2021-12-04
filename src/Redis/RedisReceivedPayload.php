<?php

declare(strict_types=1);

namespace ServiceBus\Transport\Redis;

use ServiceBus\Transport\Common\Package\IncomingPackage;
use function ServiceBus\Common\jsonDecode;
use function ServiceBus\Common\uuid;

/**
 * @internal
 */
final class RedisReceivedPayload
{
    /**
     * @var string
     */
    private $jsonPayload;

    public function __construct(string $jsonPayload)
    {
        $this->jsonPayload = $jsonPayload;
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @psalm-return array{
     *     body:non-empty-string,
     *     messageId:non-empty-string,
     *     traceId:non-empty-string,
     *     headers:array<non-empty-string, int|float|string|null>
     * }
     */
    public function parse(): array
    {
        if (empty($this->jsonPayload))
        {
            throw new \LogicException('Received message payload cant be empty');
        }

        $decodedPayload = jsonDecode($this->jsonPayload);
        $messageBody    = (string) ($decodedPayload[0] ?? '');

        if ($messageBody === '')
        {
            throw new \LogicException('Received message body cant be empty');
        }

        $headers = [];

        if (\array_key_exists(1, $decodedPayload) && \is_array($decodedPayload[1]))
        {
            $headers = $decodedPayload[1];
        }

        return [
            'body'      => $messageBody,
            'messageId' => $this->extractUuidHeader(IncomingPackage::HEADER_MESSAGE_ID, $headers),
            'traceId'   => $this->extractUuidHeader(IncomingPackage::HEADER_TRACE_ID, headers: $headers),
            'headers'   => $headers,
        ];
    }

    /**
     * @psalm-param non-empty-string $key
     *
     * @psalm-return non-empty-string
     */
    private function extractUuidHeader(string $key, array &$headers): string
    {
        $value = (string) $headers[$key];

        unset($headers[$key]);

        if ($value !== '')
        {
            return $value;
        }

        return uuid();
    }
}
