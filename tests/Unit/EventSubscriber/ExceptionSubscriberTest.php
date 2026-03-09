<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use App\Exception\UserNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ExceptionSubscriberTest extends TestCase
{
    private ExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new ExceptionSubscriber();
    }

    private function createEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ExceptionEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    public function testSubscribesToKernelException(): void
    {
        $events = ExceptionSubscriber::getSubscribedEvents();
        self::assertArrayHasKey(KernelEvents::EXCEPTION, $events);
    }

    public function testHandlesUserNotFoundException(): void
    {
        $event = $this->createEvent(new UserNotFoundException(42));
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertSame('User with id 42 not found.', $data['message']);
    }

    public function testHandlesHttpException(): void
    {
        $event = $this->createEvent(new HttpException(403, 'Access Denied'));
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(403, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertSame('Access Denied', $data['message']);
    }

    public function testHandlesValidationException(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Login is required.', '', [], '', 'login', ''),
        ]);
        $validationException = new ValidationFailedException('value', $violations);
        $httpException = new HttpException(422, 'Validation failed', $validationException);

        $event = $this->createEvent($httpException);
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertSame('Validation failed.', $data['message']);
        self::assertArrayHasKey('login', $data['errors']);
        self::assertContains('Login is required.', $data['errors']['login']);
    }

    public function testHandlesUniqueConstraintViolation(): void
    {
        $dbalException = $this->createMock(UniqueConstraintViolationException::class);

        $event = $this->createEvent($dbalException);
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(409, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertStringContainsString('Duplicate entry', $data['message']);
    }

    public function testHandlesUnexpectedExceptionWithoutTrace(): void
    {
        $event = $this->createEvent(new \RuntimeException('Something broke'));
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(500, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertSame('Internal server error.', $data['message']);
        self::assertArrayNotHasKey('trace', $data);
        self::assertArrayNotHasKey('exception', $data);
    }
}
