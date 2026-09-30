/**
 * CloudLinux-friendly build (no native esbuild threads).
 * Usage: node scripts/build-no-esbuild.mjs
 */
import { mkdir, readFile, writeFile, readdir, unlink } from 'fs/promises';
import { createHash } from 'crypto';
import path from 'path';
import { fileURLToPath } from 'url';
import { rollup } from 'rollup';
import vue from '@vitejs/plugin-vue';
import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import replace from '@rollup/plugin-replace';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const outDir = path.join(root, 'public/build');
const assetsDir = path.join(outDir, 'assets');

function hashContent(buf) {
  return createHash('sha256').update(Buffer.isBuffer(buf) ? buf : Buffer.from(buf)).digest('hex').slice(0, 8);
}

async function emptyAssets() {
  await mkdir(assetsDir, { recursive: true });
  const files = await readdir(assetsDir);
  await Promise.all(files.map((f) => unlink(path.join(assetsDir, f)).catch(() => {})));
}

function cssCollector(collected) {
  return {
    name: 'css-collector',
    transform(code, id) {
      const clean = id.split('?')[0];
      if (!clean.endsWith('.css') && !/\?vue&type=style/.test(id)) return null;
      collected.push(code);
      return { code: 'export default {}', map: { mappings: '' } };
    },
  };
}

async function loadPreviousTailwind() {
  const candidates = [];
  try {
    const files = await readdir(path.join(root, 'public/build/assets'));
    for (const f of files) {
      if (f.startsWith('app-') && f.endsWith('.css')) {
        candidates.push(path.join(root, 'public/build/assets', f));
      }
    }
  } catch {}

  let best = null;
  let bestLen = 0;
  for (const p of candidates) {
    try {
      const buf = await readFile(p);
      // Prefer a base that already looks like Tailwind (has utilities),
      // but strip previously appended layout blocks to avoid duplication.
      const stripped = stripLayoutBlocks(buf.toString('utf8'));
      if (stripped.length > bestLen) {
        best = Buffer.from(stripped);
        bestLen = stripped.length;
      }
    } catch {}
  }
  return best || Buffer.from('/* no prior tailwind */\n');
}

function stripLayoutBlocks(css) {
  // Remove marked layout blocks (current + historical duplicates)
  let out = css.replace(
    /\/\* === BATIXPER LAYOUT START === \*\/[\s\S]*?\/\* === BATIXPER LAYOUT END === \*\//g,
    ''
  );
  // Also strip unmarked historical copies that start with the old header
  out = out.replace(
    /\/\* Critical layout — independent of Tailwind scan \*\/[\s\S]*?(?=\/\* Critical layout — independent of Tailwind scan \*\/|\/\* === BATIXPER LAYOUT START === \*\/|$)/g,
    ''
  );
  return out.replace(/\n{3,}/g, '\n\n').trim() + '\n';
}

async function tryCompileTailwind() {
  // Oxide/rayon thread pool fails on CloudLinux LVE — skip native compile.
  return null;
}

async function run() {
  const collectedCss = [];
  // Ensure layout.css is always present even if import graph changes
  const layoutCss = await readFile(path.join(root, 'resources/css/layout.css'), 'utf8');

  const bundle = await rollup({
    input: path.join(root, 'resources/js/app.js'),
    plugins: [
      replace({
        preventAssignment: true,
        values: {
          'process.env.NODE_ENV': JSON.stringify('production'),
          __VUE_OPTIONS_API__: 'true',
          __VUE_PROD_DEVTOOLS__: 'false',
          __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false',
        },
      }),
      vue({ template: { transformAssetUrls: false } }),
      cssCollector(collectedCss),
      resolve({ browser: true, extensions: ['.mjs', '.js', '.json', '.vue'] }),
      commonjs(),
    ],
    onwarn(warning, warn) {
      if (warning.code === 'CIRCULAR_DEPENDENCY') return;
      warn(warning);
    },
  });

  const { output } = await bundle.generate({
    format: 'es',
    sourcemap: false,
    inlineDynamicImports: true,
  });
  await bundle.close();

  const chunk = output.find((o) => o.type === 'chunk');
  const jsName = `app-${hashContent(chunk.code)}.js`;

  const tw = (await tryCompileTailwind()) || (await loadPreviousTailwind());
  const hasLayoutInCollected = collectedCss.some((c) => c.includes('BATIXPER LAYOUT START') || c.includes('.app-sidebar'));
  const cssParts = [tw.toString('utf8'), '\n', ...collectedCss];
  if (!hasLayoutInCollected) cssParts.push('\n', layoutCss);
  const cssText = cssParts.join('\n');
  const cssName = `app-${hashContent(cssText)}.css`;

  await emptyAssets();
  await writeFile(path.join(assetsDir, cssName), cssText);
  await writeFile(path.join(assetsDir, jsName), chunk.code);

  const manifest = {
    'resources/css/app.css': {
      file: `assets/${cssName}`,
      src: 'resources/css/app.css',
      isEntry: true,
      name: 'app',
      names: ['app.css'],
    },
    'resources/js/app.js': {
      file: `assets/${jsName}`,
      name: 'app',
      src: 'resources/js/app.js',
      isEntry: true,
      css: [`assets/${cssName}`],
    },
  };

  await writeFile(path.join(outDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log('Built', jsName, cssName);
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
