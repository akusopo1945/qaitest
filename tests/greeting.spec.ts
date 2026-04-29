import { expect, test } from "@playwright/test";

test("home page renders a personalized greeting", async ({ page }) => {
  await page.goto("/?name=Akuncilik7");

  await expect(page.locator('[data-testid="visitor-greeting"]')).toContainText("Halo, Akuncilik7.");
});

