import buildModule from "next/dist/build/index.js";

const build = buildModule.default ?? buildModule;
const cwd = process.cwd();

build(cwd, false, false)
  .then(() => {
    console.log("Build succeeded.");
  })
  .catch((error) => {
    console.error("Build failed:");
    console.error(error);
    if (error && error.stack) {
      console.error("Stack:");
      console.error(error.stack);
    }
    process.exit(1);
  });
