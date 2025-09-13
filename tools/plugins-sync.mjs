#!/usr/bin/env node
import fs from 'node:fs'
import path from 'node:path'
import { execSync } from 'node:child_process'

const root = process.cwd()
const configPath = path.resolve(root, 'tools/plugins.config.json')

if (!fs.existsSync(configPath)) {
  console.error('[plugins-sync] tools/plugins.config.json not found')
  process.exit(1)
}

// 簡易 .env 読み込み（tools/.env → .env の順）
const envPaths = [path.resolve(root, 'tools/.env'), path.resolve(root, '.env')]
for (const p of envPaths) {
  if (fs.existsSync(p)) {
    const txt = fs.readFileSync(p, 'utf8')
    for (const line of txt.split(/\r?\n/)) {
      const m = line.match(/^([A-Z0-9_]+)=(.*)$/)
      if (m) {
        const key = m[1]
        let val = m[2]
        if (
          (val.startsWith('"') && val.endsWith('"')) ||
          (val.startsWith("'") && val.endsWith("'"))
        ) {
          val = val.slice(1, -1)
        }
        if (!process.env[key]) process.env[key] = val
      }
    }
  }
}

const rawConfig = fs.readFileSync(configPath, 'utf8')
// ${VAR} を環境変数で展開
const expanded = rawConfig.replace(/\$\{([A-Z0-9_]+)\}/g, (_, k) => process.env[k] ?? '')
const config = JSON.parse(expanded)
if (!config.plugins || !Array.isArray(config.plugins)) {
  console.error('[plugins-sync] Invalid config: missing plugins[]')
  process.exit(1)
}

const run = (cmd) => execSync(cmd, { stdio: 'inherit' })

for (const p of config.plugins) {
  const activate = p.activate ? ' --activate' : ''
  if (p.type === 'repo') {
    const version = p.version ? ` --version=${p.version}` : ''
    run(`wp-env run cli wp plugin install ${p.slug}${version}${activate} --force`)
  } else if (p.type === 'zip') {
    run(`wp-env run cli wp plugin install "${p.url}"${activate} --force`)
  } else if (p.type === 'local') {
    // wp-env の plugins マウントに依存。ここでは有効化のみ。
    run(`wp-env run cli wp plugin activate ${p.slug}`)
  } else {
    console.warn(`[plugins-sync] Unknown type: ${p.type}`)
  }
}

console.log('[plugins-sync] done')
