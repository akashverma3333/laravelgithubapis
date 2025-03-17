<?php

namespace Akash\LaravelGitHubAPIs\Commands;

use Illuminate\Console\Command;

class LogoutCommand extends Command
{
    protected $signature = 'logout';
    protected $description = 'Logout from GitHub (by clearing the stored token)';

    public function handle()
    {
        $this->info('You have logged out of GitHub. Remember to delete the GITHUB_TOKEN from .env.');
    }
}
