<?php

namespace LaravelGitHubAPIs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class CreatePullRequestCommand extends Command
{
    protected $signature = 'create-pr';
    protected $description = 'Create a GitHub Pull Request with a Markdown file';

    public function handle()
    {
        // Collect User Inputs
        $ticketId = $this->ask('Enter Ticket ID (used as PR title)');
        $description = $this->ask('Enter PR description');
        $assignees = $this->ask('Enter assignees (comma-separated usernames)') ?? '';
        $reviewers = $this->ask('Enter reviewers (comma-separated usernames)') ?? '';
        $baseBranch = $this->ask('Enter base branch', 'main');
        $headBranch = $this->ask('Enter your feature branch');

        // Convert comma-separated lists into arrays
        $assigneesArray = $assignees ? array_map('trim', explode(',', $assignees)) : [];
        $reviewersArray = $reviewers ? array_map('trim', explode(',', $reviewers)) : [];

        // Generate Markdown Content
        $mdContent = "# PR: $ticketId\n\n";
        $mdContent .= "## Description\n$description\n\n";
        
        $mdContent .= "## Assignees\n";
        foreach ($assigneesArray as $assignee) {
            $mdContent .= "- @$assignee\n";
        }
        
        $mdContent .= "\n## Reviewers\n";
        foreach ($reviewersArray as $reviewer) {
            $mdContent .= "- @$reviewer\n";
        }

        // Save Markdown File
        $filePath = storage_path("app/pull_request.md");
        File::put($filePath, $mdContent);
        $this->info("📄 Markdown file saved: $filePath");

        // GitHub API Authentication
        $githubToken = env('GITHUB_TOKEN');
        if (!$githubToken) {
            $this->error('❌ GitHub token not found. Set GITHUB_TOKEN in your .env file.');
            return;
        }

        // Define GitHub Repository
        $repoOwner = env('GITHUB_OWNER', 'your-github-username');
        $repoName = env('GITHUB_REPO', 'your-repo-name');

        // Step 1: Create Pull Request
        $prResponse = Http::withToken($githubToken)
            ->withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Laravel-GitHub-Package'
            ])
            ->post("https://api.github.com/repos/$repoOwner/$repoName/pulls", [
                'title' => $ticketId,
                'body' => $mdContent,
                'head' => $headBranch,
                'base' => $baseBranch,
            ]);

        // Check if PR was created successfully
        if (!$prResponse->successful()) {
            $this->error("❌ Failed to create pull request. Error: " . json_encode($prResponse->json(), JSON_PRETTY_PRINT));
            return;
        }

        // Extract PR Number
        $prNumber = $prResponse->json()['number'];
        $prUrl = "https://github.com/$repoOwner/$repoName/pull/$prNumber";
        $this->info("✅ Pull request created successfully! PR Number: $prNumber");
        $this->info("🔗 Check it on GitHub: $prUrl");
        

        // Step 2: Assign Users to PR
        if (!empty($assigneesArray)) {
            $assignResponse = Http::withToken($githubToken)
                ->post("https://api.github.com/repos/$repoOwner/$repoName/issues/$prNumber/assignees", [
                    'assignees' => $assigneesArray,
                ]);

            if ($assignResponse->successful()) {
                $this->info("✅ Assignees added successfully!");
            } else {
                $this->error("⚠️ Failed to add assignees. Error: " . json_encode($assignResponse->json(), JSON_PRETTY_PRINT));
            }
        }

        // Step 3: Add Reviewers to PR (Updated Fix)
        if (!empty($reviewersArray)) {
            $reviewResponse = Http::withToken($githubToken)
                ->post("https://api.github.com/repos/$repoOwner/$repoName/pulls/$prNumber/requested_reviewers", [
                    'reviewers' => $reviewersArray,
                ]);

            if ($reviewResponse->successful()) {
                $this->info("✅ Reviewers added successfully!");
            } else {
                $errorData = $reviewResponse->json();
                if (isset($errorData['message']) && str_contains($errorData['message'], 'only be requested from collaborators')) {
                    $this->warn("⚠️ Some reviewers were not added because they are not collaborators.");
                } else {
                    $this->warn("⚠️ Failed to add reviewers. Error: " . json_encode($errorData, JSON_PRETTY_PRINT));
                }
            }
        }
    }
}
