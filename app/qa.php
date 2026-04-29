<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function qa_project_root(): string
{
    $root = realpath(__DIR__ . '/..');
    return $root !== false ? $root : __DIR__ . '/..';
}

function qa_output_dir(): string
{
    return qa_project_root() . '/qa-output';
}

function qa_temp_token(): string
{
    return date('Ymd-His') . '-' . bin2hex(random_bytes(4));
}

function qa_has_openai_key(): bool
{
    return trim((string) env_value('OPENAI_API_KEY', '')) !== '';
}

function qa_command(array $args): string
{
    return implode(' ', array_map('escapeshellarg', $args));
}

function qa_env_prefix(array $env): string
{
    $parts = [];

    foreach ($env as $key => $value) {
        $parts[] = sprintf('%s=%s', (string) $key, escapeshellarg((string) $value));
    }

    return implode(' ', $parts);
}

function qa_run_command(array $args, array $env = []): array
{
    if (!function_exists('proc_open')) {
        return [
            'exit_code' => 1,
            'stdout' => '',
            'stderr' => 'proc_open is disabled on this server.',
        ];
    }

    $command = qa_command($args);
    $envPrefix = qa_env_prefix($env);
    if ($envPrefix !== '') {
        $command = $envPrefix . ' ' . $command;
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, qa_project_root());
    if (!is_resource($process)) {
        return [
            'exit_code' => 1,
            'stdout' => '',
            'stderr' => 'Unable to start the QA process.',
        ];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return [
        'exit_code' => $exitCode,
        'stdout' => $stdout === false ? '' : $stdout,
        'stderr' => $stderr === false ? '' : $stderr,
    ];
}

function qa_json_encode(array $payload): string
{
    return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

function qa_generate_plan(string $prompt, string $baseUrl, ?string $model = null): array
{
    $token = qa_temp_token();
    $wrapperPath = qa_output_dir() . "/web-plan-{$token}.json";

    if (!is_dir(qa_output_dir())) {
        mkdir(qa_output_dir(), 0775, true);
    }

    $result = qa_run_command([
        'node',
        'qa/plan.js',
        '--prompt',
        $prompt,
        '--base-url',
        $baseUrl,
        '--output',
        $wrapperPath,
    ], [
        'OPENAI_MODEL' => $model ?? env_value('OPENAI_MODEL', 'gpt-5.5'),
    ]);

    $wrapper = null;
    if (is_file($wrapperPath)) {
        $raw = file_get_contents($wrapperPath);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $wrapper = $decoded;
            }
        }
    }

    if (!is_array($wrapper) || !is_array($wrapper['plan'] ?? null)) {
        return [
            'ok' => false,
            'error' => trim($result['stderr'] ?: $result['stdout'] ?: 'Failed to generate plan.'),
            'command_result' => $result,
            'wrapper_path' => $wrapperPath,
        ];
    }

    $plan = $wrapper['plan'];
    $planPath = qa_output_dir() . '/web-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', (string)($plan['id'] ?? 'plan')) . '-' . $token . '.plan.json';
    file_put_contents($planPath, qa_json_encode($plan), LOCK_EX);

    return [
        'ok' => $result['exit_code'] === 0,
        'plan' => $plan,
        'plan_path' => $planPath,
        'wrapper' => $wrapper,
        'command_result' => $result,
        'wrapper_path' => $wrapperPath,
        'error' => $result['exit_code'] === 0 ? null : trim($result['stderr'] ?: $result['stdout'] ?: 'Failed to generate plan.'),
    ];
}

function qa_run_plan_file(string $planPath): array
{
    if (!is_file($planPath)) {
        return [
            'ok' => false,
            'error' => 'Plan file not found.',
        ];
    }

    $raw = file_get_contents($planPath);
    $plan = $raw !== false ? json_decode($raw, true) : null;
    if (!is_array($plan)) {
        return [
            'ok' => false,
            'error' => 'Plan file is not valid JSON.',
        ];
    }

    $result = qa_run_command([
        'node',
        'qa/runner.js',
        $planPath,
    ]);

    $resultPath = qa_output_dir() . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '-', (string)($plan['id'] ?? 'plan')) . '.result.json';
    $output = null;
    if (is_file($resultPath)) {
        $resultRaw = file_get_contents($resultPath);
        if ($resultRaw !== false) {
            $decoded = json_decode($resultRaw, true);
            if (is_array($decoded)) {
                $output = $decoded;
            }
        }
    }

    return [
        'ok' => $result['exit_code'] === 0 && is_array($output),
        'plan' => $plan,
        'result' => $output,
        'result_path' => $resultPath,
        'command_result' => $result,
        'error' => $result['exit_code'] === 0 ? null : trim($result['stderr'] ?: $result['stdout'] ?: 'Failed to run plan.'),
    ];
}
