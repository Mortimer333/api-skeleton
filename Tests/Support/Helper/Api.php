<?php

declare(strict_types=1);

namespace App\Tests\Support\Helper;

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
        $uri = ltrim($uri, '/');
        if ('GET' == $method && !empty($parameters)) {
            $query = '';
            foreach ($parameters as $name => $value) {
                if (strlen($query) > 0) {
                    $query .= '&';
                }
                $query .= $name . '=' . urlencode(is_array($value) ? json_encode($value) : $value);
            }

            if (false === strpos($query, '?')) {
                $uri .= '?' . $query;
            } else {
                $uri .= '&' . $query;
            }

            $parameters = [];
        }

        return $this->getModule('Symfony')->_request(
            $method,
            '/_/' . $uri,
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
        $this->seeResponseContainsString($needle, true);
    }

    public function getRandomPassword(): string
    {
        return FakerBase::lexify('????')
            . FakerBase::numerify('####')
            . FakerBase::toUpper(FakerBase::lexify('????'))
            . '@';
    }

    public function stubRemoteService(string $name, object $service): void
    {
        /** @var \Codeception\Module\Symfony $sfModule */
        $sfModule = $this->getModule('Symfony');

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $sfModule->_getContainer();

        if ($container->initialized($name)) {
            $sfModule->unpersistService($name);
            //            return;
        }

        $container->set($name, $service);
        $sfModule->persistService($name);
    }
}
