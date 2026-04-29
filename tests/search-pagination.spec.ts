import { expect, test } from "@playwright/test";

test("entries search and pagination work", async ({ page }) => {
  const runId = Date.now().toString(36);
  const prefix = `Pager-${runId}`;
  const entries = Array.from({ length: 6 }, (_, index) => ({
    message: `${prefix} message ${index + 1}`,
    name: `${prefix}-User${index + 1}`,
  }));

  for (const entry of entries) {
    await page.goto("/");
    await page.getByLabel("Nama pengunjung").fill(entry.name);
    await page.getByLabel("Pesan").fill(entry.message);
    await page.getByRole("button", { name: "Simpan ke guestbook" }).click();
    await expect(page).toHaveURL(/saved=1/);
  }

  const searchPath = `/entries.php?q=${encodeURIComponent(prefix)}`;
  await page.goto(searchPath);

  await expect(page.locator('[data-testid="entries-count"]')).toContainText("6");
  await expect(page.getByRole("link", { name: "Next" })).toBeVisible();
  await expect(page.locator("tbody tr")).toHaveCount(5);

  await page.getByRole("link", { name: "Next" }).click();
  await expect(page).toHaveURL(/page=2/);
  await expect(page.locator("tbody tr")).toHaveCount(1);

  await page.goto(`${searchPath}&page=1`);

  for (let index = 0; index < entries.length; index += 1) {
    const firstRow = page.locator("tbody tr").first();
    await expect(firstRow).toBeVisible();

    page.once("dialog", (dialog) => dialog.accept());
    await firstRow.getByRole("button", { name: "Delete" }).click();

    await expect(page).toHaveURL(/deleted=1/);
    await page.goto(`${searchPath}&page=1`);
  }

  await expect(page.locator('[data-testid="entries-empty"]')).toBeVisible();
});
