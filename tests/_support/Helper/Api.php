<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use Codeception\Exception\ExternalUrlException;
use Codeception\Exception\ModuleException;
use Codeception\Util\JsonType;
use Faker\Provider\Base as FakerBase;
use PHPUnit\Framework\ExpectationFailedException;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{

    /**
     * @throws ExternalUrlException|ModuleException
     */
    public function request(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $files = [],
        array $server = [],
    ): string {
        $parameters['CSRF-Token'] = 'test';

        return $this->getModule('Symfony')->_request(
            $method,
            '/_/' . ltrim($uri, '/'),
            [],
            $files,
            $server,
            json_encode($parameters)
        );
    }

    public function seeResponseContains(array $conditions, bool $match = true): void
    {
        $content = $this->getModule('Symfony')->_getResponseContent();
        $jsonType = new JsonType(json_decode($content, true), $match);
        $result = $jsonType->matches($conditions);
        if (is_string($result)) {
            throw new ExpectationFailedException($result);
        }
    }

    public function dontSeeResponseContains(array $conditions): void
    {
        $this->seeResponseContains($conditions, false);
    }

    public function seeResponseContainsString(string $needle, bool $reverse = false): void
    {
        $content = $this->getModule('Symfony')->_getResponseContent();
        // We have to bring content and needle to the same grounds which is easly done by json_decode and json_encode
        $content = json_encode(json_decode($content, true));
        $needle = json_decode(json_encode($needle), true);
        $contains = str_contains($content, $needle);

        if (!$contains && !$reverse) {
            throw new ExpectationFailedException('Needle `' . $needle . '` not found in `' . $content . '`');
        }

        if ($contains && $reverse) {
            throw new ExpectationFailedException('Needle `' . $needle . '` was found in `' . $content . '`');
        }
    }

    public function dontSeeResponseContainsString(string $needle): void
    {
        $this->seeResponseContainsString($needle, false);
    }

    public function getRandomPassword(): string
    {
        return FakerBase::lexify('????')
        . FakerBase::numerify('####')
        . FakerBase::toUpper(FakerBase::lexify('????'))
        . '@';
    }
}
