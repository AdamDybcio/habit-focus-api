<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Auth\Domain\Exception\InvalidCredentials;
use App\Auth\Domain\Exception\InvalidRefreshToken;
use App\User\Domain\Exception\EmailAlreadyRegistered;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Turns every exception into an RFC 9457 "problem details" JSON response, so
 * clients always get the same error shape and never a stack trace.
 *
 * To expose a new domain exception, add it to {@see self::DOMAIN_ERRORS}.
 */
#[AsEventListener(event: ExceptionEvent::class)]
final readonly class ApiExceptionListener
{
    /** @var array<class-string<\Throwable>, int> */
    private const DOMAIN_ERRORS = [
        InvalidCredentials::class => Response::HTTP_UNAUTHORIZED,
        InvalidRefreshToken::class => Response::HTTP_UNAUTHORIZED,
        EmailAlreadyRegistered::class => Response::HTTP_CONFLICT,
    ];

    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (null !== $event->getResponse()) {
            return;
        }

        $throwable = $event->getThrowable();
        $headers = [];
        $violations = $this->violations($throwable);

        if ($throwable instanceof HttpExceptionInterface) {
            $status = $throwable->getStatusCode();
            $headers = $throwable->getHeaders();
            $detail = $throwable->getMessage();
        } elseif (isset(self::DOMAIN_ERRORS[$throwable::class])) {
            $status = self::DOMAIN_ERRORS[$throwable::class];
            $detail = $throwable->getMessage();
        } else {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            $detail = $this->debug ? $throwable->getMessage() : 'Internal server error.';
        }

        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
            if (!$this->debug) {
                $detail = 'Internal server error.';
            }
        }

        $body = [
            'type' => 'about:blank',
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'status' => $status,
            'detail' => $detail,
        ];
        if ([] !== $violations) {
            $body['violations'] = $violations;
        }

        $headers['Content-Type'] = 'application/problem+json';
        $event->setResponse(new JsonResponse($body, $status, $headers));
    }

    /**
     * @return list<array{field: string, message: string}>
     */
    private function violations(\Throwable $throwable): array
    {
        for ($current = $throwable; null !== $current; $current = $current->getPrevious()) {
            if (!$current instanceof ValidationFailedException) {
                continue;
            }

            $violations = [];
            foreach ($current->getViolations() as $violation) {
                $violations[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => (string) $violation->getMessage(),
                ];
            }

            return $violations;
        }

        return [];
    }
}
