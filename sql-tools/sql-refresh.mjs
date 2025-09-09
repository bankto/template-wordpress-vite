import { execSync } from 'node:child_process'
import fs from 'node:fs'
import path from 'node:path'

const projectRoot = process.cwd()
const configPath = path.resolve(projectRoot, 'sql-tools/sql.config.json')

const readConfig = () => {
  try {
    const raw = fs.readFileSync(configPath, 'utf8')
    return JSON.parse(raw)
  } catch (_) {
    return {}
  }
}

const parseArgs = () => {
  const result = {}
  for (const arg of process.argv.slice(2)) {
    if (arg.startsWith('--sql=')) result.sql = arg.slice('--sql='.length)
    else if (arg.startsWith('--from=')) result.sourceUrl = arg.slice('--from='.length)
    else if (arg.startsWith('--to=')) result.localUrl = arg.slice('--to='.length)
  }
  return result
}

const cfg = readConfig()
const cli = parseArgs()

// 固有名詞に依存しない。必須値（sql, sourceUrl）は設定/引数/環境変数から取得。
const sql = process.env.DB_SQL || cli.sql || cfg.sql
const sourceUrl = process.env.SOURCE_URL || cli.sourceUrl || cfg.sourceUrl
const localUrl = process.env.LOCAL_URL || cli.localUrl || cfg.localUrl || 'http://localhost:8888'

if (!sql) {
  console.error(
    '[sql-refresh] Missing SQL path. Set sql-tools/sql.config.json "sql" or pass --sql or DB_SQL'
  )
  process.exit(1)
}
if (!sourceUrl) {
  console.error(
    '[sql-refresh] Missing sourceUrl. Set sql-tools/sql.config.json "sourceUrl" or pass --from or SOURCE_URL'
  )
  process.exit(1)
}

if (!fs.existsSync(path.resolve(projectRoot, sql))) {
  console.error(`[sql-refresh] SQL file not found: ${sql}`)
  process.exit(1)
}

const sh = String.raw
const shellCmd = sh`wp db reset --yes && wp db import '${sql}' && wp search-replace '${sourceUrl}' '${localUrl}' && wp cache flush`

try {
  execSync(`wp-env run cli sh -lc "${shellCmd}"`, { stdio: 'inherit' })
  try {
    execSync('npm run wp:restart', { stdio: 'inherit' })
  } catch (_) {
    // フォールバック（直接 stop/start）
    execSync('wp-env stop', { stdio: 'inherit' })
    execSync('wp-env start', { stdio: 'inherit' })
  }
} catch (err) {
  process.exit(err.status || 1)
}
