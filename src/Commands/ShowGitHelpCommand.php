<?php

namespace Bhavneeshgoyal99\LaravelGitHub\Commands;

use Illuminate\Console\Command;

class ShowGitHelpCommand extends Command
{
    protected $signature = 'showallcommands';
    protected $description = 'Show all saved GitHub commands';

    public function handle()
    {
        $helpText = <<<EOT
Saved GitHub Commands:
1. for login:
    php artisan login

2. for logout:
    php artisan logout

3. for showing repositories:
    php artisan repo

4. for showing branches:
    php artisan branch <user id>/<repo name>

5. Show contents of a branch:
   php artisan show-branch <user id>/<repo> <branch>
   example:php artisan show-branch akashverma3333/laravelgithubapis main 

4. Edit a file in VS Code:
   php artisan edit-branch-file <user id>/<repo> <branch> <file-path>
   example:php artisan edit-branch-file akashverma3333/laravelgithubapis main src/Commands/RepoCommand.php

5. Save all Git commands for reference:
   php artisan showallcommands
EOT;

        $this->info($helpText);
    }
}
