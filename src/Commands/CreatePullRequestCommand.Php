<?php

namespace Akashverma3333\LaravelGitHubAPIs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class CreatePullRequestCommand extends Command
{
    protected $signature = 'github:create-pull-request';
    protected $description = 'Interactively create a GitHub pull request with a predefined template.';

    public function handle()
    {
        $githubToken = env('GITHUB_TOKEN');
        if (!$githubToken) {
            return $this->error("GitHub token not set. Please add GITHUB_TOKEN to .env");
        }

        // Detect repository and branch details
        $repo = $this->getCurrentRepository();
        if (!$repo) {
            return $this->error("Failed to detect the GitHub repository. Ensure you are inside a Git repository.");
        }
        
        $featureBranch = $this->getCurrentBranch();
        $defaultBranch = $this->getDefaultBranch($repo, $githubToken);
        $baseBranch = $defaultBranch; // Automatically setting base branch

        $this->info("Current repository detected: $repo");
        $this->info("Feature branch detected: $featureBranch");
        $this->info("Base branch detected: $baseBranch");

        // Check for uncommitted changes
        if ($this->hasUncommittedChanges()) {
            $this->warn("⚠️ Uncommitted changes detected!");
            if ($this->confirm("Would you like to commit and push changes before creating the PR?")) {
                $commitMessage = $this->ask("Enter commit message", "Auto commit");
                shell_exec("git add .");
                shell_exec("git commit -m \"$commitMessage\"");
                shell_exec("git push origin $featureBranch");
                $this->info("✅ Changes committed and pushed.");
            } else {
                return $this->error("❌ PR cannot be created with uncommitted changes.");
            }
        } else {
            $this->info("✅ No uncommitted changes found.");
        }

        // Get user input
        $ticketId = $this->ask('Enter Ticket ID');
        $title = $this->ask('Enter PR Title');
        $description = $this->ask('Enter Description');
        
        $username = $this->getGitHubUsername($githubToken);
        $url = "https://github.com/$repo/compare/$baseBranch...$featureBranch";

        // Validate feature branch existence
        if (!$this->branchExists($repo, $featureBranch, $githubToken)) {
            $this->warn("Branch '$featureBranch' does not exist. Creating it...");
            if (!$this->createBranchOnGitHub($repo, $featureBranch, $baseBranch, $githubToken)) {
                return $this->error("Failed to create feature branch.");
            }
        }

        // Load PR template
        $prTemplatePath = resource_path('pr_template.md'); // Path to the markdown template
        $prBody = $this->loadPrTemplate($prTemplatePath, $ticketId, $title, $description, $url, $username, $featureBranch);
        if (!$prBody) {
            return $this->error("Failed to load PR template.");
        }

        // Save the final markdown to a file
        $filePath = storage_path('app/pr_template_output.md');
        File::put($filePath, $prBody);
        $this->info("✅ PR template saved to $filePath");

        // Create Pull Request
        $payload = [
            'title' => "[$ticketId] - $title",
            'head' => $featureBranch,
            'base' => $baseBranch,
            'body' => $prBody,
            'assignees' => [$username],
        ];

        $response = $this->postToGitHub("repos/$repo/pulls", $githubToken, $payload);

        if ($response->successful()) {
            $prUrl = $response->json()['html_url'];
            $this->info("✅ Pull request created successfully: $prUrl");
        } else {
            $this->error("❌ Failed to create pull request: " . $response->body());
        }
    }

    // Get the current repository name
    private function getCurrentRepository()
    {
        $repoUrl = trim(shell_exec('git remote get-url origin'));
        return preg_replace('/.*github.com[:\/](.*)\.git$/', '$1', $repoUrl);
    }

    // Get the current checked-out branch
    private function getCurrentBranch()
    {
        return trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
    }

    // Check if there are uncommitted changes
    private function hasUncommittedChanges()
    {
        $status = trim(shell_exec('git status --porcelain'));
        return !empty($status);
    }

    private function getGitHubUsername($githubToken)
    {
        $response = Http::withToken($githubToken)->get('https://api.github.com/user');
        return $response->successful() ? $response->json()['login'] : null;
    }

    private function branchExists($repo, $branch, $githubToken)
    {
        $response = Http::withToken($githubToken)->get("https://api.github.com/repos/$repo/branches/$branch");
        return $response->successful();
    }

    private function getDefaultBranch($repo, $githubToken)
    {
        $response = Http::withToken($githubToken)->get("https://api.github.com/repos/$repo");
        return $response->successful() ? $response->json()['default_branch'] : 'main';
    }

    private function createBranchOnGitHub($repo, $branch, $baseBranch, $githubToken)
    {
        $response = Http::withToken($githubToken)->post("https://api.github.com/repos/$repo/git/refs", [
            'ref' => 'refs/heads/' . $branch,
            'sha' => $this->getBranchSha($repo, $baseBranch, $githubToken),
        ]);

        return $response->successful();
    }

    private function getBranchSha($repo, $baseBranch, $githubToken)
    {
        $response = Http::withToken($githubToken)->get("https://api.github.com/repos/$repo/branches/$baseBranch");
        return $response->successful() ? $response->json()['commit']['sha'] : null;
    }

    private function loadPrTemplate($templatePath, $ticketId, $title, $description, $url, $username, $featureBranch)
    {
        if (!File::exists($templatePath)) {
            return null;
        }

        $template = File::get($templatePath);
        $template = str_replace('#ticketId', $ticketId, $template);
        $template = str_replace('#title', $title, $template);
        $template = str_replace('#description', $description, $template);
        $template = str_replace('#url', $url, $template);
        $template = str_replace('#username', $username, $template);
        $template = str_replace('#featureBranch', $featureBranch, $template);

        return $template;
    }

    private function postToGitHub($endpoint, $githubToken, $payload)
    {
        return Http::withToken($githubToken)->post("https://api.github.com/$endpoint", $payload);
    }
}
