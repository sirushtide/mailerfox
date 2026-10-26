#!/usr/bin/env node
/**
 * MailPurse addon packager.
 *
 * Packages one or more addons from packages/ into distributable ZIPs.
 * The ZIP is staged with a single top-level folder named after the
 * addon.json "slug" so the installer always resolves the correct root.
 *
 * Usage:
 *   node scripts/build-addon.mjs --all
 *   node scripts/build-addon.mjs --slug sms
 *   node scripts/build-addon.mjs --slug sms --out dist/addons
 *
 * Notes:
 *   - The output file is named <slug>.zip (matches the existing
 *     convention used by the update server).
 *   - Junk files (.DS_Store, __MACOSX, AppleDouble ._*) are excluded.
 *   - A package must contain addon.json; bootstrap.php is strongly
 *     recommended and a warning is printed when missing.
 */

import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import fsp from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(__dirname, '..');
const packagesDir = path.join(projectRoot, 'packages');

const hasFlag = (flag) => process.argv.includes(flag);
const argValue = (flag, fallback = null) => {
    const index = process.argv.indexOf(flag);
    return index !== -1 && process.argv[index + 1] ? process.argv[index + 1] : fallback;
};

function run(command, args, options = {}) {
    const result = spawnSync(command, args, { stdio: 'inherit', ...options });
    if (result.status !== 0) {
        throw new Error(`Command failed (${result.status}): ${command} ${args.join(' ')}`);
    }
}

function isJunk(baseName) {
    return baseName === '.DS_Store' || baseName === '__MACOSX' || baseName.startsWith('._');
}

async function discoverAddons() {
    const entries = await fsp.readdir(packagesDir, { withFileTypes: true });
    const addons = [];

    for (const entry of entries) {
        if (!entry.isDirectory() || entry.name.startsWith('.') || entry.name === '__MACOSX') {
            continue;
        }

        const manifestPath = path.join(packagesDir, entry.name, 'addon.json');
        if (!fs.existsSync(manifestPath)) {
            continue;
        }

        let manifest;
        try {
            manifest = JSON.parse(await fsp.readFile(manifestPath, 'utf8'));
        } catch (error) {
            console.warn(`Skipping ${entry.name}: invalid addon.json (${error.message})`);
            continue;
        }

        if (!manifest.slug || !manifest.name) {
            console.warn(`Skipping ${entry.name}: addon.json is missing "slug" and/or "name".`);
            continue;
        }

        addons.push({
            dirName: entry.name,
            sourceDir: path.join(packagesDir, entry.name),
            slug: String(manifest.slug).toLowerCase().replace(/[^a-z0-9-]/g, ''),
            name: manifest.name,
            version: manifest.version || '1.0.0',
        });
    }

    return addons;
}

async function buildAddon(addon, outDir) {
    if (!fs.existsSync(path.join(addon.sourceDir, 'bootstrap.php'))) {
        console.warn(`  ! ${addon.slug}: bootstrap.php not found (addon may not register anything).`);
    }

    const tmpDir = await fsp.mkdtemp(path.join(os.tmpdir(), `mailpurse-addon-${addon.slug}-`));
    const stagedDir = path.join(tmpDir, addon.slug);

    await fsp.cp(addon.sourceDir, stagedDir, {
        recursive: true,
        filter: (src) => !isJunk(path.basename(src)),
    });

    const zipPath = path.join(outDir, `${addon.slug}.zip`);
    await fsp.rm(zipPath, { force: true });
    run('zip', ['-rq', zipPath, addon.slug], { cwd: tmpDir });

    await fsp.rm(tmpDir, { recursive: true, force: true });

    const stats = await fsp.stat(zipPath);
    console.log(`  ✓ ${addon.slug} v${addon.version} -> ${path.relative(projectRoot, zipPath)} (${(stats.size / 1024).toFixed(1)} KB)`);

    return { slug: addon.slug, version: addon.version, zipPath };
}

async function main() {
    const all = hasFlag('--all');
    const slug = argValue('--slug');
    const outDir = path.resolve(projectRoot, argValue('--out', 'packages'));

    if (!all && !slug) {
        console.error('Nothing to do. Use --all or --slug <slug>.');
        process.exit(1);
    }

    if (!fs.existsSync(packagesDir)) {
        console.error(`packages/ directory not found at ${packagesDir}`);
        process.exit(1);
    }

    await fsp.mkdir(outDir, { recursive: true });

    const addons = await discoverAddons();
    const selected = all ? addons : addons.filter((a) => a.slug === String(slug).toLowerCase());

    if (selected.length === 0) {
        console.error(`No matching addon found for ${all ? '--all' : `--slug ${slug}`}.`);
        process.exit(1);
    }

    console.log(`Packaging ${selected.length} addon(s) into ${path.relative(projectRoot, outDir) || '.'}/`);

    const results = [];
    for (const addon of selected) {
        results.push(await buildAddon(addon, outDir));
    }

    console.log(`\nDone. Built ${results.length} addon package(s).`);
    console.log('Ship each ZIP to your update server product and install via Admin → Addons.');
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
