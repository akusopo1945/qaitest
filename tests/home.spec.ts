import { expect, test } from "@playwright/test";

test("home page renders success message", async ({ page }) => {
  await page.goto("/");

  await expect(page.locator('[data-testid="status-chip"]')).toContainText("Berhasil!");
  await expect(page.locator("footer")).toContainText("dev with");
  await expect(page.locator('[data-testid="entry-count"]')).toBeVisible();
  await expect(page.locator('[data-testid="demo-flow"]')).toBeVisible();
  await expect(page.locator('[data-testid="demo-summary"]')).toContainText("Entry guestbook tampil di halaman entries");
});
