# Qaitest

Qaitest is a lightweight PHP playground for a guestbook, demo pages, and a Playwright-based QA workflow.

## Overview

The project uses:

- native PHP
- Nginx
- PHP-FPM
- Playwright for automated tests

The goal is simple:

- provide a clean landing page
- keep a few small demo pages around
- offer a compact guestbook with CRUD
- expose a QA dashboard for prompts, plans, and execution
- stay easy to maintain as a small baseline project

## What’s Included

Main project components:

- homepage in `index.php`
- about page in `about.php`
- entries page in `entries.php`
- QA dashboard in `qa.php`
- edit page in `edit.php`
- PHP helpers in `app/bootstrap.php`
- reusable layout in `app/layout.php`
- storage helper in `app/storage.php`
- reference MySQL schema in `database/guestbook_schema.sql`
- reusable footer in `partials/footer.php`
- reusable top navigation in `partials/topbar.php`
- automated tests in `tests/`
- Playwright config in `playwright.config.ts`
- system guidance in `project_context.md`
- neutral QA plan format in `qa_plan_format.md`
- QA plan schema in `qa/qa_plan.schema.json`
- QA runner in `qa/runner.js`
- OpenAI planner in `qa/plan.js`
- sample QA plan in `qa/plans/guestbook-happy.json`

## Pages

### Home

This page shows:

- a stronger hero title
- a status chip
- a hardcoded demo flow for AI planning
- a small guestbook form for name input
- a personal greeting
- server name
- request URI
- recent entries

### About

This page gives a short, plain overview of the project.

### Entries

This page lists everything saved through the guestbook homepage.

Available features:

- search by name or message
- sort by newest, oldest, name A-Z, or name Z-A
- date range filtering with `from` and `to`
- pagination when the list grows
- edit and delete actions

### Edit

This page updates an entry that has already been saved.

### QA Dashboard

This page is used to:

- write test prompts in plain language
- generate a structured plan with OpenAI
- run that plan through Playwright
- inspect technical output and execution results

## Getting Started

### 1. Install dependencies

```bash
pnpm install
```

### 2. Set the base URL

Default base URL:

```bash
http://qaitest.test/
```

Adjust host mapping or the base URL to match your environment if needed.

### 3. Open it in a browser

```text
http://qaitest.test/
```

If everything is set up correctly, the Qaitest landing page should load normally.

## Testing

The project has two main layers:

- app layer: native PHP playground
- QA layer: Playwright as the deterministic executor

QA flow:

1. natural language prompt
2. structured plan
3. Playwright execution
4. technical output
5. AI summary

Run a QA plan manually:

```bash
pnpm qa:run qa/plans/guestbook-happy.json
```

Technical results are written to `qa-output/<plan-id>.result.json`.

Generate a plan from a natural language prompt:

```bash
pnpm qa:plan --prompt "check that a user can submit the guestbook and the entry appears on the entries page"
```

Save the generated plan to a file:

```bash
pnpm qa:plan --prompt "check guestbook happy path" --output qa/plans/generated.json
```

Run the Playwright smoke tests:

```bash
pnpm test
```

Current checks include:

- homepage shows success status
- homepage shows server name
- personal greeting appears when `name` is passed in the query string
- guestbook entry can be saved
- entry can be edited and deleted
- entries page supports search and pagination
- entries page supports sorting and date filtering
- entries page renders data correctly
- QA dashboard page renders the planning form
- about page shows a concise project summary

## Environment

Environment files:

- `.env.local`
- `.env`

Important settings:

```bash
PLAYWRIGHT_BASE_URL=http://qaitest.test/
OPENAI_API_KEY=your_openai_api_key
OPENAI_MODEL=gpt-5.5
```

Use `.env.local` to override the URL or other values.

If you want lower cost plan generation, switch `OPENAI_MODEL` to a smaller model such as `gpt-5.4-mini`.

MySQL mode:

```bash
GUESTBOOK_STORAGE=mysql
GUESTBOOK_DB_HOST=127.0.0.1
GUESTBOOK_DB_PORT=3306
GUESTBOOK_DB_NAME=qaitest
GUESTBOOK_DB_USER=akusopo
GUESTBOOK_DB_PASSWORD=...
```

This mode activates automatically once `.env.local` is set up.

Current MySQL schema details:

- `created_at` uses `DATETIME(3)`
- `updated_at` is included
- indexes exist for `created_at`, `name + created_at`, and `updated_at`
- a manual SQL reference lives in `database/guestbook_schema.sql`

## File Structure

Key files:

- `index.php` for the homepage
- `about.php` for the about page
- `entries.php` for the data list
- `edit.php` for updates
- `app/bootstrap.php` for shared helpers
- `app/layout.php` for the reusable template
- `app/storage.php` for guestbook read/write logic
- `partials/footer.php` for the reusable footer
- `partials/topbar.php` for navigation
- `tests/*.spec.ts` for Playwright
- `playwright.config.ts` for test configuration
- `project_context.md` for system guidance

## Conventions

Project rules:

- keep it native PHP and simple
- escape every user-controlled output
- never render raw HTML from input
- prefer reusable pieces when adding pages
- add tests when adding features
- keep MySQL ready as the next path when data grows

## Recommended Flow

A clean workflow usually looks like this:

1. define the test intent in natural language
2. turn that intent into a structured plan
3. run the plan through Playwright
4. capture explicit technical output
5. write the AI summary at the end
6. add app features or more coverage if needed

Avoid overbuilding the architecture before there is a real need. This project works best when it stays small, clear, and easy to maintain.

## Next Steps

If you want to keep going, these are the most natural next moves:

1. add more advanced filters on entries
2. add simple auth if a private area is needed
3. add CI so `pnpm test` runs automatically
