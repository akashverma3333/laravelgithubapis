<?php

namespace Akashverma3333\LaravelGitHubAPIs;

use Illuminate\Support\ServiceProvider;

class GitHubAPIServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            \Akashverma3333\LaravelGitHubAPIs\Commands\LoginCommand::class,
        ]);
        // Register any bindings or services you need here.
    }

    public function boot()
    {
        // Register commands.
        $this->commands([
            Commands\RepoCommand::class,
            Commands\BranchCommand::class,
            Commands\LoginCommand::class,
            Commands\LogoutCommand::class,
        ]);
    }
}
