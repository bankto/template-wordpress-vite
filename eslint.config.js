import { FlatCompat } from '@eslint/eslintrc'
import js from '@eslint/js'
import globals from 'globals'
import prettier from 'eslint-config-prettier'

// コンパティビリティレイヤーを設定
const compat = new FlatCompat({
  baseDirectory: import.meta.url,
  recommendedConfig: js.configs.recommended,
})

export default [
  {
    ignores: [
      'node_modules/**',
      'wp/.vite/**',
      'wp/scripts/**',
      'wp/stylesheets/**',
      'plugins/**',
      'wp-content/**',
    ],
  },
  {
    files: ['**/*.js'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        ...globals.node,
        ...globals.es2022,
      },
    },
    rules: {
      // 必要に応じてルールを追加
      // 'no-console': 'warn', // console.logを警告
    },
  },
  prettier,
  ...compat.config({
    extends: ['eslint:recommended', 'prettier'],
  }),
]

