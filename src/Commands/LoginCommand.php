<?php

namespace Akashverma3333\LaravelGitHubAPIs\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class LoginCommand extends Command
{
    // Command signature to make it callable via `php artisan login`
    protected $signature = 'login';

    // Command description to be shown in the list of commands
    protected $description = 'Login to GitHub using stored credentials';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Retrieve GitHub username and token from the .env file
        $username = env('GITHUB_USERNAME');
        $token = env('GITHUB_TOKEN');

        // Check if both the username and token are set in the .env file
        if (!$username || !$token) {
            $this->error('GitHub username or token is not set in .env file.');
            return;
        }

        // Create a Guzzle HTTP client to interact with the GitHub API
        try {
            $client = new Client();
            $response = $client->get('https://api.github.com/user', [
                'auth' => [$username, $token]
            ]);

            // Check if the response is successful (status code 200)
            if ($response->getStatusCode() === 200) {
                $this->info('Logged in successfully as ' . $username);
            } else {
                $this->error('Invalid GitHub token.');
            }
        } catch (\Exception $e) {
            $this->error('Error connecting to GitHub: ' . $e->getMessage());
        }
    }
}
