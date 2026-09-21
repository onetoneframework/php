<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Repository;

use Clover\Classes\OperationSystem;
use RuntimeException;
use function count;
use function sprintf;
use function is_array;
use function in_array;

class Git
{
    /**
     * Execute a shell command and return trimmed output lines.
     * Throws RuntimeException if the exit code is non-zero and $throw is true.
     *
     * @param string $cmd     Shell command to run
     * @param bool   $throw   Whether to throw on non-zero exit code
     * @return array{output: string[], rc: int}
     */
    private static function run(string $cmd, bool $throw = true): array
    {
        $output = [];
        $rc = 0;
        exec($cmd . ' 2>&1', $output, $rc);

        if ($throw && $rc !== 0) {
            throw new RuntimeException("Git command failed (exit {$rc}): {$cmd}\n" . implode("\n", $output));
        }

        return ['output' => $output, 'rc' => $rc];
    }

    /**
     * Assert that git is installed and reachable on PATH.
     *
     * @throws RuntimeException if git binary is not found
     */
    private static function assertGitExists(): void
    {
        $result = self::run('git --version', false);
        if ($result['rc'] !== 0) {
            throw new RuntimeException('git command not found. Ensure git is installed and in PATH.');
        }
    }

    /**
     * Assert that the current working directory is inside a git work-tree.
     *
     * @throws RuntimeException if not inside a git repository
     */
    private static function assertInsideRepo(): void
    {
        $result = self::run('git rev-parse --is-inside-work-tree', false);
        if ($result['rc'] !== 0 || empty($result['output']) || trim($result['output'][0]) !== 'true') {
            throw new RuntimeException('Current directory is not a Git repository.');
        }
    }

    /**
     * Return true if the current working directory is inside a git repository.
     * 
     * @return bool
     */
    public static function isGitRepository(): bool
    {
        $result = self::run('git rev-parse --is-inside-work-tree', false);
        return $result['rc'] === 0 && !empty($result['output']) && trim($result['output'][0]) === 'true';
    }

    /**
     * Return the absolute path to the root of the current git repository.
     *
     * @throws RuntimeException
     */
    public static function getRepositoryRoot(): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git rev-parse --show-toplevel');
        return trim($result['output'][0]);
    }

    /**
     * Return the installed git version string (e.g. "2.43.0").
     *
     * @throws RuntimeException
     */
    public static function getVersion(): string
    {
        $result = self::run('git --version');
        // "git version 2.43.0" -> "2.43.0"
        if (preg_match('/git version\s+([\d.]+)/i', $result['output'][0], $m)) {
            return $m[1];
        }

        return trim($result['output'][0]);
    }

    /**
     * Return the number of changed files in the working tree.
     */
    public static function getChangedFileCount(): int
    {
        $status = OperationSystem::executeShell("git status --porcelain");
        if (!$status) {
            return 0;
        }

        return count(explode("\n", $status)) - 1;
    }

    // -------------------------------------------------------------------------
    // Branch Operations
    // -------------------------------------------------------------------------

    /**
     * Return the name of the currently checked-out branch.
     * Returns "HEAD" when in detached HEAD state.
     *
     * @throws RuntimeException
     */
    public static function getCurrentBranch(): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git rev-parse --abbrev-ref HEAD');
        return trim($result['output'][0]);
    }

    /**
     * Return all local branch names.
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function getBranches(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git branch --format=%(refname:short)');
        return array_values(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Return all remote-tracking branch names (e.g. "origin/main").
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function getRemoteBranches(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git branch -r --format=%(refname:short)');
        return array_values(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Return every branch name (local + remote).
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function getAllBranches(): array
    {
        return array_unique(array_merge(self::getBranches(), self::getRemoteBranches()));
    }

    /**
     * Return true if a local branch with the given name exists.
     *
     * @throws RuntimeException
     */
    public static function branchExists(string $branch): bool
    {
        $result = self::run(sprintf('git rev-parse --verify %s', escapeshellarg($branch)), false);
        return $result['rc'] === 0;
    }

    /**
     * Create a new local branch, optionally from a start point (commit/branch/tag).
     *
     * @param string      $branch     Name for the new branch
     * @param string|null $startPoint Starting commit/branch/tag (default: current HEAD)
     * @throws RuntimeException
     */
    public static function createBranch(string $branch, ?string $startPoint = null): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $cmd = sprintf('git branch %s', escapeshellarg($branch));
        if ($startPoint !== null) {
            $cmd .= ' ' . escapeshellarg($startPoint);
        }
        self::run($cmd);
    }

    /**
     * Delete a local branch.
     *
     * @param string $branch Branch name to delete
     * @param bool   $force  Use -D (force delete) instead of -d
     * @throws RuntimeException
     */
    public static function deleteBranch(string $branch, bool $force = false): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $flag = $force ? '-D' : '-d';
        self::run(sprintf('git branch %s %s', $flag, escapeshellarg($branch)));
    }

    /**
     * Rename a local branch.
     *
     * @param string      $newName New branch name
     * @param string|null $oldName Old branch name (default: current branch)
     * @throws RuntimeException
     */
    public static function renameBranch(string $newName, ?string $oldName = null): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        if ($oldName !== null) {
            $cmd = sprintf('git branch -m %s %s', escapeshellarg($oldName), escapeshellarg($newName));
        } else {
            $cmd = sprintf('git branch -m %s', escapeshellarg($newName));
        }
        self::run($cmd);
    }

    /**
     * Checkout an existing branch or commit.
     *
     * @param string $target Branch name, commit hash, or tag
     * @throws RuntimeException
     */
    public static function checkoutBranch(string $target): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git checkout %s', escapeshellarg($target)));
    }

    /**
     * Create and immediately checkout a new branch.
     *
     * @param string      $branch     New branch name
     * @param string|null $startPoint Optional start point
     * @throws RuntimeException
     */
    public static function checkoutNewBranch(string $branch, ?string $startPoint = null): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $cmd = sprintf('git checkout -b %s', escapeshellarg($branch));
        if ($startPoint !== null) {
            $cmd .= ' ' . escapeshellarg($startPoint);
        }
        self::run($cmd);
    }

    /**
     * Try to determine the default (main) branch of the repository.
     * Checks common names: main, master, trunk, develop.
     *
     * @throws RuntimeException
     */
    public static function getDefaultBranch(): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        // Try reading from remote HEAD symbolic-ref
        $result = self::run('git symbolic-ref refs/remotes/origin/HEAD', false);
        if ($result['rc'] === 0 && !empty($result['output'])) {
            // refs/remotes/origin/main -> main
            return trim(str_replace('refs/remotes/origin/', '', $result['output'][0]));
        }

        // Fallback: check well-known branch names
        foreach (['main', 'master', 'trunk', 'develop'] as $candidate) {
            if (self::branchExists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to determine the default branch.');
    }

    // -------------------------------------------------------------------------
    // Commit Information
    // -------------------------------------------------------------------------

    /**
     * Return the full SHA-1 hash of the latest commit on HEAD (or a given ref).
     *
     * @param string $ref Git ref to resolve (default: HEAD)
     * @throws RuntimeException
     */
    public static function getLastCommitHash(string $ref = 'HEAD'): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git rev-parse %s', escapeshellarg($ref)));
        return trim($result['output'][0]);
    }

    /**
     * Return the abbreviated (short) commit hash for a given ref.
     *
     * @param string $ref Git ref (default: HEAD)
     * @throws RuntimeException
     */
    public static function getLastCommitShortHash(string $ref = 'HEAD'): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git rev-parse --short %s', escapeshellarg($ref)));
        return trim($result['output'][0]);
    }

    /**
     * Return the commit message of the most recent commit (or a given ref).
     *
     * @param string $ref Git ref (default: HEAD)
     * @throws RuntimeException
     */
    public static function getLastCommitMessage(string $ref = 'HEAD'): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git log -1 --pretty=format:%%s %s', escapeshellarg($ref)));
        return trim(implode(' ', $result['output']));
    }

    /**
     * Return the author name of the most recent commit.
     *
     * @param string $ref Git ref (default: HEAD)
     * @throws RuntimeException
     */
    public static function getLastCommitAuthor(string $ref = 'HEAD'): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git log -1 --pretty=format:%%an %s', escapeshellarg($ref)));
        return trim(implode('', $result['output']));
    }

    /**
     * Return the author email of the most recent commit.
     *
     * @param string $ref Git ref (default: HEAD)
     * @throws RuntimeException
     */
    public static function getLastCommitAuthorEmail(string $ref = 'HEAD'): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git log -1 --pretty=format:%%ae %s', escapeshellarg($ref)));
        return trim(implode('', $result['output']));
    }

    /**
     * Return the ISO-8601 commit date/time of the most recent commit.
     *
     * @param string $ref Git ref (default: HEAD)
     * @throws RuntimeException
     */
    public static function getLastCommitDate(string $ref = 'HEAD'): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git log -1 --pretty=format:%%cI %s', escapeshellarg($ref)));
        return trim(implode('', $result['output']));
    }

    /**
     * Return the total number of commits reachable from HEAD (or a given ref).
     *
     * @param string $ref Git ref to count from (default: HEAD)
     * @throws RuntimeException
     */
    public static function getTotalCommitCount(string $ref = 'HEAD'): int
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git rev-list --count %s', escapeshellarg($ref)));
        return (int) trim($result['output'][0]);
    }

    /**
     * Return structured commit log entries.
     *
     * Each entry is an associative array with keys:
     *   hash, short_hash, author, email, date, message
     *
     * @param int    $limit Maximum number of commits to return (0 = all)
     * @param string $ref   Starting ref (default: HEAD)
     * @return array<int, array<string, string>>
     * @throws RuntimeException
     */
    public static function getCommitLog(int $limit = 20, string $ref = 'HEAD'): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        // Use a unique separator to reliably split fields
        $sep = '|||';
        $format = implode($sep, ['%H', '%h', '%an', '%ae', '%cI', '%s']);
        $limitFlag = $limit > 0 ? sprintf('-n %d', $limit) : '';

        $result = self::run(
            sprintf('git log %s --pretty=format:%s %s', $limitFlag, escapeshellarg($format), escapeshellarg($ref))
        );

        $entries = [];
        foreach ($result['output'] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode($sep, $line, 6);
            if (count($parts) < 6) {
                continue;
            }
            $entries[] = [
                'hash' => $parts[0],
                'short_hash' => $parts[1],
                'author' => $parts[2],
                'email' => $parts[3],
                'date' => $parts[4],
                'message' => $parts[5],
            ];
        }

        return $entries;
    }

    /**
     * Return details of a specific commit by its hash.
     *
     * @param string $hash Full or abbreviated commit hash
     * @return array<string, string> Keys: hash, short_hash, author, email, date, message, body
     * @throws RuntimeException
     */
    public static function getCommitInfo(string $hash): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $sep = '|||';
        $format = implode($sep, ['%H', '%h', '%an', '%ae', '%cI', '%s', '%b']);
        $result = self::run(
            sprintf('git show -s --pretty=format:%s %s', escapeshellarg($format), escapeshellarg($hash))
        );

        $line = implode("\n", $result['output']);
        $parts = explode($sep, $line, 7);
        if (count($parts) < 6) {
            throw new RuntimeException("Unable to parse commit info for: {$hash}");
        }

        return [
            'hash' => $parts[0],
            'short_hash' => $parts[1],
            'author' => $parts[2],
            'email' => $parts[3],
            'date' => $parts[4],
            'message' => $parts[5],
            'body' => $parts[6] ?? '',
        ];
    }

    /**
     * Return the number of commits the current branch is ahead of and behind its upstream.
     *
     * @return array{ahead: int, behind: int}
     * @throws RuntimeException
     */
    public static function getAheadBehindCount(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git rev-list --left-right --count HEAD...@{u}', false);
        if ($result['rc'] !== 0) {
            throw new RuntimeException('Unable to compute ahead/behind count. Is an upstream configured?');
        }

        $parts = preg_split('/\s+/', trim($result['output'][0] ?? ''));
        return [
            'ahead' => (int) ($parts[0] ?? 0),
            'behind' => (int) ($parts[1] ?? 0),
        ];
    }

    /**
     * Return number of commits that would be pulled from remote (remote-only commits).
     *
     * @param string $remote  Remote name (default "origin")
     * @param bool   $doFetch Whether to run `git fetch` before counting (default false)
     * @return int Number of commits on remote not present locally
     * @throws RuntimeException on errors (not a git repo, no remote, command failure)
     */
    public static function getPullCommitCount(string $remote = 'origin', bool $doFetch = false): int
    {
        $out = [];
        $rc = 0;
        exec('git --version 2>&1', $out, $rc);
        if ($rc != 0) {
            throw new RuntimeException('git command not found. Ensure Git for Windows is installed and git is in your PATH.');
        }

        $out = [];
        exec('git rev-parse --is-inside-work-tree 2>&1', $out, $rc);
        if ($rc != 0 || empty($out) || trim($out[0]) !== 'true') {
            throw new RuntimeException('Current directory is not a Git repository.');
        }

        exec('git rev-parse --abbrev-ref HEAD 2>&1', $branchOut, $rc);
        if ($rc != 0 || empty($branchOut)) {
            throw new RuntimeException('Unable to determine the current branch.');
        }
        $branch = trim($branchOut[0]);
        if ($branch === 'HEAD') {
            throw new RuntimeException('Detached HEAD state. Specify a branch to compare explicitly.');
        }

        if ($doFetch) {
            exec(sprintf('git fetch %s 2>&1', escapeshellarg($remote)), $fetchOut, $fetchRc);
            if ($fetchRc !== 0) {
                throw new RuntimeException('Git fetch failed: ' . implode("\n", $fetchOut));
            }
        }

        exec('git rev-parse --abbrev-ref --symbolic-full-name @{u} 2>&1', $upstreamOut, $upstreamRc);
        if ($upstreamRc === 0 && !empty($upstreamOut)) {
            $upstream = trim($upstreamOut[0]);
        } else {
            $upstream = $remote . '/' . $branch;
            exec(sprintf('git rev-parse --verify --quiet refs/remotes/%s 2>&1', str_replace('/', '\\/', $upstream)), $verifyOut, $verifyRc);
            if ($verifyRc !== 0) {
                throw new RuntimeException("Upstream not found: {$upstream}. The branch may not exist on the remote or no upstream is configured.");
            }
        }

        $cmd = sprintf('git rev-list --count %s..%s 2>&1', escapeshellarg('HEAD'), escapeshellarg($upstream));
        exec($cmd, $countOut, $countRc);
        if ($countRc !== 0 || !isset($countOut[0])) {
            throw new RuntimeException('Failed to count commits: ' . implode("\n", $countOut));
        }

        return (int) trim($countOut[0]);
    }

    // -------------------------------------------------------------------------
    // Staging & Working Tree
    // -------------------------------------------------------------------------

    /**
     * Return true if the working tree and index are clean (no changes, no untracked files).
     *
     * @throws RuntimeException
     */
    public static function isClean(): bool
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git status --porcelain');
        return empty(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Return true if there are uncommitted changes (staged or unstaged).
     *
     * @throws RuntimeException
     */
    public static function hasUncommittedChanges(): bool
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git status --porcelain');
        return !empty(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Return a list of files currently staged for the next commit.
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function getStagedFiles(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git diff --cached --name-only');
        return array_values(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Return a list of modified (unstaged) files in the working tree.
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function getModifiedFiles(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git diff --name-only');
        return array_values(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Return a list of untracked files in the working tree.
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function getUntrackedFiles(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git ls-files --others --exclude-standard');
        return array_values(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Return a list of files tracked by git that have been deleted from disk.
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function getDeletedFiles(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git ls-files --deleted');
        return array_values(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Stage one or more files (equivalent to `git add`).
     *
     * @param string|string[] $paths File path(s) to stage
     * @throws RuntimeException
     */
    public static function stageFile(string|array $paths): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $targets = is_array($paths) ? $paths : [$paths];
        $escaped = implode(' ', array_map('escapeshellarg', $targets));
        self::run("git add {$escaped}");
    }

    /**
     * Stage all changes in the working tree (`git add -A`).
     *
     * @throws RuntimeException
     */
    public static function stageAll(): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run('git add -A');
    }

    /**
     * Unstage a file from the index without altering the working tree.
     *
     * @param string $path File path to unstage
     * @throws RuntimeException
     */
    public static function unstageFile(string $path): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git restore --staged %s', escapeshellarg($path)));
    }

    // -------------------------------------------------------------------------
    // Commit Actions
    // -------------------------------------------------------------------------

    /**
     * Create a new commit with the staged changes.
     *
     * @param string $message Commit message
     * @param bool   $allowEmpty Allow a commit with no staged changes
     * @throws RuntimeException
     */
    public static function commit(string $message, bool $allowEmpty = false): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $emptyFlag = $allowEmpty ? ' --allow-empty' : '';
        self::run(sprintf('git commit%s -m %s', $emptyFlag, escapeshellarg($message)));
    }

    /**
     * Amend the most recent commit message without altering its content.
     *
     * @param string $newMessage New commit message
     * @throws RuntimeException
     */
    public static function amendLastCommitMessage(string $newMessage): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git commit --amend -m %s', escapeshellarg($newMessage)));
    }

    /**
     * Reset HEAD to a specific commit.
     *
     * @param string $ref    Target commit hash or ref (default: HEAD~1)
     * @param string $mode   Reset mode: 'soft' | 'mixed' | 'hard'
     * @throws RuntimeException
     */
    public static function reset(string $ref = 'HEAD~1', string $mode = 'mixed'): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $allowed = ['soft', 'mixed', 'hard'];
        if (!in_array($mode, $allowed, true)) {
            throw new RuntimeException("Invalid reset mode '{$mode}'. Use: soft, mixed, or hard.");
        }
        self::run(sprintf('git reset --%s %s', $mode, escapeshellarg($ref)));
    }

    /**
     * Discard all local changes to a file and restore it from the index or HEAD.
     *
     * @param string $path Path to the file to restore
     * @throws RuntimeException
     */
    public static function discardFileChanges(string $path): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git checkout -- %s', escapeshellarg($path)));
    }

    /**
     * Cherry-pick one or more commits onto the current branch.
     *
     * @param string|string[] $hashes Commit hash(es) to cherry-pick
     * @throws RuntimeException
     */
    public static function cherryPick(string|array $hashes): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $targets = is_array($hashes) ? $hashes : [$hashes];
        $escaped = implode(' ', array_map('escapeshellarg', $targets));
        self::run("git cherry-pick {$escaped}");
    }

    /**
     * Merge a branch into the current branch.
     *
     * @param string      $branch     Branch to merge
     * @param string|null $message    Optional merge commit message
     * @param bool        $noFastForward Force a merge commit even when fast-forward is possible
     * @throws RuntimeException
     */
    public static function merge(string $branch, ?string $message = null, bool $noFastForward = false): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $cmd = sprintf('git merge %s', escapeshellarg($branch));
        if ($noFastForward) {
            $cmd .= ' --no-ff';
        }
        if ($message !== null) {
            $cmd .= ' -m ' . escapeshellarg($message);
        }
        self::run($cmd);
    }

    /**
     * Rebase the current branch onto another branch or commit.
     *
     * @param string $onto Target branch or commit to rebase onto
     * @throws RuntimeException
     */
    public static function rebase(string $onto): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git rebase %s', escapeshellarg($onto)));
    }

    /**
     * Return the unified diff of unstaged changes in the working tree.
     *
     * @throws RuntimeException
     */
    public static function getDiff(): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git diff');
        return implode("\n", $result['output']);
    }

    /**
     * Return the unified diff of staged (index) changes.
     *
     * @throws RuntimeException
     */
    public static function getStagedDiff(): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git diff --cached');
        return implode("\n", $result['output']);
    }

    /**
     * Return the unified diff for a specific file (unstaged by default).
     *
     * @param string $path   File path relative to repository root
     * @param bool   $staged Whether to diff against the index instead of the working tree
     * @throws RuntimeException
     */
    public static function getFileDiff(string $path, bool $staged = false): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $stagedFlag = $staged ? '--cached ' : '';
        $result = self::run(sprintf('git diff %s-- %s', $stagedFlag, escapeshellarg($path)));
        return implode("\n", $result['output']);
    }

    /**
     * Return the diff between two refs (commits, branches, tags).
     *
     * @param string $from First ref
     * @param string $to   Second ref (default: HEAD)
     * @throws RuntimeException
     */
    public static function getDiffBetween(string $from, string $to = 'HEAD'): string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git diff %s %s', escapeshellarg($from), escapeshellarg($to)));
        return implode("\n", $result['output']);
    }

    /**
     * Return a structured commit history for a specific file.
     *
     * Each entry contains: hash, short_hash, author, date, message
     *
     * @param string $path  File path relative to repository root
     * @param int    $limit Max commits to return (0 = all)
     * @return array<int, array<string, string>>
     * @throws RuntimeException
     */
    public static function getFileHistory(string $path, int $limit = 20): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $sep = '|||';
        $format = implode($sep, ['%H', '%h', '%an', '%cI', '%s']);
        $limitFlag = $limit > 0 ? sprintf('-n %d', $limit) : '';

        $result = self::run(
            sprintf('git log %s --pretty=format:%s -- %s', $limitFlag, escapeshellarg($format), escapeshellarg($path))
        );

        $entries = [];
        foreach ($result['output'] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode($sep, $line, 5);
            if (count($parts) < 5) {
                continue;
            }
            $entries[] = [
                'hash' => $parts[0],
                'short_hash' => $parts[1],
                'author' => $parts[2],
                'date' => $parts[3],
                'message' => $parts[4],
            ];
        }

        return $entries;
    }

    /**
     * Return blame information for a file as an array of line records.
     *
     * Each record contains: line_number, hash, author, date, content
     *
     * @param string $path File path relative to repository root
     * @return array<array|array{hash: mixed, line_number: int}|string>
     * @throws RuntimeException
     */
    public static function getBlame(string $path): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git blame --line-porcelain %s', escapeshellarg($path)));

        $entries = [];
        $lineNumber = 0;
        /**
         * @var array{hash: mixed, line_number: int}|string
         */
        $current = [];

        foreach ($result['output'] as $line) {
            // A porcelain boundary line starts with a 40-char hex hash followed by line numbers
            if (preg_match('/^([0-9a-f]{40})\s+\d+\s+(\d+)/', $line, $m)) {
                if (!empty($current)) {
                    $entries[] = $current;
                }
                $lineNumber = (int) $m[2];
                $current = ['line_number' => $lineNumber, 'hash' => $m[1]];
            } elseif (str_starts_with($line, 'author ')) {
                $current['author'] = substr($line, 7);
            } elseif (str_starts_with($line, 'author-time ')) {
                $current['date'] = date('Y-m-d H:i:s', (int) substr($line, 12));
            } elseif (str_starts_with($line, "\t")) {
                // The actual line content starts with a TAB
                $current['content'] = substr($line, 1);
            }
        }

        if (!empty($current)) {
            $entries[] = $current;
        }

        return $entries;
    }

    /**
     * Return all tag names in the repository, sorted by version (newest first).
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function getTags(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git tag --sort=-version:refname');
        return array_values(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Return the most recent tag reachable from HEAD, or null if none exists.
     *
     * @throws RuntimeException
     */
    public static function getLatestTag(): ?string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git describe --tags --abbrev=0', false);
        if ($result['rc'] !== 0 || empty($result['output'])) {
            return null;
        }

        return trim($result['output'][0]);
    }

    /**
     * Create a new lightweight or annotated tag.
     *
     * @param string      $name    Tag name
     * @param string|null $message If provided, creates an annotated tag
     * @param string      $ref     Ref to tag (default: HEAD)
     * @throws RuntimeException
     */
    public static function createTag(string $name, ?string $message = null, string $ref = 'HEAD'): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        if ($message !== null) {
            $cmd = sprintf('git tag -a %s -m %s %s', escapeshellarg($name), escapeshellarg($message), escapeshellarg($ref));
        } else {
            $cmd = sprintf('git tag %s %s', escapeshellarg($name), escapeshellarg($ref));
        }
        self::run($cmd);
    }

    /**
     * Delete a local tag.
     *
     * @param string $name Tag name to delete
     * @throws RuntimeException
     */
    public static function deleteTag(string $name): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git tag -d %s', escapeshellarg($name)));
    }

    /**
     * Return true if a tag with the given name exists locally.
     *
     * @throws RuntimeException
     */
    public static function tagExists(string $name): bool
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git tag -l %s', escapeshellarg($name)), false);
        return !empty(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Return all configured remote names.
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function getRemotes(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git remote');
        return array_values(array_filter(array_map('trim', $result['output'])));
    }

    /**
     * Return the repository remote URL for the current working directory.
     *
     * @param string|null $preferredRemote Optional preferred remote name (default: 'origin')
     * @return string
     * @throws RuntimeException
     */
    public static function getRepositoryUrl(?string $preferredRemote = 'origin'): string
    {
        $out = [];
        $rc = 0;
        exec('git --version 2>&1', $out, $rc);
        if ($rc !== 0) {
            throw new RuntimeException('git command not found. Ensure git is installed and in PATH.');
        }

        if ($preferredRemote) {
            $cmd = sprintf('git remote get-url %s 2>&1', escapeshellarg($preferredRemote));
            $out = [];
            exec($cmd, $out, $rc);
            if ($rc === 0 && !empty($out) && trim($out[0]) !== '') {
                return trim($out[0]);
            }
        }

        exec('git remote 2>&1', $remotes, $rc);
        if ($rc === 0 && !empty($remotes)) {
            $first = trim($remotes[0]);
            if ($first !== '') {
                $cmd = sprintf('git remote get-url %s 2>&1', escapeshellarg($first));
                exec($cmd, $out, $rc);
                if ($rc === 0 && !empty($out) && trim($out[0]) !== '') {
                    return trim($out[0]);
                }
            }
        }

        // fallback: parse .git/config if present
        $gitConfigPath = getcwd() . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . 'config';
        if (is_file($gitConfigPath) && is_readable($gitConfigPath)) {
            $config = file_get_contents($gitConfigPath);
            if ($config !== false) {
                // find first [remote "name"] section and its url = line
                if (preg_match('/\[remote\s+"([^"]+)"\][^\[]*?url\s*=\s*(.+)/i', $config, $m)) {
                    $url = trim($m[2]);
                    if ($url !== '') {
                        return $url;
                    }
                }
            }
        }

        throw new RuntimeException('Unable to determine repository remote URL. Ensure you are in a git repository and a remote is configured.');
    }

    /**
     * Add a new remote.
     *
     * @param string $name Remote name (e.g. "upstream")
     * @param string $url  Remote URL
     * @throws RuntimeException
     */
    public static function addRemote(string $name, string $url): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git remote add %s %s', escapeshellarg($name), escapeshellarg($url)));
    }

    /**
     * Remove an existing remote.
     *
     * @param string $name Remote name to remove
     * @throws RuntimeException
     */
    public static function removeRemote(string $name): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git remote remove %s', escapeshellarg($name)));
    }

    /**
     * Update the URL of an existing remote.
     *
     * @param string $name Remote name
     * @param string $url  New URL
     * @throws RuntimeException
     */
    public static function setRemoteUrl(string $name, string $url): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git remote set-url %s %s', escapeshellarg($name), escapeshellarg($url)));
    }

    /**
     * Fetch from a remote without merging.
     *
     * @param string $remote Remote name (default: "origin")
     * @param bool   $prune  Prune deleted remote-tracking branches
     * @throws RuntimeException
     */
    public static function fetch(string $remote = 'origin', bool $prune = false): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $pruneFlag = $prune ? ' --prune' : '';
        self::run(sprintf('git fetch%s %s', $pruneFlag, escapeshellarg($remote)));
    }

    /**
     * Pull (fetch + merge/rebase) from the configured upstream.
     *
     * @param string $remote Remote name (default: "origin")
     * @param string $branch Branch name (default: current branch)
     * @param bool   $rebase Use --rebase instead of merge
     * @throws RuntimeException
     */
    public static function pull(string $remote = 'origin', string $branch = '', bool $rebase = false): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $rebaseFlag = $rebase ? ' --rebase' : '';
        $branchPart = $branch !== '' ? ' ' . escapeshellarg($branch) : '';
        self::run(sprintf('git pull%s %s%s', $rebaseFlag, escapeshellarg($remote), $branchPart));
    }

    /**
     * Push the current branch to a remote.
     *
     * @param string $remote      Remote name (default: "origin")
     * @param string $branch      Branch to push (default: current branch)
     * @param bool   $setUpstream Set tracking upstream (-u flag)
     * @param bool   $force       Force-push (--force-with-lease for safety)
     * @throws RuntimeException
     */
    public static function push(string $remote = 'origin', string $branch = '', bool $setUpstream = false, bool $force = false): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        if ($branch === '') {
            $branch = self::getCurrentBranch();
        }

        $flags = '';
        if ($setUpstream) {
            $flags .= ' -u';
        }
        if ($force) {
            $flags .= ' --force-with-lease';
        }

        self::run(sprintf('git push%s %s %s', $flags, escapeshellarg($remote), escapeshellarg($branch)));
    }

    /**
     * Push all local tags to a remote.
     *
     * @param string $remote Remote name (default: "origin")
     * @throws RuntimeException
     */
    public static function pushTags(string $remote = 'origin'): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git push %s --tags', escapeshellarg($remote)));
    }

    // -------------------------------------------------------------------------
    // Stash Operations
    // -------------------------------------------------------------------------

    /**
     * Stash the current working-tree and index changes.
     *
     * @param string|null $message Optional stash description
     * @param bool        $includeUntracked Also stash untracked files
     * @throws RuntimeException
     */
    public static function stash(?string $message = null, bool $includeUntracked = false): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $cmd = 'git stash push';
        if ($includeUntracked) {
            $cmd .= ' -u';
        }
        if ($message !== null) {
            $cmd .= ' -m ' . escapeshellarg($message);
        }
        self::run($cmd);
    }

    /**
     * Apply and remove the most recent stash entry (or a specific one).
     *
     * @param string $stashRef Stash ref (default: "stash@{0}")
     * @throws RuntimeException
     */
    public static function stashPop(string $stashRef = 'stash@{0}'): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git stash pop %s', escapeshellarg($stashRef)));
    }

    /**
     * Apply a stash entry without removing it from the stash list.
     *
     * @param string $stashRef Stash ref (default: "stash@{0}")
     * @throws RuntimeException
     */
    public static function stashApply(string $stashRef = 'stash@{0}'): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git stash apply %s', escapeshellarg($stashRef)));
    }

    /**
     * Drop (delete) a stash entry without applying it.
     *
     * @param string $stashRef Stash ref (default: "stash@{0}")
     * @throws RuntimeException
     */
    public static function stashDrop(string $stashRef = 'stash@{0}'): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git stash drop %s', escapeshellarg($stashRef)));
    }

    /**
     * Return a list of stash entries with their index and description.
     *
     * @return array<int, array{index: int, ref: string, description: string}>
     * @throws RuntimeException
     */
    public static function stashList(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run('git stash list');
        $entries = [];

        foreach ($result['output'] as $i => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            // Format: "stash@{N}: On branch: message"
            preg_match('/^(stash@\{\d+\}):\s*(.+)$/', $line, $m);
            $entries[] = [
                'index' => $i,
                'ref' => $m[1] ?? "stash@{{$i}}",
                'description' => $m[2] ?? $line,
            ];
        }

        return $entries;
    }

    /**
     * Remove all stash entries.
     *
     * @throws RuntimeException
     */
    public static function stashClear(): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run('git stash clear');
    }

    // -------------------------------------------------------------------------
    // Contributors & Stats
    // -------------------------------------------------------------------------

    /**
     * Return all contributors with their commit counts, sorted by count descending.
     *
     * @return array<int, array{author: string, email: string, commits: int}>
     * @throws RuntimeException
     */
    public static function getContributors(): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run("git log --pretty=format:'%an|||%ae'");
        $counts = [];

        foreach ($result['output'] as $line) {
            $line = trim($line, "' ");
            if ($line === '') {
                continue;
            }
            $parts = explode('|||', $line, 2);
            if (count($parts) < 2) {
                continue;
            }
            $key = $parts[0] . '|||' . $parts[1];
            if (!isset($counts[$key])) {
                $counts[$key] = ['author' => $parts[0], 'email' => $parts[1], 'commits' => 0];
            }
            $counts[$key]['commits']++;
        }

        usort($counts, static fn($a, $b) => $b['commits'] - $a['commits']);
        return array_values($counts);
    }

    /**
     * Return the number of commits made by a specific author (matched by name or email substring).
     *
     * @param string $author Author name or email to search
     * @throws RuntimeException
     */
    public static function getCommitCountByAuthor(string $author): int
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(
            sprintf('git rev-list --count --author=%s HEAD', escapeshellarg($author))
        );
        return (int) trim($result['output'][0]);
    }

    /**
     * Return per-file insertion/deletion statistics for a commit.
     *
     * @param string $ref Commit hash or ref (default: HEAD)
     * @return array<int, array{file: string, insertions: int, deletions: int}>
     * @throws RuntimeException
     */
    public static function getCommitStats(string $ref = 'HEAD'): array
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git show --stat --format= %s', escapeshellarg($ref)));
        $entries = [];

        foreach ($result['output'] as $line) {
            $line = trim($line);
            // "src/Foo.php | 12 ++-"
            if (preg_match('/^(.+?)\s+\|\s+(\d+)\s*([\+\-]*)$/', $line, $m)) {
                $ins = substr_count($m[3], '+');
                $del = substr_count($m[3], '-');
                $entries[] = [
                    'file' => trim($m[1]),
                    'insertions' => $ins,
                    'deletions' => $del,
                ];
            }
        }

        return $entries;
    }

    /**
     * Initialize a new git repository in the given directory.
     * If $path is null, initializes in the current working directory.
     *
     * @param string|null $path Target directory (null = cwd)
     * @throws RuntimeException
     */
    public static function initRepository(?string $path = null): void
    {
        self::assertGitExists();

        $cmd = 'git init';
        if ($path !== null) {
            $cmd .= ' ' . escapeshellarg($path);
        }
        self::run($cmd);
    }

    /**
     * Clone a remote repository into a local directory.
     *
     * @param string      $url    Repository URL to clone
     * @param string|null $target Local directory name (null = git default)
     * @param int         $depth  Shallow clone depth (0 = full clone)
     * @throws RuntimeException
     */
    public static function cloneRepository(string $url, ?string $target = null, int $depth = 0): void
    {
        self::assertGitExists();

        $cmd = 'git clone';
        if ($depth > 0) {
            $cmd .= sprintf(' --depth %d', $depth);
        }
        $cmd .= ' ' . escapeshellarg($url);
        if ($target !== null) {
            $cmd .= ' ' . escapeshellarg($target);
        }
        self::run($cmd);
    }

    /**
     * Set a local git configuration value (git config --local key value).
     *
     * @param string $key   Config key, e.g. "user.email"
     * @param string $value Config value
     * @throws RuntimeException
     */
    public static function setConfig(string $key, string $value): void
    {
        self::assertGitExists();
        self::assertInsideRepo();

        self::run(sprintf('git config --local %s %s', escapeshellarg($key), escapeshellarg($value)));
    }

    /**
     * Get a local git configuration value.
     * Returns null when the key is not set.
     *
     * @param string $key Config key, e.g. "user.email"
     * @throws RuntimeException
     */
    public static function getConfig(string $key): ?string
    {
        self::assertGitExists();
        self::assertInsideRepo();

        $result = self::run(sprintf('git config --local %s', escapeshellarg($key)), false);
        if ($result['rc'] !== 0 || empty($result['output'])) {
            return null;
        }

        return trim($result['output'][0]);
    }

    // -------------------------------------------------------------------------
    // License & GitHub Helpers (original methods preserved)
    // -------------------------------------------------------------------------

    /**
     * Detect and return the SPDX license identifier used in the repository.
     * Checks LICENSE files and composer.json / package.json as fallback.
     */
    public static function getMainLicense(): string
    {
        $files = ['LICENSE', 'LICENSE.md', 'LICENSE.txt'];
        foreach ($files as $f) {
            $content = [];
            exec("git show HEAD:{$f}", $content);
            $content = trim(trim($content[0] ?? '') . " " . trim($content[1] ?? ''));
            if ($content !== null && trim($content) !== '') {
                if (preg_match('/SPDX-License-Identifier:\s*([^\s]+)/i', $content, $m)) {
                    return $m[1];
                }
                if (preg_match('/\b(MIT|Apache-2.0|GPL-3.0|GPL-2.0|BSD-3-Clause|ISC|MPL-2.0|Unlicense)\b/i', $content, $m)) {
                    return $m[1];
                }
                if (preg_match('/GNU AFFERO GENERAL PUBLIC LICENSE Version ([\d.]+)/i', $content, $m)) {
                    return "AGPL v{$m[1]}";
                }
                return 'Custom';
            }
        }

        $composer = @shell_exec('git show HEAD:composer.json 2>/dev/null');
        if ($composer) {
            $j = json_decode($composer, true);
            if (!empty($j['license'])) {
                return is_array($j['license']) ? $j['license'][0] : $j['license'];
            }
        }

        $package = @shell_exec('git show HEAD:package.json 2>/dev/null');
        if ($package) {
            $p = json_decode($package, true);
            if (!empty($p['license'])) {
                return $p['license'];
            }
        }

        return 'unknown';
    }

    /**
     * Return GitHub star count for the repository referenced by the current git remote.
     *
     * @param string|null $token           Optional GitHub token to increase rate limits
     * @param string|null $preferredRemote Optional remote name (default 'origin')
     * @return int Star count (0 or greater)
     * @throws RuntimeException on error (no git, not a repo, cannot parse remote, API error)
     */
    public static function getStarCount(?string $token = null, ?string $preferredRemote = 'origin'): int
    {
        exec('git --version 2>&1', $out, $rc);
        if ($rc !== 0) {
            throw new RuntimeException('git command not found. Ensure git is installed and in PATH.');
        }

        $remoteUrl = null;
        if ($preferredRemote) {
            exec(sprintf('git remote get-url %s 2>&1', escapeshellarg($preferredRemote)), $out, $rc);
            if ($rc === 0 && !empty($out) && trim($out[0]) !== '') {
                $remoteUrl = trim($out[0]);
            }
        }

        if ($remoteUrl === null) {
            exec('git remote 2>&1', $remotes, $rc);
            if ($rc !== 0 || empty($remotes)) {
                throw new RuntimeException('No git remotes found.');
            }
            $first = trim($remotes[0]);
            exec(sprintf('git remote get-url %s 2>&1', escapeshellarg($first)), $out, $rc);
            if ($rc !== 0 || empty($out) || trim($out[0]) === '') {
                throw new RuntimeException('Unable to read remote URL.');
            }
            $remoteUrl = trim($out[0]);
        }

        // Supports: git@github.com:owner/repo.git  and  https://github.com/owner/repo.git
        $repo = preg_replace('#^git@[^:]+:#', '', $remoteUrl);
        $repo = preg_replace('#^https?://[^/]+/#', '', $repo);
        $repo = preg_replace('#\.git$#', '', $repo);
        $repo = trim($repo, "/ \t\n\r\0\x0B");
        if (strpos($repo, '/') === false) {
            throw new RuntimeException("Cannot parse owner/repo from remote URL: {$remoteUrl}");
        }

        $url = "https://api.github.com/repos/{$repo}";
        $ch = curl_init($url);
        $headers = [
            'User-Agent: php-client',
            'Accept: application/vnd.github+json',
        ];
        if (!empty($token)) {
            $headers[] = "Authorization: token {$token}";
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            curl_close($ch);
        }

        if ($body === false || $httpCode !== 200) {
            $msg = $curlErr ?: "GitHub API request failed with HTTP {$httpCode}";
            throw new RuntimeException($msg);
        }

        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['stargazers_count'])) {
            throw new RuntimeException('Unexpected response from GitHub API.');
        }

        return (int) $data['stargazers_count'];
    }
}
