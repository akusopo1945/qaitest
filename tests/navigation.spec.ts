import { expect, test } from "@playwright/test";

test("top navigation links exist and home is active on the homepage", async ({ page }) => {
  await page.goto("/");

  const topbar = page.locator('[data-testid="topbar"]');
  const homeLink = topbar.locator('a.topbar-link[href="/"]').filter({ hasText: "Home" });
  const entriesLink = topbar.locator('a.topbar-link[href="/entries.php"]');
  const qaLink = topbar.locator('a.topbar-link[href="/qa.php"]');
  const aboutLink = topbar.locator('a.topbar-link[href="/about.php"]');

  await expect(homeLink).toBeVisible();
  await expect(entriesLink).toBeVisible();
  await expect(qaLink).toBeVisible();
  await expect(aboutLink).toBeVisible();
  await expect(homeLink).toHaveClass(/is-active/);
});
