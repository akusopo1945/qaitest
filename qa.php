<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/qa.php';

$defaultPrompt = 'cek user bisa submit guestbook lalu entry muncul di halaman entries';
$defaultBaseUrl = env_value('PLAYWRIGHT_BASE_URL', 'http://qaitest.test/');
$notice = null;
$error = null;
$generatedPlan = null;
$generatedPlanPath = '';
$generatedResult = null;
$generatedResultPath = '';
$lastAction = '';
$lastCommand = null;
$submittedPrompt = trim((string)($_POST['prompt'] ?? $defaultPrompt));
$submittedBaseUrl = trim((string)($_POST['base_url'] ?? $defaultBaseUrl));
$submittedBaseUrl = $submittedBaseUrl !== '' ? $submittedBaseUrl : $defaultBaseUrl;
$submittedModel = trim((string)($_POST['model'] ?? env_value('OPENAI_MODEL', 'gpt-5.5')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastAction = (string)($_POST['action'] ?? '');

    if ($lastAction === 'generate' || $lastAction === 'generate_and_run') {
        $generated = qa_generate_plan($submittedPrompt, $submittedBaseUrl, $submittedModel);
        $lastCommand = $generated['command_result'] ?? null;

        if (($generated['ok'] ?? false) && is_array($generated['plan'] ?? null)) {
            $generatedPlan = $generated['plan'];
            $generatedPlanPath = (string)($generated['plan_path'] ?? '');
            $notice = 'Plan berhasil dibuat dari prompt natural language.';

            if ($lastAction === 'generate_and_run') {
                $run = qa_run_plan_file($generatedPlanPath);
                $lastCommand = $run['command_result'] ?? null;

                if (($run['ok'] ?? false) && is_array($run['result'] ?? null)) {
                    $generatedResult = $run['result'];
                    $generatedResultPath = (string)($run['result_path'] ?? '');
                    $notice = 'Plan berhasil dibuat dan dijalankan.';
                } else {
                    $error = (string)($run['error'] ?? 'Gagal menjalankan plan.');
                }
            }
        } else {
            $error = (string)($generated['error'] ?? 'Gagal membuat plan.');
        }
    }

    if ($lastAction === 'run_existing') {
        $planPath = trim((string)($_POST['plan_path'] ?? ''));
        $run = qa_run_plan_file($planPath);
        $lastCommand = $run['command_result'] ?? null;

        if (($run['ok'] ?? false) && is_array($run['result'] ?? null)) {
            $generatedPlan = $run['plan'] ?? null;
            $generatedPlanPath = $planPath;
            $generatedResult = $run['result'];
            $generatedResultPath = (string)($run['result_path'] ?? '');
            $notice = 'Plan berhasil dijalankan.';
        } else {
            $error = (string)($run['error'] ?? 'Gagal menjalankan plan.');
        }
    }
}

render_layout('QA Dashboard - Qaitest', 'qa', function () use ($defaultPrompt, $defaultBaseUrl, $notice, $error, $generatedPlan, $generatedPlanPath, $generatedResult, $generatedResultPath, $submittedPrompt, $submittedBaseUrl, $submittedModel, $lastAction, $lastCommand): void {
    $openaiReady = qa_has_openai_key();
    ?>
    <div class="eyebrow">
        <span class="dot" aria-hidden="true"></span>
        QA orchestration
    </div>

    <h1>QA Dashboard</h1>

    <p class="lede">
        Tulis test pakai bahasa manusia, lalu dashboard ini ubah jadi plan JSON, eksekusi via Playwright, dan tampilkan hasil teknis yang bisa diaudit.
    </p>

    <div class="grid qa-stats">
        <div class="panel">
            <div class="label">Pipeline</div>
            <div class="value">Natural language -> plan -> Playwright -> technical output -> AI summary</div>
        </div>
        <div class="panel">
            <div class="label">Provider</div>
            <div class="value"><?php echo h($submittedModel); ?></div>
        </div>
        <div class="panel">
            <div class="label">Base URL</div>
            <div class="value"><?php echo h($submittedBaseUrl); ?></div>
        </div>
        <div class="panel">
            <div class="label">OpenAI status</div>
            <div class="value" data-testid="openai-status"><?php echo $openaiReady ? 'Configured' : 'Not configured yet'; ?></div>
        </div>
    </div>

    <?php if (!$openaiReady): ?>
        <div class="notice warn" data-testid="openai-warning">
            <span class="tag">Setup</span>
            <div>OpenAI key belum ketemu. Isi <code>OPENAI_API_KEY</code> di <code>.env.local</code> kalau mau generate plan dari web.</div>
        </div>
    <?php endif; ?>

    <?php if ($notice !== null): ?>
        <div class="notice" data-testid="qa-notice">
            <span class="tag">OK</span>
            <div><?php echo h($notice); ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="notice warn" data-testid="qa-error">
            <span class="tag">Error</span>
            <div><?php echo h($error); ?></div>
        </div>
    <?php endif; ?>

    <div class="qa-columns stack">
        <div class="panel">
            <div class="section-head">
                <div>
                    <div class="label">Prompt</div>
                    <div class="muted">Isi dengan bahasa manusia. AI yang ubah jadi plan.</div>
                </div>
            </div>

            <form class="form" method="post" action="/qa.php">
                <input type="hidden" name="action" value="generate">

                <div class="field">
                    <label class="label" for="prompt">Natural language test</label>
                    <textarea class="textarea" id="prompt" name="prompt" data-testid="qa-prompt"><?php echo h($submittedPrompt !== '' ? $submittedPrompt : $defaultPrompt); ?></textarea>
                </div>

                <div class="grid">
                    <div class="field">
                        <label class="label" for="base_url">Base URL</label>
                        <input class="input" id="base_url" name="base_url" value="<?php echo h($submittedBaseUrl); ?>" placeholder="http://qaitest.test/">
                    </div>

                    <div class="field">
                        <label class="label" for="model">OpenAI model</label>
                        <input class="input" id="model" name="model" value="<?php echo h($submittedModel); ?>" placeholder="gpt-5.5">
                    </div>
                </div>

                <div class="button-row">
                    <button class="button primary" type="submit">Generate plan</button>
                    <button class="button secondary" type="submit" name="action" value="generate_and_run">Generate & run</button>
                    <a class="button secondary" href="/qa.php">Reset</a>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="section-head">
                <div>
                    <div class="label">How it works</div>
                    <div class="muted">Langkahnya tetap eksplisit dan mudah diaudit.</div>
                </div>
            </div>

            <div class="list">
                <div class="entry">
                    <div class="entry-name">1. Prompt</div>
                    <div class="entry-message">User nulis intent test dengan bahasa natural.</div>
                </div>
                <div class="entry">
                    <div class="entry-name">2. Plan</div>
                    <div class="entry-message">OpenAI mengubah prompt jadi plan JSON sesuai schema.</div>
                </div>
                <div class="entry">
                    <div class="entry-name">3. Run</div>
                    <div class="entry-message">Playwright mengeksekusi step, assertion, dan cleanup.</div>
                </div>
                <div class="entry">
                    <div class="entry-name">4. Summary</div>
                    <div class="entry-message">Hasil teknis disimpan, lalu AI summary bisa dibuat di layer atas.</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (is_array($generatedPlan)): ?>
        <div class="stack">
            <div class="panel">
                <div class="section-head">
                    <div>
                        <div class="label">Generated plan</div>
                        <div class="muted">Plan disimpan di <?php echo h($generatedPlanPath); ?></div>
                    </div>

                    <?php if ($generatedPlanPath !== ''): ?>
                        <form method="post" action="/qa.php">
                            <input type="hidden" name="action" value="run_existing">
                            <input type="hidden" name="plan_path" value="<?php echo h($generatedPlanPath); ?>">
                            <input type="hidden" name="prompt" value="<?php echo h($submittedPrompt); ?>">
                            <input type="hidden" name="base_url" value="<?php echo h($submittedBaseUrl); ?>">
                            <input type="hidden" name="model" value="<?php echo h($submittedModel); ?>">
                            <button class="button secondary" type="submit">Run saved plan</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="codebox" data-testid="qa-plan-json"><?php echo h(qa_json_encode($generatedPlan)); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (is_array($generatedResult)): ?>
        <div class="stack">
            <div class="panel">
                <div class="section-head">
                    <div>
                        <div class="label">Technical result</div>
                        <div class="muted">Result disimpan di <?php echo h($generatedResultPath); ?></div>
                    </div>
                </div>

                <div class="grid qa-stats">
                    <div class="panel">
                        <div class="label">Steps</div>
                        <div class="value"><?php echo h((string)($generatedResult['technical_summary']['passed_steps'] ?? 0)); ?>/<?php echo h((string)($generatedResult['technical_summary']['total_steps'] ?? 0)); ?> passed</div>
                    </div>
                    <div class="panel">
                        <div class="label">Assertions</div>
                        <div class="value"><?php echo h((string)($generatedResult['technical_summary']['passed_assertions'] ?? 0)); ?>/<?php echo h((string)($generatedResult['technical_summary']['total_assertions'] ?? 0)); ?> passed</div>
                    </div>
                    <div class="panel">
                        <div class="label">Cleanup</div>
                        <div class="value"><?php echo h((string)($generatedResult['technical_summary']['passed_cleanup_steps'] ?? 0)); ?>/<?php echo h((string)($generatedResult['technical_summary']['total_cleanup_steps'] ?? 0)); ?> passed</div>
                    </div>
                    <div class="panel">
                        <div class="label">Plan ID</div>
                        <div class="value"><?php echo h((string)($generatedResult['plan_id'] ?? '')); ?></div>
                    </div>
                </div>

                <div class="codebox" data-testid="qa-result-json"><?php echo h(qa_json_encode($generatedResult)); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (is_array($lastCommand)): ?>
        <div class="stack">
            <div class="panel">
                <div class="label">Last command</div>
                <div class="codebox" data-testid="qa-command">
                    <?php echo h(qa_json_encode($lastCommand)); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php
}, [
    'bodyClass' => 'page-qa',
]);
