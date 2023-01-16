<?php

namespace App\Tests\Integration\Service;

use App\Service\Util\HttpUtilService;
use App\Service\ValidationService;
use App\Tests\Integration\BaseIntegrationAbstract;
use Codeception\Attribute\Examples;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidateServiceTest extends BaseIntegrationAbstract
{
    protected HttpUtilService $httpUtilService;
    protected ValidatorInterface $validator;
    protected ValidationService $validationService;

    public function _before(): void
    {
        parent::_before();
        $this->httpUtilService = $this->getService(HttpUtilService::class);
        $this->validator = $this->getService(ValidatorInterface::class);

        $this->validationService = new ValidationService(
            $this->httpUtilService,
            $this->validator,
        );

        $this->httpUtilService->setErrors([]);
    }

    #[Examples('zaq1@WSX', true, true, true, true, 8, true, 0)]
    #[Examples('zaq1@WSX', true, true, true, true, 9, false, 1)]
    #[Examples('T', true, false, false, false, 0, true, 0)]
    #[Examples('t', true, false, false, false, 0, false, 1)]
    #[Examples('t', false, true, false, false, 0, true, 0)]
    #[Examples('T', false, true, false, false, 0, false, 1)]
    #[Examples('1', false, false, true, false, 0, true, 0)]
    #[Examples('t', false, false, true, false, 0, false, 1)]
    #[Examples('@', false, false, false, true, 0, true, 0)]
    #[Examples('t', false, false, false, true, 0, false, 1)]
    #[Examples('t', false, false, false, false, 1, true, 0)]
    #[Examples('t', false, false, false, false, 2, false, 1)]
    #[Examples('test', false, false, false, false, 2, true, 0)]
    public function testValidatePasswordStrength(
        string $password,
        bool $upper,
        bool $lower,
        bool $number,
        bool $special,
        int $length,
        bool $expectedRes,
        int $errorCount
    ): void {
        $res = $this->validationService->validatePasswordStrength(
            $password,
            $upper,
            $lower,
            $number,
            $special,
            $length,
        );
        $this->assertEquals(
            $expectedRes,
            $res,
            'Password was validated incorrectly: ' . implode(', ', $this->httpUtilService->getErrors())
        );
        $this->assertEquals($errorCount, count($this->httpUtilService->getErrors()), 'Errors count doesn\'t match');
    }

    public function testFailedValidationAddErrorMessages(): void
    {
        $constraint = new Assert\Collection([
            'test' => [
                new Assert\Ip([
                    'message' => 'Test must be an IP',
                ]),
                new Assert\Length([
                    'min' => 15,
                    'minMessage' => 'Test must have at least 15 characters',
                ]),
            ],
        ]);
        $test = [
            'test' => '123..123',
        ];
        $this->validationService->validate($test, $constraint);

        $this->assertEquals(2, count($this->httpUtilService->getErrors()['test']), 'Errors count doesn\'t match');
    }
}
