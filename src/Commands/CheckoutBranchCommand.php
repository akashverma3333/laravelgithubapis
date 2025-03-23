<?php

namespace Akashverma3333\LaravelGitHubAPIs\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CheckoutBranchCommand extends Command
{
    protected $signature = 'branch:checkout {repository} {branch}';
    protected $description = 'Checkout to a branch using GitHub API or local Git';

    public function handle()
    {
        if (!$this->isGitInstalled()) {
            return;
        }

        $repository = $this->argument('repository');
        $branch = $this->argument('branch');

        $this->checkoutBranch($repository, $branch);
    }

    // ✅ Check if Git is installed
    private function isGitInstalled(): bool
    {
        $process = new Process(['git', '--version']);
        $process->run();

        if ($process->isSuccessful()) {
            $this->info("✅ Git is installed.");
            return true;
        }

        $this->error("❌ Git is not installed.");
        return false;
    }

    // ✅ Checkout branch logic with proper error handling
    private function checkoutBranch($repo, $branch)
    {
        $repoPath = getcwd(); // Get current working directory

        if (!is_dir($repoPath . '/.git')) {
            $this->error("❌ Not a valid Git repository: '$repoPath'");
            return;
        }

        // 🔄 Fetch latest branches
        $this->info("🔄 Fetching latest branches...");
        (new Process(['git', 'fetch', '--all']))->setWorkingDirectory($repoPath)->run();

        // 🔍 Check for uncommitted changes
        $statusProcess = new Process(['git', 'status', '--porcelain']);
        $statusProcess->setWorkingDirectory($repoPath);
        $statusProcess->run();

        if (!empty($statusProcess->getOutput())) {
            $this->warn("⚠️ Uncommitted changes detected! Stashing changes...");
            (new Process(['git', 'stash']))->setWorkingDirectory($repoPath)->run();
        }

        // 🔄 Attempt to checkout the branch
        $this->info("🔄 Checking out branch: $branch...");
        $checkoutProcess = new Process(['git', 'checkout', $branch]);
        $checkoutProcess->setWorkingDirectory($repoPath);
        $checkoutProcess->run();

        if ($checkoutProcess->isSuccessful()) {
            $this->info("✅ Switched to branch: $branch");
            return;
        }

        // 🔄 If checkout fails, try creating/tracking remote branch
        $this->warn("⚠️ Checkout failed. Trying to track remote branch...");
        $trackProcess = new Process(['git', 'checkout', '-B', $branch, "origin/$branch"]);
        $trackProcess->setWorkingDirectory($repoPath);
        $trackProcess->run();

        if ($trackProcess->isSuccessful()) {
            $this->info("✅ Successfully switched to remote-tracked branch: $branch");
        } else {
            $this->error("❌ Failed to checkout branch: $branch. Make sure it exists.");
        }
    }
}
