<?php

// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

namespace App\Tests\Api;

use App\Contract\SuperUserInterface;
use App\Entity\User;
use App\Service\Helper\TestHelper;
use App\Service\JWSService;
use App\Service\Util\HttpUtilService;
use App\Tests\ApiTester;
use Codeception\Util\Fixtures;

abstract class BaseCestAbstract
{
    /** @var array<int, object> $toRemove */
    protected array $toRemove = [];

    public function _before(ApiTester $I): void
    {
        $httpUtilService = $I->getService(HttpUtilService::class);
        $httpUtilService->clearErrors();
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-CSRF-Token', 'test');
        $jws = $I->getService(JWSService::class);
        $token = null;
        $superToken = null;
        $user = $I->grabEntityFromRepository(User::class, ['email' => TestHelper::USER_EMAIL]);
        $superUser = $I->grabEntityFromRepository(User::class, ['email' => TestHelper::SUPER_USER_EMAIL]);
        if (Fixtures::exists('loginData')) {
            $data = Fixtures::get('loginData');
            // Refresh token
            if ($data['created'] + (60 * 4.5) > time()) {
                $token = $data['token'];
                $superToken = $data['superToken'];
            }
        }

        if (!$token) {
            $token = $jws->createToken($user);
            $superToken = $jws->createToken($superUser);
            Fixtures::add('loginData', [
                'token' => $token,
                'superToken' => $superToken,
                'user' => $user,
                'superUser' => $superUser,
                'created' => time(),
            ]);
        }

        if ($this instanceof SuperUserInterface) {
            $I->amLoggedInAs($superUser);
            $I->seeUserHasRole('ROLE_SUPER_USER');
            $I->haveHttpHeader('Authorization', 'Bearer ' . $superToken);
        } else {
            $I->amLoggedInAs($user);
            $I->seeUserHasRole('ROLE_USER');
            $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        }
    }

    public function _after(ApiTester $I): void
    {
        $I->removeSavedEntities($this->toRemove);
    }

    protected function addToRemove(object $entity): void
    {
        $this->toRemove[] = $entity;
    }
}
