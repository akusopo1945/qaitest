import { expect, test } from "@playwright/test";

test("entries sorting and date filters work", async ({ page }) => {
  const runId = Date.now().toString(36);
  const prefix = `Filter-${runId}`;
  const today = new Date().toISOString().slice(0, 10);
  const entries = [
    { name: `${prefix}-Zulu`, message: `${prefix} message zulu` },
    { name: `${prefix}-Alpha`, message: `${prefix} message alpha` },
    { name: `${prefix}-Bravo`, message: `${prefix} message bravo` },
  ];

  for (const entry of entries) {
    await page.goto("/");
    await page.getByLabel("Nama pengunjung").fill(entry.name);
    await page.getByLabel("Pesan").fill(entry.message);
    await page.getByRole("button", { name: "Simpan ke guestbook" }).click();
    await expect(page).toHaveURL(/saved=1/);
  }

  await page.goto(`/entries.php?q=${encodeURIComponent(prefix)}&sort=name_asc`);
  await expect(page.locator("tbody tr").first()).toContainText(`${prefix}-Alpha`);
  await expect(page.locator('[data-testid="entries-list"]')).toContainText(`${prefix}-Zulu`);

  await page.goto(`/entries.php?q=${encodeURIComponent(prefix)}&sort=name_desc`);
  await expect(page.locator("tbody tr").first()).toContainText(`${prefix}-Zulu`);

  await page.goto(`/entries.php?q=${encodeURIComponent(prefix)}&from=${today}&to=${today}&sort=newest`);
  await expect(page.locator("tbody tr")).toHaveCount(3);

  await page.goto(`/entries.php?q=${encodeURIComponent(prefix)}&from=2099-01-01&to=2099-01-02&sort=newest`);
  await expect(page.locator('[data-testid="entries-empty"]')).toBeVisible();

  await page.goto(`/entries.php?q=${encodeURIComponent(prefix)}&from=${today}&to=${today}&sort=newest`);

  for (let index = 0; index < entries.length; index += 1) {
    const firstRow = page.locator("tbody tr").first();
    await expect(firstRow).toBeVisible();

    page.once("dialog", (dialog) => dialog.accept());
    await firstRow.getByRole("button", { name: "Delete" }).click();

    await expect(page).toHaveURL(/deleted=1/);
    await page.goto(`/entries.php?q=${encodeURIComponent(prefix)}&from=${today}&to=${today}&sort=newest`);
  }

  await expect(page.locator('[data-testid="entries-empty"]')).toBeVisible();
});
