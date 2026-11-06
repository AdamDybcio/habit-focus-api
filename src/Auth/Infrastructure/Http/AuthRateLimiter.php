<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http;

use App\User\Domain\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Throttles the public auth endpoints against brute force and abuse.
 *
 * Login is limited twice: per client IP (many guesses from one place) and per
 * email (many guesses from many places against one account).
 */
final readonly class AuthRateLimiter
{
    public function __construct(
        #[Autowire(service: 'limiter.auth_login_ip')]
        private RateLimiterFactory $loginByIp,
        #[Autowire(service: 'limiter.auth_login_email')]
        private RateLimiterFactory $loginByEmail,
        #[Autowire(service: 'limiter.auth_refresh')]
        private RateLimiterFactory $refresh,
        #[Autowire(service: 'limiter.auth_register')]
        private RateLimiterFactory $register,
    ) {
    }

    public function checkLogin(Request $request, string $email): void
    {
        $this->consume($this->loginByIp, $this->ip($request));
        $this->consume($this->loginByEmail, hash('sha256', User::normalizeEmail($email)));
    }

    public function checkRefresh(Request $request): void
    {
        $this->consume($this->refresh, $this->ip($request));
    }

    public function checkRegister(Request $request): void
    {
        $this->consume($this->register, $this->ip($request));
    }

    private function consume(RateLimiterFactory $factory, string $key): void
    {
        $limit = $factory->create($key)->consume();

        if (!$limit->isAccepted()) {
            $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

            throw new TooManyRequestsHttpException($retryAfter, 'Too many requests, try again later.');
        }
    }

    private function ip(Request $request): string
    {
        return $request->getClientIp() ?? 'unknown';
    }
}
