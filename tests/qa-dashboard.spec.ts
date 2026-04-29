import { expect, test } from "@playwright/test";

test("qa dashboard renders the planning gui", async ({ page }) => {
  await page.goto("/qa.php");

  await expect(page.getByRole("heading", { name: "QA Dashboard" })).toBeVisible();
  await expect(page.getByTestId("qa-prompt")).toBeVisible();
  await expect(page.getByRole("button", { name: "Generate plan" })).toBeVisible();
  await expect(page.getByRole("button", { name: "Generate & run" })).toBeVisible();
  await expect(page.getByTestId("openai-status")).toBeVisible();
});
