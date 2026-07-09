<?php

namespace src;
use RuntimeException;

/**
 * GitManager – handles cloning, writing files, committing and pushing.
 */
class GitManager
{
    private string $workDir;
    private string $repoUrl;
    private string $branch;

    public function __construct(string $repoUrl, string $branch = 'main')
    {
        $this->repoUrl = $repoUrl;
        $this->branch = $branch;
        $this->workDir = sys_get_temp_dir() . '/ai_dev_agent_' . uniqid();
    }

    public function getWorkDir(): string
    {
        return $this->workDir;
    }

    public function clone(): void
    {
        $url = $this->repoUrl;
        $token = getenv('GIT_TOKEN');
        if ($token && str_starts_with($url, 'https://')) {
            $url = preg_replace('#https://#', "https://{$token}@", $url);
        }

        $url    = escapeshellarg($url);
        $dir    = escapeshellarg($this->workDir);
        $branch = escapeshellarg($this->branch);

        $this->exec("git clone --depth=1 --branch {$branch} {$url} {$dir} 2>&1");
    }

    public function writeFile(string $relativePath, string $content): void
    {
        // Sanitize path – prevent traversal
        $relativePath = ltrim(str_replace('..', '', $relativePath), '/\\');
        $fullPath = $this->workDir . '/' . $relativePath;
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);
    }

    public function commitAndPush(string $message): void
    {
        $wd = escapeshellarg($this->workDir);
        $msg = escapeshellarg($message);
        $br = escapeshellarg($this->branch);

        $this->exec("git -C {$wd} config user.email 'ai-agent@localhost'");
        $this->exec("git -C {$wd} config user.name 'AI Dev Agent'");
        $this->exec("git -C {$wd} add -A 2>&1");
        $this->exec("git -C {$wd} commit -m {$msg} 2>&1");
        $this->exec("git -C {$wd} push origin {$br} 2>&1");
    }

    /** Remove the temp directory. */
    public function cleanup(): void
    {
        if (is_dir($this->workDir)) {
            $this->exec("rm -rf " . escapeshellarg($this->workDir) . " 2>&1");
        }
    }


    private function exec(string $cmd): string
    {
        exec($cmd, $out, $code);
        $output = implode("\n", $out);
        if ($code !== 0) {
            throw new RuntimeException("Git command failed (exit {$code}):\n{$cmd}\n{$output}");
        }
        return $output;
    }
}
