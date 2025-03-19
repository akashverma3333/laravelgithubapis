<?php

namespace LaravelGitHubAPIs;

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
                \LaravelGitHubAPIs\Commands\RepoCommand::class,
                \LaravelGitHubAPIs\Commands\BranchCommand::class,
                \LaravelGitHubAPIs\Commands\LoginCommand::class,
                \LaravelGitHubAPIs\Commands\LogoutCommand::class,
                \LaravelGitHubAPIs\Commands\ShowBranchContentCommand::class, 
                \LaravelGitHubAPIs\Commands\EditBranchFileCommand::class,
                \LaravelGitHubAPIs\Commands\ShowGitHelpCommand::class,
                \LaravelGitHubAPIs\Commands\CheckoutBranchCommand::class,
            ]);
        }
    }
}
