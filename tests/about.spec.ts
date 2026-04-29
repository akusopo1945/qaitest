import { expect, test } from "@playwright/test";

test("about page renders footer credit", async ({ page }) => {
  await page.goto("/about.php");

  await expect(page).toHaveTitle("About - Qaitest");
  await expect(page.locator("footer")).toContainText("dev with");
});
