<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $event->setResponse(
            $this->buildResponse($event->getThrowable()),
        );
    }

    private function buildResponse(\Throwable $exception): JsonResponse
    {
        return match (true) {
            $exception instanceof HttpExceptionInterface => $this->handleHttpException($exception),
            $exception instanceof UniqueConstraintViolationException => $this->jsonError(Response::HTTP_CONFLICT, 'Duplicate entry. A user with this data already exists.'),
            default => $this->jsonError(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal server error.'),
        };
    }

    private function handleHttpException(HttpExceptionInterface $exception): JsonResponse
    {
        $statusCode = $exception->getStatusCode();
        $previous = $exception->getPrevious();

        if ($previous instanceof ValidationFailedException) {
            return $this->handleValidationException($previous, $statusCode);
        }

        return $this->jsonError(
            $statusCode,
            $exception->getMessage() ?: Response::$statusTexts[$statusCode] ?? 'Error',
        );
    }

    private function handleValidationException(ValidationFailedException $exception, int $statusCode): JsonResponse
    {
        $errors = [];

        foreach ($exception->getViolations() as $violation) {
            $field = $violation->getPropertyPath();
            $errors[$field][] = $violation->getMessage();
        }

        return new JsonResponse(
            ['code' => $statusCode, 'message' => 'Validation failed.', 'errors' => $errors],
            $statusCode,
        );
    }

    private function jsonError(int $code, string $message): JsonResponse
    {
        return new JsonResponse(['code' => $code, 'message' => $message], $code);
    }
}
