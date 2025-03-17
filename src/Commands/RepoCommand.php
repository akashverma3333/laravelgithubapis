<?php

namespace Akashverma3333\LaravelGitHubAPIs\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class RepoCommand extends Command
{
    protected $signature = 'repo {repo}';
    protected $description = 'Fetch repository details from GitHub using the GitHub API';

    public function handle()
    {
        $repo = $this->argument('repo');
        $token = env('GITHUB_TOKEN');
        $client = new Client();

        try {
            $response = $client->get("https://api.github.com/repos/{$repo}", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ]
            ]);

            $repoData = json_decode($response->getBody(), true);
            $this->info("Repository: {$repoData['name']}");
            $this->info("Description: {$repoData['description']}");
            $this->info("Stars: {$repoData['stargazers_count']}");
        } catch (\Exception $e) {
            $this->error("Failed to fetch repo data: " . $e->getMessage());
        }
    }
}
