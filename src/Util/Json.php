<?php
declare(strict_types=1);

namespace CommentsService\Util;

class Json
{
    /**
     * @throws \JsonException
     */
    public static function decode(string $data): array
    {
        return \json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     */
    public static function encode(array $data): string
    {
        return \json_encode($data, JSON_THROW_ON_ERROR);
    }
}
