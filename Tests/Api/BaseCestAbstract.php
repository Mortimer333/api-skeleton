<?php

// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

namespace App\Tests\Api;

use App\Contract\LoginInterface;
use App\Contract\SuperUserInterface;
use App\Entity\User;
use App\Service\Helper\TestHelper;
use App\Service\JWSService;
use App\Service\User\UserManageService;
use App\Service\Util\HttpUtilService;
use App\Tests\Support\ApiTester;
use Codeception\Util\Fixtures;

abstract class BaseCestAbstract
{
    protected string $logged;

    /** @var array<int, object> $toRemove */
    protected array $toRemove = [];

    public function _before(ApiTester $I): void
    {
        $I->clearToRemove();
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-CSRF-Token', 'test');
        $I->setCookie('CSRF-Token', 'test');

        $httpUtilService = $I->getService(HttpUtilService::class);
        $httpUtilService->clearErrors();

        if (!$this instanceof LoginInterface) {
            return;
        }

        if (!Fixtures::exists('loginData')) {
            $this->setLoginData($I);
        } else {
            $loginData = Fixtures::get('loginData');
            $created = (int) $loginData['created'];
            $exp = $httpUtilService->getTokenExpTimeSeconds();
            if ($created + $exp < time()) {
                $this->setLoginData($I);
            }
        }

        if ($this instanceof SuperUserInterface) {
            $this->loginUser($I, 'super');
        } else {
            $this->loginUser($I, 'normal');
        }
    }

    public function _after(ApiTester $I): void
    {
        $I->removeSavedEntities();
    }

    protected function logout(ApiTester $I): void
    {
        $I->logout();
        $I->haveHttpHeader('Authorization', '');
    }

    protected function loginUser(ApiTester $I, string $type): void
    {
        if (!Fixtures::exists('loginData')) {
            $I->fail('Fixture to login are not loaded yet');
        }

        $this->logged = $type;

        $loginData = Fixtures::get('loginData');
        ['entity' => $entity, 'token' => $token, 'role' => $role] = $loginData[$type];
        $I->amLoggedInAs($entity);
        $I->seeUserHasRole($role);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
    }

    /**
     * @return array{entity: User, token: string, role: array<string>}|null
     */
    protected function getLoggedUser(): ?array
    {
        $loginData = Fixtures::get('loginData');

        return $loginData[$this->logged] ?? null;
    }

    /**
     * @return array{0: array<string, mixed>, 1: User}
     */
    protected function createNewUserAndLogin(ApiTester $I): array
    {
        $userManageService = $I->getService(UserManageService::class);
        $userArr = $I->getUserArray();
        $user = $userManageService->create($userArr);
        $I->addToRemove($user);
        $I->amLoggedInAs($user);
        $jws = $I->getService(JWSService::class);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $jws->createToken($user));

        return [
            $userArr,
            $user,
        ];
    }

    private function setLoginData(ApiTester $I): void
    {
        $jws = $I->getService(JWSService::class);
        $user = $I->grabEntityFromRepository(User::class, ['email' => TestHelper::USER_EMAIL]);
        $superUser = $I->grabEntityFromRepository(User::class, ['email' => TestHelper::SUPER_USER_EMAIL]);

        Fixtures::add('loginData', [
            'normal' => [
                'entity' => $user,
                'token' => $jws->createToken($user),
                'role' => 'ROLE_USER',
            ],
            'super' => [
                'entity' => $superUser,
                'token' => $jws->createToken($superUser),
                'role' => 'ROLE_SUPER_USER',
            ],
            'created' => time(),
        ]);
    }
}
