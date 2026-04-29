import fs from "node:fs/promises";
import path from "node:path";
import process from "node:process";
import OpenAI from "openai";
import { config as loadEnv } from "dotenv";

loadEnv({ path: ".env.local" });
loadEnv({ path: ".env" });

const DEFAULT_MODEL = process.env.OPENAI_MODEL ?? "gpt-5.5";

async function loadPlanSchema() {
  const schemaPath = path.resolve("qa/qa_plan.schema.json");
  const raw = await fs.readFile(schemaPath, "utf8");
  return JSON.parse(raw);
}

function buildPlanningPrompt({ prompt, baseUrl, projectContext }) {
  return [
    "You are a QA planner for a PHP guestbook app called Qaitest.",
    "Convert the user's natural-language test intent into a deterministic execution plan.",
    "Return only data that fits the provided JSON schema.",
    "Prefer selectors and labels that already exist in the app.",
    "The app routes and known UI affordances are:",
    "- Home: /",
    "- Entries: /entries.php",
    "- Edit: /edit.php?id=<entry-id>",
    "- About: /about.php",
    "- Homepage labels: 'Nama pengunjung', 'Pesan', button 'Simpan ke guestbook'",
    "- Entries page: search, sort, date filters, edit/delete buttons, pagination",
    "- Useful test ids: saved-notice, entries-count, entries-list, entries-empty, updated-notice, deleted-notice, status-chip, server-name, request-uri, visitor-greeting, entry-count, recent-entries",
    "Use cleanup steps when the plan creates data.",
    "Keep the plan compact but executable.",
    "",
    `Base URL: ${baseUrl}`,
    "",
    "Project context:",
    projectContext,
    "",
    "User prompt:",
    prompt,
  ].join("\n");
}

export async function generatePlanFromPrompt({ prompt, baseUrl, projectContext = "" }) {
  const apiKey = process.env.OPENAI_API_KEY;

  if (!apiKey) {
    throw new Error("OPENAI_API_KEY is not set");
  }

  const schema = await loadPlanSchema();
  const client = new OpenAI({ apiKey });
  const response = await client.responses.create({
    model: DEFAULT_MODEL,
    input: buildPlanningPrompt({
      prompt,
      baseUrl,
      projectContext,
    }),
    text: {
      format: {
        type: "json_schema",
        name: "qa_plan",
        strict: true,
        schema,
      },
    },
  });

  if (typeof response.output_text !== "string" || response.output_text.trim() === "") {
    throw new Error("OpenAI returned an empty plan payload");
  }

  const plan = JSON.parse(response.output_text);
  plan.provider_hint = plan.provider_hint ?? "openai";
  plan.base_url = baseUrl;

  return {
    plan,
    model: response.model ?? DEFAULT_MODEL,
    response_id: response.id,
  };
}
