<?php

namespace Jinomial\LaravelSsl\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Jinomial\LaravelSsl\Facades\Ssl;
use Jinomial\LaravelSsl\Support\Certificate;

final class CheckSslCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ssl:check {hosts?* : The hosts to check}';

    /**
     * The console command description.
     */
    protected $description = 'Check SSL health for configured or provided hosts';

    /**
     * Execute the console command.
     *
     * @api
     */
    public function handle(): int
    {
        /** @var string[] $hosts */
        $hosts = $this->argument('hosts');

        if (empty($hosts)) {
            /** @var string[] $hosts */
            $hosts = Config::get('ssl.monitored_hosts', []);
        }

        if (empty($hosts)) {
            $this->error('No hosts provided or configured in ssl.monitored_hosts.');

            return 1;
        }

        $headers = ['Host', 'Status', 'Common Name', 'Expires In', 'Message'];
        $rows = [];
        $warningThreshold = (int) Config::get('ssl.warning_threshold', 14);

        $this->info('Checking SSL certificates...');

        foreach ($hosts as $host) {
            try {
                $certificates = Ssl::show($host);
                $cert = $certificates->first();

                if (! $cert instanceof Certificate) {
                    $rows[] = [$host, '<error>FAILED</error>', '-', '-', 'Could not retrieve certificate'];

                    continue;
                }

                $status = $cert->isValid() ? '<info>VALID</info>' : '<error>INVALID</error>';
                $expiresAt = $cert->getValidTo();
                $diff = $expiresAt ? (int) $expiresAt->diffInDays(now(), false) : 0;
                $expiresIn = $expiresAt ? abs($diff) . ' days' : '-';

                if ($cert->isValid() && $expiresAt && $diff > -$warningThreshold) {
                    $status = '<comment>EXPIRING SOON</comment>';
                }

                if ($cert->isExpired()) {
                    $status = '<error>EXPIRED</error>';
                }

                $rows[] = [
                    $host,
                    $status,
                    $cert->getCommonName() ?? '-',
                    $expiresIn,
                    $cert->isVerificationSuccessful() ? 'OK' : ($cert->verification['message'] ?? 'Verification failed'),
                ];
            } catch (\Exception $e) {
                $rows[] = [$host, '<error>ERROR</error>', '-', '-', $e->getMessage()];
            }
        }

        $this->table($headers, $rows);

        return 0;
    }
}
