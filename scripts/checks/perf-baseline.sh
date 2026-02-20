#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

REPORT_FILE="${1:-doc/reports/perf-baseline.md}"
mkdir -p "$(dirname "$REPORT_FILE")"

WP_BASE_URL="${WP_BASE_URL:-}"
WP_USER="${WP_USER:-}"
WP_APP_PASSWORD="${WP_APP_PASSWORD:-}"
POST_ID="${POST_ID:-1}"
CONCURRENCY="${CONCURRENCY:-20}"
REQUESTS="${REQUESTS:-100}"

if [[ -z "$WP_BASE_URL" || -z "$WP_USER" || -z "$WP_APP_PASSWORD" ]]; then
  cat > "$REPORT_FILE" <<'EOF'
# DesignMeta 基础性能压测（占位）

未执行真实压测：缺少环境变量 `WP_BASE_URL` / `WP_USER` / `WP_APP_PASSWORD`。

可执行命令：

`WP_BASE_URL=http://localhost WP_USER=admin WP_APP_PASSWORD=xxxx POST_ID=1 bash scripts/checks/perf-baseline.sh`
EOF
  echo "Perf report written to: $REPORT_FILE"
  exit 0
fi

AUTH="$(printf '%s:%s' "$WP_USER" "$WP_APP_PASSWORD" | base64)"
READ_URL="$WP_BASE_URL/wp-json/wp/v2/posts/$POST_ID?context=edit"
MISS_URL="$WP_BASE_URL/wp-json/wp/v2/posts/99999999?context=edit"

read_hit_cmd="seq $REQUESTS | xargs -I{} -P $CONCURRENCY curl -s -o /dev/null -H 'Authorization: Basic $AUTH' '$READ_URL'"
read_miss_cmd="seq $REQUESTS | xargs -I{} -P $CONCURRENCY curl -s -o /dev/null -H 'Authorization: Basic $AUTH' '$MISS_URL'"
write_conflict_cmd="seq $REQUESTS | xargs -I{} -P $CONCURRENCY curl -s -o /dev/null -X POST -H 'Authorization: Basic $AUTH' -H 'Content-Type: application/json' '$WP_BASE_URL/wp-json/wp/v2/posts/$POST_ID' -d '{\"designer_url\":\"same-slug\",\"src_designer_url\":\"https://example.com/src\",\"pattern_url\":\"same-slug\",\"src_pattern_url\":\"https://example.com/src2\"}'"

{
  echo "# DesignMeta 基础性能压测报告"
  echo
  echo "生成时间：$(date '+%Y-%m-%d %H:%M:%S')"
  echo
  echo "参数：REQUESTS=$REQUESTS, CONCURRENCY=$CONCURRENCY, POST_ID=$POST_ID"
  echo
  echo "## 1) 读取（缓存命中）"
  echo '```bash'
  echo "$read_hit_cmd"
  echo '```'
  { time bash -lc "$read_hit_cmd"; } 2>&1
  echo
  echo "## 2) 读取（未命中）"
  echo '```bash'
  echo "$read_miss_cmd"
  echo '```'
  { time bash -lc "$read_miss_cmd"; } 2>&1
  echo
  echo "## 3) 并发写冲突（slug 冲突重试路径）"
  echo '```bash'
  echo "$write_conflict_cmd"
  echo '```'
  { time bash -lc "$write_conflict_cmd"; } 2>&1
} > "$REPORT_FILE"

echo "Perf report written to: $REPORT_FILE"
