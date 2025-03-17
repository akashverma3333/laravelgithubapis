<?php

namespace Akash\LaravelGitHubAPIs\Commands;

use Illuminate\Console\Command;

class LoginCommand extends Command
{
    protected $signature = 'login';
    protected $description = 'Login to GitHub using the GitHub API';

    public function handle()
    {
        $this->info('You are already logged in if you have set the personal access token in .env file.');
    }
}
