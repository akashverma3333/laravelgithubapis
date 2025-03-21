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

        // Automatically detect the current repository
        $repo = $this->getCurrentRepository();
        if (!$repo) {
            return $this->error("Failed to detect the GitHub repository. Ensure you are inside a Git repository.");
        }
        
        $this->info("Current repository detected: $repo");

        // Get user input
        $ticketId = $this->ask('Enter Ticket ID');
        $title = $this->ask('Enter PR Title');
        $description = $this->ask('Enter Description');
        $featureBranch = $this->ask('Enter Feature Branch');
        $baseBranch = $this->ask('Enter Base Branch (default: main)', 'main');

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

          // Fetch automatic reviewers (e.g., collaborators) and assignee
        // $reviewers = $this->getAutoReviewers($repo, $githubToken);
        // $assignee = $username; // The authenticated user will be the assignee.

        // Create Pull Request (existing logic)
        $payload = [
            'title' => "[$ticketId] - $title",
            'head' => $featureBranch,
            'base' => $baseBranch,
            'body' => $prBody,
            'assignees' => [$username],  // Set assignee to the authenticated user
            // 'reviewers' => $reviewers,   // Set reviewers to the fetched collaborators
        ];

        $response = $this->postToGitHub("repos/$repo/pulls", $githubToken, $payload);

        if ($response->successful()) {
            $prUrl = $response->json()['html_url'];
            $this->info("✅ Pull request created successfully: $prUrl");
        } else {
            $this->error("❌ Failed to create pull request: " . $response->body());
        }
    }
        //  creating method to get current repository
    private function getCurrentRepository()
    {
        $repoUrl = trim(shell_exec('git remote get-url origin'));
        return preg_replace('/.*github.com[:\/](.*)\.git$/', '$1', $repoUrl);
    }

    private function getGitHubUsername($githubToken)
    {
        $response = Http::withToken($githubToken)->get('https://api.github.com/user');

        if ($response->successful()) {
            return $response->json()['login']; // Return the GitHub username
        }

        return null; // Return null if the request fails
    }

    private function branchExists($repo, $branch, $githubToken)
    {
        $response = Http::withToken($githubToken)->get("https://api.github.com/repos/$repo/branches/$branch");

        return $response->successful();
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

        if ($response->successful()) {
            return $response->json()['commit']['sha']; // Return the SHA of the base branch
        }

        return null; // Return null if the request fails
    }

    private function loadPrTemplate($templatePath, $ticketId, $title, $description, $url, $username, $featureBranch)
    {
        // Load the markdown template from file
        if (!File::exists($templatePath)) {
            return null;
        }

        $template = File::get($templatePath);

        // Replace placeholders with dynamic content
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

    /**
 * Fetch automatic reviewers from the repository (e.g., from collaborators)
 */
// private function getAutoReviewers($repo, $githubToken)
// {
//     $response = Http::withToken($githubToken)->get("https://api.github.com/repos/$repo/collaborators");

//     if ($response->successful()) {
//         $collaborators = $response->json();
//         // Select the first collaborator (or customize this logic)
//         $reviewers = array_map(fn($collaborator) => $collaborator['login'], $collaborators);
//         return $reviewers; // Return the list of reviewers
//     }

//     return []; // Return an empty array if no reviewers could be fetched
// }

}