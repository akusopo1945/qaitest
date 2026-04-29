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
    $aiApis = [
        'OpenAI API',
        'Xiaomi MiMo API',
        'Anthropic Claude API',
        'Google Gemini API',
        'Mistral AI API',
        'DeepSeek API',
        'xAI Grok API',
        'Groq API',
        'Microsoft Azure OpenAI API',
        'Amazon Web Services Bedrock API',
    ];
    ?>
    <div class="eyebrow">
        <span class="dot" aria-hidden="true"></span>
        QA orchestration
    </div>

    <h1>QA Dashboard</h1>

    <p class="lede">
        Ada dua mode di halaman ini. Satu mode simulasi interaktif tanpa AI, satu lagi mode QA asli untuk generate plan dan run lewat Playwright.
    </p>

    <div class="panel stack" data-testid="qa-demo-run">
        <div class="section-head">
            <div>
                <div class="label">Interactive demo run</div>
                <div class="muted">Masukkan prompt apa pun. Demo ini tetap berakhir sukses dan menampilkan summary.</div>
            </div>
            <div class="tag" data-testid="qa-demo-status">Idle</div>
        </div>

        <form class="form" id="qa-demo-form">
            <div class="field">
                <label class="label" for="qa-demo-prompt">Demo prompt</label>
                <textarea class="textarea" id="qa-demo-prompt" name="prompt" data-testid="qa-demo-prompt" placeholder="Tulis prompt apa saja di sini">cek login, submit form, dan lihat summary</textarea>
            </div>

            <div class="button-row">
                <button class="button primary" type="submit" data-testid="qa-demo-run-button">Run demo</button>
                <button class="button secondary" type="button" data-testid="qa-demo-reset-button">Reset</button>
            </div>
        </form>

        <div class="demo-progress">
            <div class="demo-progress-bar" data-testid="qa-demo-progress-bar"></div>
        </div>

        <div class="demo-status-grid">
            <div class="demo-status-card" data-stage="loading" data-testid="qa-demo-stage-loading">
                <div class="label">Loading</div>
                <div class="value">Menyiapkan simulasi.</div>
            </div>
            <div class="demo-status-card" data-stage="flow" data-testid="qa-demo-stage-flow">
                <div class="label">Process flow</div>
                <div class="value">Menjalankan langkah demo satu per satu.</div>
            </div>
            <div class="demo-status-card" data-stage="validation" data-testid="qa-demo-stage-validation">
                <div class="label">Validation</div>
                <div class="value">Semua validasi dibuat lulus.</div>
            </div>
            <div class="demo-status-card" data-stage="summary" data-testid="qa-demo-stage-summary">
                <div class="label">Summary</div>
                <div class="value">Ringkasan hasil demo akan tampil di sini.</div>
            </div>
        </div>

        <div class="demo-summary card-lite" data-testid="qa-demo-summary">
            <div class="label">Demo summary</div>
            <div class="value" data-testid="qa-demo-summary-text">Belum ada run.</div>
        </div>
    </div>

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

    <div class="panel stack">
        <div class="section-head">
            <div>
                <div class="label">Supported AI APIs</div>
                <div class="muted">Daftar provider yang bisa ditampilkan sebagai opsi model atau integrasi.</div>
            </div>
        </div>

        <div class="list" data-testid="ai-api-list">
            <?php foreach ($aiApis as $api): ?>
                <div class="entry">
                    <div class="entry-name"><?php echo h($api); ?></div>
                </div>
            <?php endforeach; ?>
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
    <script>
        (() => {
            const form = document.getElementById('qa-demo-form');
            const promptInput = document.getElementById('qa-demo-prompt');
            const status = document.querySelector('[data-testid="qa-demo-status"]');
            const runButton = document.querySelector('[data-testid="qa-demo-run-button"]');
            const resetButton = document.querySelector('[data-testid="qa-demo-reset-button"]');
            const progressBar = document.querySelector('[data-testid="qa-demo-progress-bar"]');
            const summaryText = document.querySelector('[data-testid="qa-demo-summary-text"]');
            const stageMap = {
                loading: document.querySelector('[data-testid="qa-demo-stage-loading"]'),
                flow: document.querySelector('[data-testid="qa-demo-stage-flow"]'),
                validation: document.querySelector('[data-testid="qa-demo-stage-validation"]'),
                summary: document.querySelector('[data-testid="qa-demo-stage-summary"]'),
            };

            if (!form || !promptInput || !status || !runButton || !resetButton || !progressBar || !summaryText) {
                return;
            }

            const stages = [
                { key: 'loading', label: 'Loading', progress: 20, delay: 700 },
                { key: 'flow', label: 'Process flow', progress: 55, delay: 900 },
                { key: 'validation', label: 'Validation', progress: 80, delay: 900 },
                { key: 'summary', label: 'Summary', progress: 100, delay: 650 },
            ];

            let timers = [];

            const clearTimers = () => {
                timers.forEach((timer) => window.clearTimeout(timer));
                timers = [];
            };

            const setStage = (activeStage) => {
                Object.entries(stageMap).forEach(([key, element]) => {
                    if (!element) {
                        return;
                    }

                    element.dataset.state = key === activeStage ? 'active' : key === 'summary' && activeStage === 'summary' ? 'done' : 'idle';
                });
            };

            const resetDemo = () => {
                clearTimers();
                status.textContent = 'Idle';
                summaryText.textContent = 'Belum ada run.';
                progressBar.style.width = '0%';
                runButton.disabled = false;
                runButton.textContent = 'Run demo';
                setStage('');
            };

            const runDemo = () => {
                clearTimers();

                const promptValue = promptInput.value.trim() || 'Prompt kosong';
                const stepSummary = `Prompt "${promptValue}" diproses, semua tahap simulasi lulus, dan summary berhasil dibuat.`;

                runButton.disabled = true;
                runButton.textContent = 'Running...';
                status.textContent = 'Loading';
                summaryText.textContent = 'Menjalankan simulasi...';
                progressBar.style.width = '0%';
                setStage('loading');

                stages.forEach((stage, index) => {
                    const timer = window.setTimeout(() => {
                        status.textContent = stage.label;
                        progressBar.style.width = `${stage.progress}%`;
                        setStage(stage.key);

                        if (stage.key === 'summary') {
                            summaryText.textContent = stepSummary;
                            status.textContent = 'Success';
                            runButton.disabled = false;
                            runButton.textContent = 'Run demo';
                        }
                    }, stages.slice(0, index).reduce((total, item) => total + item.delay, 0) + stage.delay);

                    timers.push(timer);
                });
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                runDemo();
            });

            resetButton.addEventListener('click', resetDemo);
            resetDemo();
        })();
    </script>
    <?php
}, [
    'bodyClass' => 'page-qa',
]);
