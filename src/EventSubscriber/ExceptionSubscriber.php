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

final class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $response = $this->buildResponse($exception);

        $event->setResponse($response);
    }

    private function buildResponse(\Throwable $exception): JsonResponse
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $this->handleHttpException($exception);
        }

        if ($exception instanceof \DomainException) {
            $code = $exception->getCode() ?: Response::HTTP_BAD_REQUEST;

            return new JsonResponse(
                ['code' => $code, 'message' => $exception->getMessage()],
                $code,
            );
        }

        if ($exception instanceof UniqueConstraintViolationException) {
            return new JsonResponse(
                ['code' => Response::HTTP_CONFLICT, 'message' => 'Duplicate entry. A user with this data already exists.'],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(
            ['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'message' => 'Internal server error.'],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    private function handleHttpException(HttpExceptionInterface $exception): JsonResponse
    {
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage();

        $previous = $exception->getPrevious();
        if ($previous instanceof ValidationFailedException) {
            return $this->handleValidationException($previous, $statusCode);
        }

        return new JsonResponse(
            ['code' => $statusCode, 'message' => $message ?: Response::$statusTexts[$statusCode] ?? 'Error'],
            $statusCode,
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
            [
                'code' => $statusCode,
                'message' => 'Validation failed.',
                'errors' => $errors,
            ],
            $statusCode,
        );
    }
}
