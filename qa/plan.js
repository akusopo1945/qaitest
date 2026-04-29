import fs from "node:fs/promises";
import path from "node:path";
import process from "node:process";
import { config as loadEnv } from "dotenv";
import { generatePlanFromPrompt } from "./providers/openai.js";

loadEnv({ path: ".env.local" });
loadEnv({ path: ".env" });

function parseArgs(argv) {
  const args = {
    prompt: "",
    baseUrl: process.env.PLAYWRIGHT_BASE_URL ?? "http://qaitest.test/",
    output: "",
  };

  for (let index = 0; index < argv.length; index += 1) {
    const value = argv[index];

    if (value === "--base-url") {
      args.baseUrl = argv[index + 1] ?? args.baseUrl;
      index += 1;
      continue;
    }

    if (value === "--output") {
      args.output = argv[index + 1] ?? "";
      index += 1;
      continue;
    }

    if (value === "--prompt") {
      args.prompt = argv.slice(index + 1).join(" ");
      break;
    }

    if (!value.startsWith("--") && args.prompt === "") {
      args.prompt = argv.slice(index).join(" ");
      break;
    }
  }

  return args;
}

async function loadProjectContext() {
  const contextPath = path.resolve("project_context.md");
  try {
    return await fs.readFile(contextPath, "utf8");
  } catch {
    return "";
  }
}

async function main() {
  const args = parseArgs(process.argv.slice(2));

  if (!args.prompt.trim()) {
    console.error("Usage: pnpm qa:plan --prompt \"natural language test\" [--base-url http://qaitest.test/] [--output qa/plans/generated.json]");
    process.exit(1);
  }

  const projectContext = await loadProjectContext();
  console.log("[qa-plan] Generating plan...");

  const { plan, model, response_id } = await generatePlanFromPrompt({
    prompt: args.prompt.trim(),
    baseUrl: args.baseUrl,
    projectContext,
  });

  const payload = {
    generated_at: new Date().toISOString(),
    model,
    response_id,
    plan,
  };

  const json = `${JSON.stringify(payload, null, 2)}\n`;

  if (args.output) {
    await fs.mkdir(path.dirname(args.output), { recursive: true });
    await fs.writeFile(args.output, json);
    console.log(`[qa-plan] Saved to ${args.output}`);
  } else {
    process.stdout.write(json);
  }
}

main().catch((error) => {
  console.error("[qa-plan] Failed to generate plan");
  console.error(error instanceof Error ? error.stack ?? error.message : String(error));
  process.exit(1);
});
