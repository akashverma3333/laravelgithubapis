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

                \Akashverma3333\LaravelGitHubAPIs\Commands\RepoCommand::class,
                \Akashverma3333\LaravelGitHubAPIs\Commands\BranchCommand::class,
                \Akashverma3333\LaravelGitHubAPIs\Commands\LoginCommand::class,
                \Akashverma3333\LaravelGitHubAPIs\Commands\LogoutCommand::class,
                \Akashverma3333\LaravelGitHubAPIs\Commands\ShowBranchContentCommand::class, 
                \Akashverma3333\LaravelGitHubAPIs\Commands\EditBranchFileCommand::class,
                \Akashverma3333\LaravelGitHubAPIs\Commands\ShowGitHelpCommand::class,
                \Akashverma3333\LaravelGitHubAPIs\Commands\CreateBranchCommand::class,
                \Akashverma3333\LaravelGitHubAPIs\Commands\CheckoutBranchCommand::class,
                \Akashverma3333\LaravelGitHubAPIs\Commands\CreatePullRequestCommand::class,

            ]);
        }
    }
}
