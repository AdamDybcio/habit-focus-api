<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Console;

use App\Auth\Domain\RefreshTokenRepository;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deletes expired refresh tokens. Run it daily from cron, see docs/deployment.md.
 *
 * Tokens are kept for a day after expiry, so a replay of a just expired
 * token is still recognized as a known token and not as garbage.
 */
#[AsCommand(name: 'app:refresh-tokens:purge', description: 'Delete expired refresh tokens')]
final class PurgeExpiredRefreshTokensCommand extends Command
{
    public function __construct(
        private readonly RefreshTokenRepository $refreshTokens,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $deleted = $this->refreshTokens->deleteExpiredBefore($this->clock->now()->modify('-1 day'));
        $io->success(\sprintf('Deleted %d expired refresh tokens.', $deleted));

        return Command::SUCCESS;
    }
}
