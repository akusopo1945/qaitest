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
  await expect(page.getByTestId("qa-provider")).toBeVisible();
  await expect(page.getByTestId("qa-provider")).toContainText("OpenAI API");
  await expect(page.getByTestId("qa-demo-provider-line")).toContainText("OpenAI API");
});

test("qa demo run stays interactive and always ends in success", async ({ page }) => {
  await page.goto("/qa.php");

  const prompt = page.getByTestId("qa-demo-prompt");
  const runButton = page.getByTestId("qa-demo-run-button");
  const status = page.getByTestId("qa-demo-status");
  const summary = page.getByTestId("qa-demo-summary-text");
  const providerSelect = page.getByTestId("qa-provider");
  const providerLine = page.getByTestId("qa-demo-provider-line");
  const modelLabel = page.getByTestId("qa-model-label");
  const modelInput = page.getByTestId("qa-model-input");

  await providerSelect.selectOption("Microsoft Azure OpenAI API");
  await expect(modelLabel).toContainText("Deployment name");
  await expect(modelInput).toHaveAttribute("placeholder", "gpt-5.5-prod");
  await expect(providerLine).toContainText("Microsoft Azure OpenAI API");

  await prompt.fill("Cek apa pun, hasil tetap sukses");
  await runButton.click();

  await expect(status).toHaveText("Success");
  await expect(summary).toContainText("Cek apa pun, hasil tetap sukses");
  await expect(summary).toContainText("Microsoft Azure OpenAI API");
  await expect(summary).toContainText("semua tahap simulasi lulus");
});
