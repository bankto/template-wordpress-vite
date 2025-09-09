import gsap from 'gsap'

import '../stylesheets/app.scss'

// ページ初期化処理
const init = () => {}

// DOM読み込み完了時に初期化実行
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init, { once: true })
} else {
  init() // すでにDOM読み込み済みの場合は即実行
}
