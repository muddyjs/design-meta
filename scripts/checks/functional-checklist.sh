#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

REPORT_FILE="${1:-doc/reports/functional-checklist.md}"
mkdir -p "$(dirname "$REPORT_FILE")"

{
  echo "# DesignMeta 功能测试清单（可执行）"
  echo
  echo "生成时间：$(date '+%Y-%m-%d %H:%M:%S')"
  echo
  echo "## 1) PHP 语法检查"
  echo '```bash'
  echo "for f in \$(rg --files -g '*.php'); do php -l \"\$f\"; done"
  echo '```'
  echo
  for f in $(rg --files -g '*.php'); do
    php -l "$f"
  done
  echo
  echo "## 2) 结构与关键能力静态检查"
  echo '```bash'
  echo "rg -n 'register_activation_hook|dbDelta|dm_save_all_data|dm_get_data|wp_verify_nonce|current_user_can|register_rest_field|upload_dir|wp_head' designmeta.php includes/*.php admin/*.php"
  echo '```'
  echo
  rg -n 'register_activation_hook|dbDelta|dm_save_all_data|dm_get_data|wp_verify_nonce|current_user_can|register_rest_field|upload_dir|wp_head' designmeta.php includes/*.php admin/*.php || true
  echo
  echo "## 3) REST 手工测试命令（需本地 WP 环境）"
  echo "请先设置环境变量：WP_BASE_URL、WP_USER、WP_APP_PASSWORD、POST_ID"
  echo
  echo '```bash'
  cat <<'CMD'
AUTH="$(printf '%s:%s' "$WP_USER" "$WP_APP_PASSWORD" | base64)"

# 读取8字段（GET）
curl -sS -H "Authorization: Basic $AUTH" \
  "$WP_BASE_URL/wp-json/wp/v2/posts/$POST_ID?context=edit" | jq '.id, .designer, .src_designer_url, .designer_url, .src_pattern_url, .pattern_url, .pin_url, .pin_info, .meta_description'

# merge更新（PATCH，仅传2字段）
curl -sS -X POST -H "Authorization: Basic $AUTH" -H 'Content-Type: application/json' \
  "$WP_BASE_URL/wp-json/wp/v2/posts/$POST_ID" \
  -d '{"designer":"demo-designer","meta_description":"demo description"}' | jq '.id, .designer, .meta_description'

# 再读验证未传字段保持原值
curl -sS -H "Authorization: Basic $AUTH" \
  "$WP_BASE_URL/wp-json/wp/v2/posts/$POST_ID?context=edit" | jq '.src_pattern_url, .pin_url'
CMD
  echo '```'
  echo
  echo "## 4) 媒体上传目录 X-Target-Date 手测"
  echo '```bash'
  cat <<'CMD'
# 需准备本地文件 ./tmp/test.png
curl -sS -X POST -H "Authorization: Basic $AUTH" \
  -H 'Content-Disposition: attachment; filename="test.png"' \
  -H 'Content-Type: image/png' \
  -H 'X-Target-Date: 2024-12-25' \
  --data-binary @./tmp/test.png \
  "$WP_BASE_URL/wp-json/wp/v2/media" | jq '.id, .source_url'
CMD
  echo '```'
} > "$REPORT_FILE"

echo "Functional checklist written to: $REPORT_FILE"
