<?php

namespace Akashverma3333\LaravelGitHubAPIs;

use Illuminate\Support\ServiceProvider;

class GitHubAPIServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register any bindings or services if needed
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) { // Ensure commands only register in CLI
            $this->commands([
                Commands\RepoCommand::class,
                Commands\BranchCommand::class,
                Commands\LoginCommand::class,
                Commands\LogoutCommand::class,
            ]);
        }
    }
}
