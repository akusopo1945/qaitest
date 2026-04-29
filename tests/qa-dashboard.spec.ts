import { expect, test } from "@playwright/test";

test("qa dashboard renders the planning gui", async ({ page }) => {
  await page.goto("/qa.php");

  await expect(page.getByRole("heading", { name: "QA Dashboard" })).toBeVisible();
  await expect(page.getByTestId("qa-prompt")).toBeVisible();
  await expect(page.getByRole("button", { name: "Generate plan" })).toBeVisible();
  await expect(page.getByRole("button", { name: "Generate & run" })).toBeVisible();
  await expect(page.getByTestId("openai-status")).toBeVisible();
  await expect(page.getByTestId("ai-api-list")).toContainText("OpenAI API");
  await expect(page.getByTestId("ai-api-list")).toContainText("Amazon Web Services Bedrock API");
});

test("qa demo run stays interactive and always ends in success", async ({ page }) => {
  await page.goto("/qa.php");

  const prompt = page.getByTestId("qa-demo-prompt");
  const runButton = page.getByTestId("qa-demo-run-button");
  const status = page.getByTestId("qa-demo-status");
  const summary = page.getByTestId("qa-demo-summary-text");

  await prompt.fill("Cek apa pun, hasil tetap sukses");
  await runButton.click();

  await expect(status).toHaveText("Success");
  await expect(summary).toContainText("Cek apa pun, hasil tetap sukses");
  await expect(summary).toContainText("semua tahap simulasi lulus");
});
