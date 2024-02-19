<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Service\Helper\TestHelper;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;
use Faker\Factory;

class RegisterCest extends BaseCestAbstract
{
    /** @var array<string, string> $fake */
    protected array $fake = [];

    public function _before(ApiTester $I): void
    {
        parent::_before($I);
        $faker = Factory::create();
        $this->fake['firstName'] = $faker->firstName();
        $this->fake['lastName'] = $faker->lastName();
        $this->fake['username'] = $faker->email();
        $this->fake['passwordRepeat'] = $this->fake['password'] = $I->getRandomPassword();
    }

    public function successfulRegistration(ApiTester $I): void
    {
        $response = $this->register($I);
        $I->seeResponseCodeIs(HttpCode::OK);
        verify(count($response['errors']))->equals(0);
        $user = $I->grabEntityFromRepository(User::class, ['email' => $this->fake['username']]);
        $I->addToRemove($user);
    }

    public function invalidEmail(ApiTester $I): void
    {
        $response = $this->register($I, username: 'test.com');
        $I->seeResponseContainsString('Username is not a valid e-mail');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        verify(count($response['errors']))->equals(1);
    }

    public function emptyPasswordReturnsAllErrors(ApiTester $I): void
    {
        $response = $this->register($I, password: '');

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsString('Password is too short, it is required to have 12 characters');
        $I->seeResponseContainsString('Password is required to have uppercase characters');
        $I->seeResponseContainsString('Password is required to have lowercase characters');
        $I->seeResponseContainsString('Password is required to have numeric characters');
        $I->seeResponseContainsString('Password is required to have special characters');
        $I->seeResponseContainsString('Password is too weak');
        verify(count($response['errors']))->equals(6, 'Response contains 6 errors');
    }

    public function passwordIsMissingUpperCaseLetterError(ApiTester $I): void
    {
        $response = $this->register($I, password: 'password123@');

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsString('Password is required to have uppercase characters');
        $I->seeResponseContainsString('Password is too weak');
        verify(count($response['errors']))->equals(2, 'Response contains 2 errors');
    }

    public function passwordIsMissingLowerCaseLetterError(ApiTester $I): void
    {
        $response = $this->register($I, password: 'PASSWORD123@');

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsString('Password is required to have lowercase characters');
        $I->seeResponseContainsString('Password is too weak');
        verify(count($response['errors']))->equals(2, 'Response contains 2 errors');
    }

    public function passwordIsMissingNumberError(ApiTester $I): void
    {
        $response = $this->register($I, password: 'PasswordBIG@');

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsString('Password is required to have numeric characters');
        $I->seeResponseContainsString('Password is too weak');
        verify(count($response['errors']))->equals(2, 'Response contains 2 errors');
    }

    public function passwordIsMissingSpecialCharacterError(ApiTester $I): void
    {
        $response = $this->register($I, password: 'Password1234');

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsString('Password is required to have special characters');
        $I->seeResponseContainsString('Password is too weak');
        verify(count($response['errors']))->equals(2, 'Response contains 2 errors');
    }

    public function passwordsMissMatchError(ApiTester $I): void
    {
        $response = $this->register($I, password: 'Password123@', passwordRepeat: 'Password12@4');

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsString("Passwords don't match");
        verify(count($response['errors']))->equals(1, 'Response contains 1 errors');
    }

    public function uniqueEmailRestrictionError(ApiTester $I): void
    {
        $response = $this->register($I, username: TestHelper::SUPER_USER_EMAIL);

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsString('User with this e-mail already exist');
        verify(count($response['errors']))->equals(1, 'Response contains 1 errors');
    }

    protected function addAdminToRemove(ApiTester $I, string $email): void
    {
        /** @var User $user */
        $user = $I->getEm()->getRepository(User::class)->findOneBy(['email' => $email]);
        $I->addToRemove($user);
    }

    /**
     * @return array<mixed>
     */
    protected function register(
        ApiTester $I,
        ?string $username = null,
        ?string $password = null,
        ?string $passwordRepeat = null,
        ?string $firstname = null,
        ?string $lastName = null
    ): array {
        $username = $username ?? $this->fake['username'];
        $response = $I->request('/register', 'POST', parameters: [
            'username' => $username,
            'password' => $password ?? $this->fake['password'],
            'passwordRepeat' => $passwordRepeat ?? ($password ?? $this->fake['passwordRepeat']),
            'firstname' => $firstname ?? $this->fake['firstName'],
            'surname' => $lastName ?? $this->fake['lastName'],
        ]);

        $response = json_decode($response, true);

        if (((bool) $response['success']) === true) {
            $this->addAdminToRemove($I, $username);
        }

        return $response;
    }
}
