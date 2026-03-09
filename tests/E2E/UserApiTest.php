<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserApiTest extends WebTestCase
{
    private const ROOT_TOKEN = 'root-api-token-for-testing-purposes';
    private const USER_TOKEN = 'user-api-token-for-testing-purposes';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    private function getRootId(): int
    {
        return $this->getUserByLogin('root')->getId();
    }

    private function getUserId(): int
    {
        return $this->getUserByLogin('user')->getId();
    }

    private function getUserByLogin(string $login): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return $em->getRepository(User::class)->findOneBy(['login' => $login]);
    }

    private function request(string $method, string $uri, ?string $token = null, ?array $body = null): void
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $this->client->request($method, $uri, [], [], $headers, $body !== null ? json_encode($body) : null);
    }

    private function getResponseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    // ========== Authentication Tests ==========

    public function testUnauthenticatedRequestReturns401(): void
    {
        $this->request('GET', '/v1/api/users');
        self::assertResponseStatusCodeSame(401);

        $data = $this->getResponseData();
        self::assertSame('Authentication required.', $data['message']);
    }

    public function testInvalidTokenReturns401(): void
    {
        $this->request('GET', '/v1/api/users', 'invalid-token');
        self::assertResponseStatusCodeSame(401);

        $data = $this->getResponseData();
        self::assertSame('Invalid credentials.', $data['message']);
    }

    // ========== GET /v1/api/users (list) ==========

    public function testRootCanListAllUsers(): void
    {
        $this->request('GET', '/v1/api/users', self::ROOT_TOKEN);
        self::assertResponseIsSuccessful();

        $data = $this->getResponseData();
        self::assertGreaterThanOrEqual(2, count($data));

        $logins = array_column($data, 'login');
        self::assertContains('root', $logins);
        self::assertContains('user', $logins);
    }

    public function testUserSeesOnlySelf(): void
    {
        $this->request('GET', '/v1/api/users', self::USER_TOKEN);
        self::assertResponseIsSuccessful();

        $data = $this->getResponseData();
        self::assertCount(1, $data);
        self::assertSame('user', $data[0]['login']);
    }

    // ========== GET /v1/api/users/{id} (show) ==========

    public function testRootCanViewAnyUser(): void
    {
        $userId = $this->getUserId();
        $this->request('GET', '/v1/api/users/' . $userId, self::ROOT_TOKEN);
        self::assertResponseIsSuccessful();

        $data = $this->getResponseData();
        self::assertSame('user', $data['login']);
    }

    public function testUserCanViewSelf(): void
    {
        $userId = $this->getUserId();
        $this->request('GET', '/v1/api/users/' . $userId, self::USER_TOKEN);
        self::assertResponseIsSuccessful();

        $data = $this->getResponseData();
        self::assertSame('user', $data['login']);
    }

    public function testUserCannotViewOther(): void
    {
        $rootId = $this->getRootId();
        $this->request('GET', '/v1/api/users/' . $rootId, self::USER_TOKEN);
        self::assertResponseStatusCodeSame(403);
    }

    public function testShowNonexistentUserReturns404(): void
    {
        $this->request('GET', '/v1/api/users/99999', self::ROOT_TOKEN);
        self::assertResponseStatusCodeSame(404);

        $data = $this->getResponseData();
        self::assertStringContainsString('not found', $data['message']);
    }

    // ========== POST /v1/api/users (create) ==========

    public function testRootCanCreateUser(): void
    {
        $this->request('POST', '/v1/api/users', self::ROOT_TOKEN, [
            'login' => 'newuser',
            'phone' => '55555555',
            'pass' => 'newpass',
        ]);
        self::assertResponseStatusCodeSame(201);

        $data = $this->getResponseData();
        self::assertSame('newuser', $data['login']);
        self::assertSame('55555555', $data['phone']);
        self::assertArrayHasKey('id', $data);
    }

    public function testUserCannotCreateUser(): void
    {
        $this->request('POST', '/v1/api/users', self::USER_TOKEN, [
            'login' => 'another',
            'phone' => '66666666',
            'pass' => 'pass123',
        ]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testCreateUserValidationErrors(): void
    {
        $this->request('POST', '/v1/api/users', self::ROOT_TOKEN, [
            'login' => '',
            'phone' => '',
            'pass' => '',
        ]);
        self::assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        self::assertSame('Validation failed.', $data['message']);
        self::assertArrayHasKey('errors', $data);
    }

    public function testCreateUserValidationMaxLength(): void
    {
        $this->request('POST', '/v1/api/users', self::ROOT_TOKEN, [
            'login' => 'toolonglogin',
            'phone' => '123456789',
            'pass' => '123456789',
        ]);
        self::assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        self::assertArrayHasKey('errors', $data);
    }

    public function testCreateDuplicateUserReturns409(): void
    {
        $this->request('POST', '/v1/api/users', self::ROOT_TOKEN, [
            'login' => 'root',
            'phone' => '99999999',
            'pass' => 'whatever',
        ]);

        // Either 409 for unique constraint (login is unique in DB)
        self::assertResponseStatusCodeSame(409);
    }

    // ========== PUT /v1/api/users/{id} (update) ==========

    public function testRootCanUpdateAnyUser(): void
    {
        $userId = $this->getUserId();
        $this->request('PUT', '/v1/api/users/' . $userId, self::ROOT_TOKEN, [
            'login' => 'updated',
            'phone' => '77777777',
            'pass' => 'newpass',
        ]);
        self::assertResponseIsSuccessful();

        $data = $this->getResponseData();
        self::assertSame('updated', $data['login']);
        self::assertSame('77777777', $data['phone']);
    }

    public function testUserCanUpdateSelf(): void
    {
        $userId = $this->getUserId();
        $this->request('PUT', '/v1/api/users/' . $userId, self::USER_TOKEN, [
            'login' => 'myself',
            'phone' => '88888888',
            'pass' => 'mypass',
        ]);
        self::assertResponseIsSuccessful();

        $data = $this->getResponseData();
        self::assertSame('myself', $data['login']);
    }

    public function testUserCannotUpdateOther(): void
    {
        $rootId = $this->getRootId();
        $this->request('PUT', '/v1/api/users/' . $rootId, self::USER_TOKEN, [
            'login' => 'hacked',
            'phone' => '00000000',
            'pass' => 'pass',
        ]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testUpdateNonexistentUserReturns404(): void
    {
        $this->request('PUT', '/v1/api/users/99999', self::ROOT_TOKEN, [
            'login' => 'nope',
            'phone' => '11111111',
            'pass' => 'pass',
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    public function testUpdateValidationErrors(): void
    {
        $userId = $this->getUserId();
        $this->request('PUT', '/v1/api/users/' . $userId, self::ROOT_TOKEN, [
            'login' => '',
            'phone' => '',
            'pass' => '',
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    // ========== DELETE /v1/api/users/{id} ==========

    public function testRootCanDeleteUser(): void
    {
        // Create a user to delete
        $this->request('POST', '/v1/api/users', self::ROOT_TOKEN, [
            'login' => 'todelete',
            'phone' => '44444444',
            'pass' => 'delpass',
        ]);
        self::assertResponseStatusCodeSame(201);
        $created = $this->getResponseData();

        $this->request('DELETE', '/v1/api/users/' . $created['id'], self::ROOT_TOKEN);
        self::assertResponseStatusCodeSame(204);

        // Verify deleted
        $this->request('GET', '/v1/api/users/' . $created['id'], self::ROOT_TOKEN);
        self::assertResponseStatusCodeSame(404);
    }

    public function testUserCannotDelete(): void
    {
        $userId = $this->getUserId();
        $this->request('DELETE', '/v1/api/users/' . $userId, self::USER_TOKEN);
        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteNonexistentUserReturns404(): void
    {
        $this->request('DELETE', '/v1/api/users/99999', self::ROOT_TOKEN);
        self::assertResponseStatusCodeSame(404);
    }

    // ========== Response format ==========

    public function testResponseIsJsonFormat(): void
    {
        $this->request('GET', '/v1/api/users', self::ROOT_TOKEN);
        self::assertResponseHeaderSame('content-type', 'application/json');
    }

    public function testErrorResponseIsJsonWithoutTrace(): void
    {
        $this->request('GET', '/v1/api/users/99999', self::ROOT_TOKEN);
        $data = $this->getResponseData();

        self::assertArrayHasKey('code', $data);
        self::assertArrayHasKey('message', $data);
        self::assertArrayNotHasKey('trace', $data);
        self::assertArrayNotHasKey('exception', $data);
        self::assertArrayNotHasKey('file', $data);
    }

    public function testUserResponseContainsExpectedFields(): void
    {
        $userId = $this->getUserId();
        $this->request('GET', '/v1/api/users/' . $userId, self::ROOT_TOKEN);

        $data = $this->getResponseData();
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('login', $data);
        self::assertArrayHasKey('phone', $data);
        // Password should NOT be exposed
        self::assertArrayNotHasKey('password', $data);
        self::assertArrayNotHasKey('pass', $data);
        self::assertArrayNotHasKey('apiToken', $data);
    }
}
