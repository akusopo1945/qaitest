import { expect, test } from "@playwright/test";

test("home page renders the server name", async ({ page, baseURL }) => {
  await page.goto("/");

  const expectedHost = new URL(baseURL ?? "http://localhost").hostname;
  await expect(page.locator('[data-testid="server-name"]')).toContainText(expectedHost);
});
