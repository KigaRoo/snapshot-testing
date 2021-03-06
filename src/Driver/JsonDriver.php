<?php

declare(strict_types=1);

namespace KigaRoo\SnapshotTesting\Driver;

use KigaRoo\SnapshotTesting\Accessor;
use KigaRoo\SnapshotTesting\Driver;
use KigaRoo\SnapshotTesting\Exception\CantBeSerialized;
use KigaRoo\SnapshotTesting\Wildcard\Wildcard;
use PHPUnit\Framework\Assert;
use stdClass;
use const JSON_PRETTY_PRINT;
use const PHP_EOL;
use function is_string;
use function json_decode;
use function json_encode;

final class JsonDriver implements Driver
{
    public function serialize(string $json) : string
    {
        $data = $this->decode($json);

        return json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
    }

    public function extension() : string
    {
        return 'json';
    }

    /**
     * @param Wildcard[] $wildcards
     */
    public function match(string $expected, string $actual, array $wildcards = []) : void
    {
        $actualArray = $this->decode($actual);
        $this->assertFields($actualArray, $wildcards);

        $actualArray = $this->replaceFields($actualArray, $wildcards);
        $actual      = json_encode($actualArray);

        $expectedArray = $this->decode($expected);
        $expectedArray = $this->replaceFields($expectedArray, $wildcards);
        $expected      = json_encode($expectedArray);

        Assert::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * @param string|stdClass|Wildcard[] $data
     * @param Wildcard[]                 $wildcards
     */
    private function assertFields($data, array $wildcards) : void
    {
        if (is_string($data)) {
            return;
        }

        foreach ($wildcards as $wildcard) {
            (new Accessor())->assertFields($data, $wildcard);
        }
    }

    /**
     * @param string|stdClass|Wildcard[] $data
     * @param Wildcard[]                 $wildcards
     *
     * @return string|stdClass|mixed[]
     */
    private function replaceFields($data, array $wildcards)
    {
        if (is_string($data)) {
            return $data;
        }

        foreach ($wildcards as $wildcard) {
            (new Accessor())->replaceFields($data, $wildcard);
        }

        return $data;
    }

    /**
     * @return string|object|mixed[]
     *
     * @throws CantBeSerialized
     */
    private function decode(string $data)
    {
        $data = json_decode($data);

        if ($data === false) {
            throw new CantBeSerialized('Given string does not contain valid json.');
        }

        return $data;
    }
}
