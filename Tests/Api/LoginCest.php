<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Service\Helper\TestHelper;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

class LoginCest extends BaseCestAbstract
{
    public function successfulUserLogin(ApiTester $I): void
    {
        $I->dontSeeAuthentication();

        $I->request('/login', 'POST', parameters: [
            'username' => TestHelper::USER_EMAIL,
            'password' => TestHelper::USER_PLAIN_PASSWORD,
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
        /** @var User $user */
        $user = $I->have(User::class);
        $I->addToRemove($user);
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
