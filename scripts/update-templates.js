const fs = require("fs");
const os = require("os");
const path = require("path");
const { execFileSync } = require("child_process");

const REPOSITORY = "https://github.com/surveyjs/templates.git";
const root = path.join(__dirname, "..");
const target = path.join(root, "src", "trunk", "templates");

function git(args, cwd) {
  return execFileSync("git", args, { cwd, encoding: "utf8" }).trim();
}

function checkout() {
  if (process.argv[2]) return { dir: path.resolve(process.argv[2]), temporary: false };
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), "surveyjs-templates-"));
  git(["clone", "--quiet", "--depth", "1", REPOSITORY, dir]);
  return { dir, temporary: true };
}

function jsonFiles(dir, relative = "") {
  return fs.readdirSync(path.join(dir, relative), { withFileTypes: true }).flatMap((entry) => {
    const file = path.join(relative, entry.name);
    if (entry.name.startsWith(".") || entry.name === "node_modules" || entry.name === "local") return [];
    if (entry.isDirectory()) return jsonFiles(dir, file);
    return relative && entry.name.endsWith(".json") ? [file] : [];
  });
}

const source = checkout();
try {
  const files = jsonFiles(source.dir);
  for (const file of files) JSON.parse(fs.readFileSync(path.join(source.dir, file), "utf8"));
  fs.rmSync(target, { recursive: true, force: true });
  for (const file of files) {
    fs.mkdirSync(path.dirname(path.join(target, file)), { recursive: true });
    fs.copyFileSync(path.join(source.dir, file), path.join(target, file));
  }
  const commit = git(["rev-parse", "HEAD"], source.dir);
  fs.writeFileSync(path.join(target, "source.txt"), `${REPOSITORY} ${commit}\n`);
  console.log(`src/trunk/templates: ${files.length} files from ${commit}`);
} finally {
  if (source.temporary) fs.rmSync(source.dir, { recursive: true, force: true });
}
