import { expect, test } from "@playwright/test";

test("guestbook entry can be saved and listed", async ({ page }) => {
  const name = "Tester";
  const message = `Playwright note ${Date.now()}`;
  const editedMessage = `${message} edited`;

  await page.goto("/");
  await page.getByLabel("Nama pengunjung").fill(name);
  await page.getByLabel("Pesan").fill(message);
  await page.getByRole("button", { name: "Simpan ke guestbook" }).click();

  await expect(page).toHaveURL(/saved=1/);
  await expect(page.locator('[data-testid="saved-notice"]')).toContainText("Data kamu sudah disimpan");
  await expect(page.locator('[data-testid="recent-entries"]')).toContainText(message);

  await page.goto("/entries.php");
  await expect(page.locator('[data-testid="entries-list"]')).toContainText(message);
  await expect(page.locator('[data-testid="entries-list"]')).toContainText(name);

  const originalRow = page.locator("tr").filter({ hasText: message });
  await originalRow.getByRole("link", { name: "Edit" }).click();

  await expect(page).toHaveURL(/\/edit\.php\?id=/);
  await page.getByLabel("Nama pengunjung").fill(name);
  await page.getByLabel("Pesan").fill(editedMessage);
  await page.getByRole("button", { name: "Update entry" }).click();

  await expect(page).toHaveURL(/updated=1/);
  await expect(page.locator('[data-testid="updated-notice"]')).toContainText("Entry berhasil diperbarui");
  await expect(page.locator('[data-testid="entries-list"]')).toContainText(editedMessage);

  const updatedRow = page.locator("tr").filter({ hasText: editedMessage });
  page.once("dialog", (dialog) => dialog.accept());
  await updatedRow.getByRole("button", { name: "Delete" }).click();

  await expect(page).toHaveURL(/deleted=1/);
  await expect(page.locator('[data-testid="deleted-notice"]')).toContainText("Entry berhasil dihapus");
  await expect(page.locator("body")).not.toContainText(editedMessage);
});
