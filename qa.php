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
$aiProviders = [
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
$selectedProvider = trim((string)($_POST['provider'] ?? $aiProviders[0]));
$selectedProvider = in_array($selectedProvider, $aiProviders, true) ? $selectedProvider : $aiProviders[0];
$providerThemes = [
    'OpenAI API' => [
        'badge' => 'OpenAI tuned',
        'accentStart' => '#38bdf8',
        'accentEnd' => '#22c55e',
        'activeBorder' => 'rgba(56, 189, 248, 0.32)',
        'activeBg' => 'rgba(56, 189, 248, 0.10)',
        'doneBorder' => 'rgba(34, 197, 94, 0.30)',
        'doneBg' => 'rgba(34, 197, 94, 0.10)',
    ],
    'Xiaomi MiMo API' => [
        'badge' => 'MiMo tuned',
        'accentStart' => '#fb7185',
        'accentEnd' => '#f97316',
        'activeBorder' => 'rgba(251, 113, 133, 0.32)',
        'activeBg' => 'rgba(251, 113, 133, 0.10)',
        'doneBorder' => 'rgba(249, 115, 22, 0.30)',
        'doneBg' => 'rgba(249, 115, 22, 0.10)',
    ],
    'Anthropic Claude API' => [
        'badge' => 'Claude tuned',
        'accentStart' => '#a855f7',
        'accentEnd' => '#ec4899',
        'activeBorder' => 'rgba(168, 85, 247, 0.32)',
        'activeBg' => 'rgba(168, 85, 247, 0.10)',
        'doneBorder' => 'rgba(236, 72, 153, 0.30)',
        'doneBg' => 'rgba(236, 72, 153, 0.10)',
    ],
    'Google Gemini API' => [
        'badge' => 'Gemini tuned',
        'accentStart' => '#06b6d4',
        'accentEnd' => '#10b981',
        'activeBorder' => 'rgba(6, 182, 212, 0.32)',
        'activeBg' => 'rgba(6, 182, 212, 0.10)',
        'doneBorder' => 'rgba(16, 185, 129, 0.30)',
        'doneBg' => 'rgba(16, 185, 129, 0.10)',
    ],
    'Mistral AI API' => [
        'badge' => 'Mistral tuned',
        'accentStart' => '#f59e0b',
        'accentEnd' => '#fb7185',
        'activeBorder' => 'rgba(245, 158, 11, 0.32)',
        'activeBg' => 'rgba(245, 158, 11, 0.10)',
        'doneBorder' => 'rgba(251, 113, 133, 0.30)',
        'doneBg' => 'rgba(251, 113, 133, 0.10)',
    ],
    'DeepSeek API' => [
        'badge' => 'DeepSeek tuned',
        'accentStart' => '#14b8a6',
        'accentEnd' => '#3b82f6',
        'activeBorder' => 'rgba(20, 184, 166, 0.32)',
        'activeBg' => 'rgba(20, 184, 166, 0.10)',
        'doneBorder' => 'rgba(59, 130, 246, 0.30)',
        'doneBg' => 'rgba(59, 130, 246, 0.10)',
    ],
    'xAI Grok API' => [
        'badge' => 'Grok tuned',
        'accentStart' => '#f43f5e',
        'accentEnd' => '#f59e0b',
        'activeBorder' => 'rgba(244, 63, 94, 0.32)',
        'activeBg' => 'rgba(244, 63, 94, 0.10)',
        'doneBorder' => 'rgba(245, 158, 11, 0.30)',
        'doneBg' => 'rgba(245, 158, 11, 0.10)',
    ],
    'Groq API' => [
        'badge' => 'Groq tuned',
        'accentStart' => '#22c55e',
        'accentEnd' => '#14b8a6',
        'activeBorder' => 'rgba(34, 197, 94, 0.32)',
        'activeBg' => 'rgba(34, 197, 94, 0.10)',
        'doneBorder' => 'rgba(20, 184, 166, 0.30)',
        'doneBg' => 'rgba(20, 184, 166, 0.10)',
    ],
    'Microsoft Azure OpenAI API' => [
        'badge' => 'Azure tuned',
        'accentStart' => '#2563eb',
        'accentEnd' => '#0ea5e9',
        'activeBorder' => 'rgba(37, 99, 235, 0.32)',
        'activeBg' => 'rgba(37, 99, 235, 0.10)',
        'doneBorder' => 'rgba(14, 165, 233, 0.30)',
        'doneBg' => 'rgba(14, 165, 233, 0.10)',
    ],
    'Amazon Web Services Bedrock API' => [
        'badge' => 'Bedrock tuned',
        'accentStart' => '#f97316',
        'accentEnd' => '#ea580c',
        'activeBorder' => 'rgba(249, 115, 22, 0.32)',
        'activeBg' => 'rgba(249, 115, 22, 0.10)',
        'doneBorder' => 'rgba(234, 88, 12, 0.30)',
        'doneBg' => 'rgba(234, 88, 12, 0.10)',
    ],
];
$selectedTheme = $providerThemes[$selectedProvider] ?? $providerThemes[$aiProviders[0]];

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

render_layout('QA Dashboard - Qaitest', 'qa', function () use ($defaultPrompt, $defaultBaseUrl, $notice, $error, $generatedPlan, $generatedPlanPath, $generatedResult, $generatedResultPath, $submittedPrompt, $submittedBaseUrl, $submittedModel, $selectedProvider, $aiProviders, $providerThemes, $selectedTheme, $lastAction, $lastCommand): void {
    $openaiReady = qa_has_openai_key();
    ?>
    <div class="eyebrow">
        <span class="dot" aria-hidden="true"></span>
        QA orchestration
    </div>

    <h1>QA Dashboard</h1>

    <p class="lede">
        Ada dua mode di halaman ini. Satu mode simulasi interaktif tanpa AI, satu lagi mode QA asli untuk generate plan dan run lewat Playwright.
    </p>

    <div class="panel stack demo-shell" data-provider-theme="<?php echo h($selectedProvider); ?>" data-testid="qa-demo-run" style="--provider-accent-start: <?php echo h($selectedTheme['accentStart']); ?>; --provider-accent-end: <?php echo h($selectedTheme['accentEnd']); ?>; --provider-active-border: <?php echo h($selectedTheme['activeBorder']); ?>; --provider-active-bg: <?php echo h($selectedTheme['activeBg']); ?>; --provider-done-border: <?php echo h($selectedTheme['doneBorder']); ?>; --provider-done-bg: <?php echo h($selectedTheme['doneBg']); ?>;">
        <div class="section-head">
            <div>
                <div class="label">Interactive demo run</div>
                <div class="muted">Masukkan prompt apa pun. Demo ini tetap berakhir sukses dan menampilkan summary.</div>
            </div>
            <div class="tag" data-testid="qa-demo-provider-badge"><?php echo h($selectedTheme['badge']); ?></div>
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
            <div class="muted" data-testid="qa-demo-provider-line">Provider: <?php echo h($selectedProvider); ?></div>
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
            <div class="value"><?php echo h($selectedProvider); ?></div>
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
            <?php foreach ($aiProviders as $api): ?>
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
                        <label class="label" for="provider">AI provider</label>
                        <select class="input" id="provider" name="provider" data-testid="qa-provider">
                            <?php foreach ($aiProviders as $provider): ?>
                                <option value="<?php echo h($provider); ?>" <?php echo $provider === $selectedProvider ? 'selected' : ''; ?>>
                                    <?php echo h($provider); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="muted" data-testid="qa-provider-hint">OpenAI API memakai model default untuk generate plan.</div>
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="model" data-testid="qa-model-label">Model / deployment</label>
                    <input class="input" id="model" name="model" value="<?php echo h($submittedModel); ?>" placeholder="gpt-5.5" data-testid="qa-model-input">
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
            const providerSelect = document.querySelector('[data-testid="qa-provider"]');
            const providerLine = document.querySelector('[data-testid="qa-demo-provider-line"]');
            const providerBadge = document.querySelector('[data-testid="qa-demo-provider-badge"]');
            const providerHint = document.querySelector('[data-testid="qa-provider-hint"]');
            const modelLabel = document.querySelector('[data-testid="qa-model-label"]');
            const modelInput = document.querySelector('[data-testid="qa-model-input"]');
            const demoShell = document.querySelector('[data-testid="qa-demo-run"]');
            const stageMap = {
                loading: document.querySelector('[data-testid="qa-demo-stage-loading"]'),
                flow: document.querySelector('[data-testid="qa-demo-stage-flow"]'),
                validation: document.querySelector('[data-testid="qa-demo-stage-validation"]'),
                summary: document.querySelector('[data-testid="qa-demo-stage-summary"]'),
            };

            if (!form || !promptInput || !status || !runButton || !resetButton || !progressBar || !summaryText || !providerSelect || !providerLine || !providerBadge || !providerHint || !modelLabel || !modelInput || !demoShell) {
                return;
            }

            const providerConfig = {
                'OpenAI API': {
                    badge: 'OpenAI tuned',
                    hint: 'OpenAI API memakai model default untuk generate plan.',
                    label: 'Model / deployment',
                    placeholder: 'gpt-5.5',
                },
                'Xiaomi MiMo API': {
                    badge: 'MiMo tuned',
                    hint: 'Xiaomi MiMo API biasanya memakai model atau deployment name spesifik.',
                    label: 'Model / deployment',
                    placeholder: 'mimo-v1',
                },
                'Anthropic Claude API': {
                    badge: 'Claude tuned',
                    hint: 'Anthropic Claude API cocok untuk model keluarga Claude.',
                    label: 'Model / deployment',
                    placeholder: 'claude-3.5-sonnet',
                },
                'Google Gemini API': {
                    badge: 'Gemini tuned',
                    hint: 'Google Gemini API bisa dipakai dengan model Gemini yang sesuai.',
                    label: 'Model / deployment',
                    placeholder: 'gemini-2.5-pro',
                },
                'Mistral AI API': {
                    badge: 'Mistral tuned',
                    hint: 'Mistral AI API biasanya memakai nama model Mistral yang tersedia.',
                    label: 'Model / deployment',
                    placeholder: 'mistral-large-latest',
                },
                'DeepSeek API': {
                    badge: 'DeepSeek tuned',
                    hint: 'DeepSeek API memakai model DeepSeek yang dipilih di environment ini.',
                    label: 'Model / deployment',
                    placeholder: 'deepseek-chat',
                },
                'xAI Grok API': {
                    badge: 'Grok tuned',
                    hint: 'xAI Grok API memakai model Grok yang cocok untuk endpoint ini.',
                    label: 'Model / deployment',
                    placeholder: 'grok-3',
                },
                'Groq API': {
                    badge: 'Groq tuned',
                    hint: 'Groq API sering dipakai untuk model cepat dengan deployment tertentu.',
                    label: 'Model / deployment',
                    placeholder: 'llama-3.3-70b-versatile',
                },
                'Microsoft Azure OpenAI API': {
                    badge: 'Azure tuned',
                    hint: 'Azure OpenAI biasanya memakai deployment name, bukan model mentah.',
                    label: 'Deployment name',
                    placeholder: 'gpt-5.5-prod',
                },
                'Amazon Web Services Bedrock API': {
                    badge: 'Bedrock tuned',
                    hint: 'Bedrock biasanya memakai model ID yang didukung provider.',
                    label: 'Model ID',
                    placeholder: 'anthropic.claude-3-5-sonnet-20240620-v1:0',
                },
            };

            const getProviderConfig = (provider) => providerConfig[provider] ?? providerConfig['OpenAI API'];

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
                const providerState = getProviderConfig(providerSelect.value);
                providerLine.textContent = `Provider: ${providerSelect.value}`;
                providerBadge.textContent = providerState.badge;
                providerHint.textContent = providerState.hint;
                modelLabel.textContent = providerState.label;
                modelInput.placeholder = providerState.placeholder;
                demoShell.dataset.providerTheme = providerSelect.value;
                summaryText.textContent = 'Belum ada run.';
                progressBar.style.width = '0%';
                runButton.disabled = false;
                runButton.textContent = 'Run demo';
                setStage('');
            };

            const runDemo = () => {
                clearTimers();

                const promptValue = promptInput.value.trim() || 'Prompt kosong';
                const providerState = getProviderConfig(providerSelect.value);
                const stepSummary = `Prompt "${promptValue}" diproses dengan ${providerSelect.value}, semua tahap simulasi lulus, dan summary berhasil dibuat.`;

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

            const syncProviderUi = () => {
                const providerState = getProviderConfig(providerSelect.value);
                providerLine.textContent = `Provider: ${providerSelect.value}`;
                providerBadge.textContent = providerState.badge;
                providerHint.textContent = providerState.hint;
                modelLabel.textContent = providerState.label;
                modelInput.placeholder = providerState.placeholder;
                demoShell.dataset.providerTheme = providerSelect.value;
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                runDemo();
            });

            providerSelect.addEventListener('change', syncProviderUi);
            resetButton.addEventListener('click', resetDemo);
            resetDemo();
        })();
    </script>
    <?php
}, [
    'bodyClass' => 'page-qa',
]);
