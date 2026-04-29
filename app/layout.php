<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function render_layout(string $title, string $activePage, callable $content, array $options = []): void
{
    $bodyClass = trim((string)($options['bodyClass'] ?? ''));
    $shellClass = trim((string)($options['shellClass'] ?? 'shell'));
    $cardClass = trim((string)($options['cardClass'] ?? 'card'));
    $bodyClassAttr = $bodyClass !== '' ? ' class="' . h($bodyClass) . '"' : '';

    ob_start();
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($title); ?></title>
    <style>
        :root {
            color-scheme: light;
            --bg: #0f172a;
            --bg-2: #111827;
            --card: rgba(15, 23, 42, 0.82);
            --card-border: rgba(148, 163, 184, 0.18);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --accent-2: #22c55e;
            --warn: #f59e0b;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            min-height: 100%;
        }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.18), transparent 30%),
                radial-gradient(circle at top right, rgba(34, 197, 94, 0.14), transparent 28%),
                linear-gradient(180deg, var(--bg), var(--bg-2));
        }

        .shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card {
            width: min(920px, 100%);
            border: 1px solid var(--card-border);
            background: var(--card);
            backdrop-filter: blur(18px);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 30px 80px rgba(2, 6, 23, 0.5);
        }

        .card-home {
            width: min(1180px, 100%);
        }

        .shell-home {
            align-items: start;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
            text-decoration: none;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand-mark {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, #38bdf8, #22c55e);
            box-shadow: 0 0 0 6px rgba(56, 189, 248, 0.12);
        }

        .topbar-links {
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .topbar-link {
            color: var(--muted);
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid transparent;
            transition: 0.15s ease;
        }

        .topbar-link:hover {
            color: var(--text);
            border-color: rgba(148, 163, 184, 0.2);
            background: rgba(148, 163, 184, 0.08);
        }

        .topbar-link.is-active {
            color: var(--text);
            background: rgba(56, 189, 248, 0.14);
            border-color: rgba(56, 189, 248, 0.24);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--muted);
            font-size: 0.9rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: var(--accent-2);
            box-shadow: 0 0 0 6px rgba(34, 197, 94, 0.12);
        }

        h1 {
            margin: 18px 0 12px;
            font-size: clamp(2.1rem, 6vw, 4.25rem);
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        .lede {
            margin: 0;
            max-width: 62ch;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .stack {
            display: grid;
            gap: 18px;
            margin-top: 24px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .panel {
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 18px;
            padding: 18px;
            background: rgba(2, 6, 23, 0.34);
        }

        .label {
            color: var(--muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .value {
            margin-top: 8px;
            font-size: 1rem;
            line-height: 1.5;
            word-break: break-word;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            margin-top: 18px;
            border-radius: 999px;
            background: rgba(56, 189, 248, 0.14);
            color: #c7f2ff;
            font-size: 0.9rem;
            border: 1px solid rgba(56, 189, 248, 0.22);
        }

        .notice {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(34, 197, 94, 0.10);
            border: 1px solid rgba(34, 197, 94, 0.24);
            color: #d6ffe5;
        }

        .notice.warn {
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.26);
            color: #ffefcf;
        }

        .form {
            display: grid;
            gap: 12px;
            margin-top: 24px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        .input, .textarea {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.26);
            border-radius: 14px;
            padding: 14px 16px;
            font: inherit;
            color: var(--text);
            background: rgba(2, 6, 23, 0.45);
            outline: none;
        }

        .textarea {
            min-height: 118px;
            resize: vertical;
        }

        .input:focus, .textarea:focus {
            border-color: rgba(56, 189, 248, 0.6);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.12);
        }

        .button-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            padding: 12px 16px;
            border: 0;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .button.primary {
            background: linear-gradient(135deg, #38bdf8, #22c55e);
            color: #08111f;
        }

        .button.secondary {
            background: rgba(148, 163, 184, 0.14);
            color: var(--text);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .list {
            display: grid;
            gap: 14px;
            margin-top: 12px;
        }

        .entry {
            padding: 16px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 18px;
            background: rgba(2, 6, 23, 0.32);
        }

        .entry-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
        }

        .entry-name {
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .entry-meta {
            color: var(--muted);
            font-size: 0.85rem;
        }

        .entry-message {
            margin-top: 10px;
            line-height: 1.7;
        }

        .footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid rgba(148, 163, 184, 0.18);
            color: var(--muted);
        }

        .empty {
            padding: 18px;
            border-radius: 18px;
            border: 1px dashed rgba(148, 163, 184, 0.28);
            color: var(--muted);
            background: rgba(2, 6, 23, 0.22);
        }

        .muted {
            color: var(--muted);
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.18);
            color: #c7f2ff;
            font-size: 0.85rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            overflow: hidden;
            border-radius: 18px;
        }

        .table th,
        .table td {
            text-align: left;
            padding: 12px 14px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            vertical-align: top;
        }

        .table th {
            color: var(--muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .codebox {
            margin-top: 14px;
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(2, 6, 23, 0.42);
            color: var(--text);
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            white-space: pre-wrap;
            overflow: auto;
        }

        .qa-columns {
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        }

        .qa-stats {
            margin-top: 0;
        }

        .qa-stats .panel {
            background: rgba(2, 6, 23, 0.28);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
            gap: 20px;
            align-items: start;
        }

        .hero-copy {
            display: grid;
            gap: 18px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .hero-panel {
            display: grid;
            gap: 14px;
        }

        .hero-visual {
            border-radius: 22px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background:
                radial-gradient(circle at top right, rgba(56, 189, 248, 0.2), transparent 35%),
                rgba(2, 6, 23, 0.36);
            padding: 20px;
        }

        .hero-title {
            display: grid;
            gap: 8px;
        }

        .eyebrow-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.12);
            color: #d7e8ff;
            border: 1px solid rgba(148, 163, 184, 0.18);
            font-size: 0.85rem;
        }

        .hero-headline {
            font-size: clamp(2.7rem, 7vw, 5.4rem);
            line-height: 0.9;
            margin: 0;
            max-width: 10ch;
        }

        .hero-copy .lede {
            max-width: 54ch;
            font-size: 1.06rem;
        }

        .metric-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .metric {
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(2, 6, 23, 0.34);
            border: 1px solid rgba(148, 163, 184, 0.12);
        }

        .metric-value {
            font-size: 1.15rem;
            font-weight: 700;
            margin-top: 6px;
        }

        .metric-label {
            color: var(--muted);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .timeline {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 16px;
        }

        .timeline-card {
            position: relative;
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(2, 6, 23, 0.34);
        }

        .timeline-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            background: rgba(56, 189, 248, 0.14);
            border: 1px solid rgba(56, 189, 248, 0.22);
            color: #c7f2ff;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .timeline-title {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .timeline-body {
            color: var(--muted);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .demo-shell {
            display: grid;
            gap: 14px;
        }

        .demo-title {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
        }

        .demo-card {
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(2, 6, 23, 0.34);
        }

        .demo-card .label {
            margin-bottom: 8px;
        }

        .demo-flow {
            display: grid;
            gap: 12px;
        }

        .demo-step {
            display: grid;
            gap: 4px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(2, 6, 23, 0.28);
            border: 1px solid rgba(148, 163, 184, 0.12);
        }

        .demo-step strong {
            color: #f8fbff;
        }

        .demo-progress {
            height: 12px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(148, 163, 184, 0.14);
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .demo-progress-bar {
            width: 0%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #38bdf8, #22c55e);
            transition: width 0.35s ease;
        }

        .demo-status-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .demo-status-card {
            padding: 14px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.12);
            background: rgba(2, 6, 23, 0.28);
            opacity: 0.55;
            transition: 0.2s ease;
        }

        .demo-status-card .value {
            margin-top: 6px;
            color: var(--muted);
        }

        .demo-status-card[data-state="active"] {
            opacity: 1;
            border-color: rgba(56, 189, 248, 0.32);
            background: rgba(56, 189, 248, 0.10);
        }

        .demo-status-card[data-state="done"] {
            opacity: 1;
            border-color: rgba(34, 197, 94, 0.30);
            background: rgba(34, 197, 94, 0.10);
        }

        .card-lite {
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(2, 6, 23, 0.30);
        }

        .button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 640px) {
            .card {
                padding: 22px;
                border-radius: 20px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .qa-columns {
                grid-template-columns: 1fr;
            }

            .hero {
                grid-template-columns: 1fr;
            }

            .metric-strip {
                grid-template-columns: 1fr;
            }

            .timeline {
                grid-template-columns: 1fr;
            }

            .demo-status-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body<?php echo $bodyClassAttr; ?>>
    <main class="<?php echo h($shellClass); ?>">
        <section class="<?php echo h($cardClass); ?>">
            <?php
            $activePage = $activePage;
            require __DIR__ . '/../partials/topbar.php';
            $content();
            require __DIR__ . '/../partials/footer.php';
            ?>
        </section>
    </main>
</body>
</html>
    <?php
    echo ob_get_clean();
}
