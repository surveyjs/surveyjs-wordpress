const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");

const WP_CONTAINER = process.env.WP_CONTAINER;

function fixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/site-health.php")),
    stdio: ["pipe", "pipe", "ignore"]
  }).toString();
  return JSON.parse(output.trim().split("\n").pop());
}

test.describe("Site Health check of uploaded files", () => {
  test.skip(!WP_CONTAINER, "needs WP_CONTAINER to run PHP in the WordPress container");

  test("warns with the Nginx rule and the Local hint when the test file opens by its direct address", () => {
    const result = fixture("fake", 200);
    expect(result.status).toBe("recommended");
    expect(result.request.method).toBe("HEAD");
    expect(new URL(result.request.url).pathname).toBe(`${result.uploadsPath}/surveyjs-access-check.txt`);
    expect(result.description).toContain(`<code>location ^~ ${result.uploadsPath}/ { deny all; }</code>`);
    expect(result.description).toContain("conf/nginx/site.conf.hbs");
  });

  test("passes when the direct address is blocked", () => {
    const result = fixture("fake", 403);
    expect(result.status).toBe("good");
    expect(result.label).toBe("Files uploaded to SurveyJS forms are not open to the public");
  });

  test("skips certificate checks of the loopback request like core does, unless https_local_ssl_verify asks for them", () => {
    expect(fixture("fake", 403).request.sslverify).toBe(false);
    expect(fixture("fake", 403, "verify").request.sslverify).toBe(true);
  });

  test("agrees with a real request to the upload folder", async ({ request }) => {
    const { url } = fixture("direct-file");
    try {
      const direct = (await request.get(url, { maxRedirects: 0 })).status();
      const result = fixture("real");
      test.skip(/could not be checked/.test(result.label), "the container cannot reach the site by its own address");
      expect(result.status).toBe(direct === 200 ? "recommended" : "good");
    } finally {
      fixture("direct-file-cleanup");
    }
  });
});
