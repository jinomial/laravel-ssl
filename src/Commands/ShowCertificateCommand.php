<?php

namespace Jinomial\LaravelSsl\Commands;

use Illuminate\Console\Command;
use Jinomial\LaravelSsl\Facades\Ssl;

final class ShowCertificateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ssl:show
        {host : The host to connect to}
        {port=443 : The port to connect to}';

    /**
     * The console command description.
     */
    protected $description = 'Show a security certificate';

    /**
     * Execute the console command.
     *
     * @api
     */
    public function handle(): int
    {
        $host = $this->argument('host');
        $port = $this->argument('port') ?? '443';

        if (is_array($host) || is_array($port)) {
            $this->error('Host and port cannot be arrays.');

            return 1;
        }

        $host = (string) $host;
        $port = (string) $port;

        $certificate = Ssl::show($host, $port);
        $json = json_encode($certificate);

        if ($json !== false) {
            $this->info($json);
        }

        return 0;
    }
}
