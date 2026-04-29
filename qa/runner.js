import fs from "node:fs/promises";
import path from "node:path";
import process from "node:process";
import { chromium, expect } from "@playwright/test";
import { config as loadEnv } from "dotenv";

loadEnv({ path: ".env.local" });
loadEnv({ path: ".env" });

const defaultBaseUrl = process.env.PLAYWRIGHT_BASE_URL ?? "http://qaitest.test/";

function resolveValueRef(plan, ref) {
  if (!ref) {
    return "";
  }

  if (ref.startsWith("input_data.")) {
    const key = ref.slice("input_data.".length);
    return String(plan.input_data?.[key] ?? "");
  }

  return String(plan[ref] ?? "");
}

function normalizeBaseUrl(baseUrl) {
  return baseUrl.endsWith("/") ? baseUrl : `${baseUrl}/`;
}

function joinUrl(baseUrl, url) {
  if (!url) {
    return normalizeBaseUrl(baseUrl);
  }

  if (/^https?:\/\//i.test(url)) {
    return url;
  }

  return new URL(url.replace(/^\//, ""), normalizeBaseUrl(baseUrl)).toString();
}

function toSelectorTarget(target) {
  if (!target) {
    return "";
  }

  if (target.startsWith("[") || target.startsWith("#") || target.startsWith(".")) {
    return target;
  }

  return target;
}

function prettyStep(step) {
  return `${step.id} (${step.type})${step.description ? ` - ${step.description}` : ""}`;
}

function prettyAssertion(assertion) {
  return `${assertion.id} (${assertion.type})`;
}

function validatePlan(plan) {
  const errors = [];

  if (!plan || typeof plan !== "object" || Array.isArray(plan)) {
    errors.push("Plan must be an object.");
    return errors;
  }

  for (const key of ["id", "title", "objective", "base_url"]) {
    if (typeof plan[key] !== "string" || plan[key].trim() === "") {
      errors.push(`Missing or invalid field: ${key}`);
    }
  }

  if (!Array.isArray(plan.steps) || plan.steps.length === 0) {
    errors.push("steps must be a non-empty array");
  }

  for (const [index, step] of (plan.steps ?? []).entries()) {
    if (!step || typeof step !== "object" || Array.isArray(step)) {
      errors.push(`steps[${index}] must be an object`);
      continue;
    }

    if (typeof step.id !== "string" || step.id.trim() === "") {
      errors.push(`steps[${index}].id is required`);
    }

    if (typeof step.type !== "string" || step.type.trim() === "") {
      errors.push(`steps[${index}].type is required`);
    }
  }

  return errors;
}

async function runPlan(plan) {
  const baseUrl = normalizeBaseUrl(plan.base_url ?? defaultBaseUrl);
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  const stepResults = [];
  const assertionResults = [];
  const cleanupResults = [];
  const startedAt = new Date().toISOString();

  try {
    for (const step of plan.steps) {
      const started = Date.now();
      const stepLog = {
        id: step.id,
        type: step.type,
        description: step.description ?? "",
        status: "passed",
        started_at: new Date(started).toISOString(),
      };

      try {
        switch (step.type) {
          case "navigate": {
            await page.goto(joinUrl(baseUrl, step.url ?? "/"));
            break;
          }
          case "fill": {
            const value = step.value ?? resolveValueRef(plan, step.value_ref);
            await page.getByLabel(step.target).fill(value);
            break;
          }
          case "click": {
            await page.getByRole("button", { name: step.target }).click();
            break;
          }
          case "select": {
            const value = step.value ?? resolveValueRef(plan, step.value_ref);
            await page.getByLabel(step.target).selectOption(value);
            break;
          }
          case "wait_for": {
            const target = toSelectorTarget(step.target);
            await page.locator(target).waitFor({ timeout: step.timeout_ms ?? 10000 });
            break;
          }
          case "assert_text": {
            const target = toSelectorTarget(step.target);
            const expected = step.contains_ref ? resolveValueRef(plan, step.contains_ref) : String(step.value ?? "");
            await expect(page.locator(target)).toContainText(expected);
            break;
          }
          case "assert_visible": {
            const target = toSelectorTarget(step.target);
            await expect(page.locator(target)).toBeVisible();
            break;
          }
          case "assert_count": {
            const target = toSelectorTarget(step.target);
            await expect(page.locator(target)).toHaveCount(Number(step.count ?? 0));
            break;
          }
          case "confirm_dialog": {
            page.once("dialog", async (dialog) => {
              if (step.accept === false) {
                await dialog.dismiss();
                return;
              }
              await dialog.accept();
            });
            break;
          }
          case "screenshot": {
            const filename = step.target ?? `step-${step.id}.png`;
            const screenshotPath = path.resolve("qa-output", filename);
            await fs.mkdir(path.dirname(screenshotPath), { recursive: true });
            await page.screenshot({ path: screenshotPath, fullPage: true });
            stepLog.artifact = screenshotPath;
            break;
          }
          case "custom": {
            throw new Error("custom steps are not implemented in the scaffold yet");
          }
          default:
            throw new Error(`Unsupported step type: ${step.type}`);
        }
      } catch (error) {
        stepLog.status = "failed";
        stepLog.error = error instanceof Error ? error.message : String(error);
        throw error;
      } finally {
        stepLog.duration_ms = Date.now() - started;
        stepResults.push(stepLog);
      }
    }

    for (const assertion of plan.assertions ?? []) {
      const started = Date.now();
      const assertionLog = {
        id: assertion.id,
        type: assertion.type,
        status: "passed",
        started_at: new Date(started).toISOString(),
      };

      try {
        switch (assertion.type) {
          case "url_contains": {
            const expected = String(assertion.value ?? "");
            await expect(page).toHaveURL(new RegExp(expected.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")));
            break;
          }
          case "text_contains": {
            const target = toSelectorTarget(assertion.target);
            const value = assertion.value_ref ? resolveValueRef(plan, assertion.value_ref) : String(assertion.value ?? "");
            await expect(page.locator(target)).toContainText(value);
            break;
          }
          case "visible": {
            const target = toSelectorTarget(assertion.target);
            await expect(page.locator(target)).toBeVisible();
            break;
          }
          case "count": {
            const target = toSelectorTarget(assertion.target);
            await expect(page.locator(target)).toHaveCount(Number(assertion.count ?? 0));
            break;
          }
          default:
            throw new Error(`Unsupported assertion type: ${assertion.type}`);
        }
      } catch (error) {
        assertionLog.status = "failed";
        assertionLog.error = error instanceof Error ? error.message : String(error);
        throw error;
      } finally {
        assertionLog.duration_ms = Date.now() - started;
        assertionResults.push(assertionLog);
      }
    }

    for (const cleanup of plan.cleanup ?? []) {
      const started = Date.now();
      const cleanupLog = {
        type: cleanup.type,
        status: "passed",
        started_at: new Date(started).toISOString(),
      };

      try {
        if (cleanup.type === "delete_created_entries") {
          const candidates = Object.values(plan.input_data ?? {})
            .map((value) => String(value))
            .filter(Boolean);
          const matchedTexts = candidates.length > 0 ? candidates : [plan.objective];

          await page.goto(joinUrl(baseUrl, "/entries.php"));

          for (const text of matchedTexts) {
            const row = page.locator("tbody tr").filter({ hasText: text }).first();
            if ((await row.count()) === 0) {
              continue;
            }

            await expect(row).toBeVisible();
            page.once("dialog", (dialog) => dialog.accept());
            await row.getByRole("button", { name: "Delete" }).click();
            await expect(page).toHaveURL(/deleted=1/);
            await page.goto(joinUrl(baseUrl, "/entries.php"));
          }
        } else {
          throw new Error(`Unsupported cleanup type: ${cleanup.type}`);
        }
      } catch (error) {
        cleanupLog.status = "failed";
        cleanupLog.error = error instanceof Error ? error.message : String(error);
        throw error;
      } finally {
        cleanupLog.duration_ms = Date.now() - started;
        cleanupResults.push(cleanupLog);
      }
    }
  } finally {
    await browser.close();
  }

  const finishedAt = new Date().toISOString();
  return {
    plan_id: plan.id,
    title: plan.title,
    objective: plan.objective,
    base_url: baseUrl,
    started_at: startedAt,
    finished_at: finishedAt,
    steps: stepResults,
    assertions: assertionResults,
    cleanup: cleanupResults,
    technical_summary: {
      total_steps: stepResults.length,
      passed_steps: stepResults.filter((step) => step.status === "passed").length,
      failed_steps: stepResults.filter((step) => step.status === "failed").length,
      total_assertions: assertionResults.length,
      passed_assertions: assertionResults.filter((assertion) => assertion.status === "passed").length,
      failed_assertions: assertionResults.filter((assertion) => assertion.status === "failed").length,
      total_cleanup_steps: cleanupResults.length,
      passed_cleanup_steps: cleanupResults.filter((item) => item.status === "passed").length,
      failed_cleanup_steps: cleanupResults.filter((item) => item.status === "failed").length,
    },
    ai_summary: null,
  };
}

async function main() {
  const planFile = process.argv[2];

  if (!planFile) {
    console.error("Usage: node qa/runner.js <plan.json>");
    process.exit(1);
  }

  const rawPlan = await fs.readFile(planFile, "utf8");
  const plan = JSON.parse(rawPlan);
  const errors = validatePlan(plan);

  if (errors.length > 0) {
    console.error("Plan validation failed:");
    for (const error of errors) {
      console.error(`- ${error}`);
    }
    process.exit(1);
  }

  console.log(`[qa] Running plan ${plan.id}: ${plan.title}`);
  for (const step of plan.steps) {
    console.log(`[qa] -> ${prettyStep(step)}`);
  }
  for (const assertion of plan.assertions ?? []) {
    console.log(`[qa] => ${prettyAssertion(assertion)}`);
  }

  try {
    const result = await runPlan(plan);
    await fs.mkdir("qa-output", { recursive: true });
    await fs.writeFile(
      path.join("qa-output", `${plan.id}.result.json`),
      `${JSON.stringify(result, null, 2)}\n`
    );
    console.log(`[qa] Plan finished successfully: ${plan.id}`);
    console.log(`[qa] Technical summary: ${JSON.stringify(result.technical_summary)}`);
  } catch (error) {
    console.error(`[qa] Plan failed: ${plan.id}`);
    console.error(error instanceof Error ? error.stack ?? error.message : String(error));
    process.exit(1);
  }
}

main();
