const fs = require("fs");
const path = require("path");

const target = path.resolve(
  process.cwd(),
  "node_modules/next/dist/build/generate-build-id.js"
);

const originalSnippet =
  "async function generateBuildId(generate, fallback) {\n    let buildId = await generate();";

const patchedSnippet =
  "async function generateBuildId(generate, fallback) {\n    const generator = typeof generate === 'function' ? generate : async () => null;\n    let buildId = await generator();";

if (fs.existsSync(target)) {
  const content = fs.readFileSync(target, "utf8");
  if (content.includes(originalSnippet) && !content.includes("typeof generate === 'function'")) {
    const updated = content.replace(originalSnippet, patchedSnippet);
    fs.writeFileSync(target, updated, "utf8");
    console.log("Patched Next.js generate-build-id fallback to guard missing function.");
  } else {
    console.log("Next.js generate-build-id already patched or unexpected format.");
  }
} else {
  console.warn("generate-build-id.js not found; skipping patch.");
}
