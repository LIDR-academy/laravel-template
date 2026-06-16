#!/usr/bin/env php
<?php

/**
 * PreToolUse hook (Bash). Enforces the Definition of Done.
 * Tests run INSIDE the Docker stack (PCOV lives in the container, not on the host).
 *
 *   git commit     → blocked unless the Pest suite is green.
 *   gh pr create   → blocked unless the suite is green AND coverage ≥ 80%.
 *
 * This is ENFORCEMENT, not guidance: CLAUDE.md asks; this guarantees.
 */

$payload = json_decode(file_get_contents('php://stdin'), true) ?: [];
$command = $payload['tool_input']['command'] ?? '';

$isPr     = (bool) preg_match('/\bgh\s+pr\s+create\b/', $command);
$isCommit = (bool) preg_match('/\bgit\s+commit\b/', $command);

if (! $isPr && ! $isCommit) {
    exit(0); // allow everything else untouched
}

$projectDir = getenv('CLAUDE_PROJECT_DIR') ?: getcwd();
chdir($projectDir);

// PR is the stricter gate and wins if a command somehow does both.
if ($isPr) {
    $run  = 'docker compose exec -T app php artisan test --coverage --min=80 2>&1';
    $gate = 'opening a PR requires a green suite AND coverage ≥ 80%';
} else {
    $run  = 'docker compose exec -T app php artisan test 2>&1';
    $gate = 'committing requires a green test suite';
}

exec($run, $output, $code);

if ($code !== 0) {
    fwrite(STDERR, "\n⛔ Blocked: {$gate} — not satisfied.\n");
    fwrite(STDERR, "   (TDD: red → green → refactor. Fix, then retry.)\n\n");
    fwrite(STDERR, implode("\n", array_slice($output, -25)) . "\n");
    exit(2); // exit code 2 → block the tool call, feed stderr back to the agent
}

exit(0);
