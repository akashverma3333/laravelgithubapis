<?php

namespace LaravelGitHubAPIs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;


class CreatePullRequestCommand extends Command
{
    protected $signature = 'create-pr';
    protected $description = 'Create a new pull request on GitHub and store details in a text file';

    public function handle()
    {
        // Ask for user inputs
        $ticketId = $this->ask('Enter ticket ID');
        $owner = $this->ask('Enter GitHub username or organization');
        $repo = $this->ask('Enter repository name');
        $baseBranch = $this->ask('Enter base branch (e.g., main, develop)');
        $headBranch = $this->ask('Enter head branch (feature-branch, fix-bug)');
        $title = $this->ask('Enter PR Title');
        $description = $this->ask('Enter PR Description');

        // Store in a file (inside storage/app/)
        $data = [
            'ticketId' => $ticketId,
            'owner' => $owner,
            'repo' => $repo,
            'baseBranch' => $baseBranch,
            'headBranch' => $headBranch,
            'title' => $title,
            'description' => $description,
            'created_at' => now()
        ];

        $filePath = getenv("HOMEPATH") ? getenv("HOMEPATH") . '\pr_details.txt' : 'C:\Users\akash\pr_details.txt';
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        $this->info("✅ PR details saved in: $filePath");

        // (Optional) If you want to create the PR on GitHub, add API logic here
        $this->createGitHubPullRequest($owner, $repo, $baseBranch, $headBranch, $title, $description);
    }

    private function createGitHubPullRequest($owner, $repo, $baseBranch, $headBranch, $title, $description)
    {
        $githubToken = env('GITHUB_TOKEN');

        if (!$githubToken) {
            $this->error("❌ GitHub Token not set! Add it in .env as GITHUB_TOKEN");
            return;
        }

        $response = Http::withToken($githubToken)->post("https://api.github.com/repos/$owner/$repo/pulls", [
            'title' => $title,
            'head' => $headBranch,
            'base' => $baseBranch,
            'body' => $description
        ]);

        if ($response->successful()) {
            $prUrl = $response->json()['html_url'];
            $this->info("✅ Pull Request Created: $prUrl");
        } else {
            $this->error("❌ Failed to create PR: " . json_encode($response->json(), JSON_PRETTY_PRINT));
        }
    }
}
