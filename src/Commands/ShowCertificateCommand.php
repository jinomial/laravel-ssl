<?php

namespace Jinomial\LaravelSsl\Commands;

use Illuminate\Console\Command;
use Jinomial\LaravelSsl\Facades\Ssl;

class ShowCertificateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssl:show
        {host : The host to connect to}
        {port=443 : The port to connect to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show a security certificate';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $host = $this->argument('host');
        $port = $this->argument('port');
        $certificate = Ssl::show($host, $port);
        $this->info(json_encode($certificate));

        return 0;
    }
}
