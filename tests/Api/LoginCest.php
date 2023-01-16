<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Tests\ApiTester;
use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;

class LoginCest extends BaseCestAbstract
{
    public function _before(ApiTester $I): void
    {
        // Overwrite parents' before
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-CSRF-Token', 'test');
    }

    public function successfulUserLogin(ApiTester $I): void
    {
        $I->dontSeeAuthentication();
        $user = $I->have(User::class);
        $email = $user->getEmail();
        $I->grabEntityFromRepository(User::class, ['email' => $email]);

        $response = $I->request('/login', 'POST', parameters: [
            'username' => $email,
            'password' => Fixtures::get('plainPassword'),
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeAuthentication();
        $I->seeUserHasRole('ROLE_USER');
        $I->seeResponseContains([
            'data' => [
                'token' => 'string',
                'user' => [
                    'roles' => 'array',
                ],
            ],
        ]);
    }

    public function credentialsMissMatch(ApiTester $I): void
    {
        $I->dontSeeAuthentication();
        $user = $I->have(User::class);
        $email = $user->getEmail();
        $I->grabEntityFromRepository(User::class, ['email' => $email]);

        $response = $I->request('/login', 'POST', parameters: [
            'username' => $email,
            'password' => 'notexistingpassword',
        ]);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->dontSeeAuthentication();
    }
}
