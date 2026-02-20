#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

bash scripts/checks/functional-checklist.sh doc/reports/functional-checklist.md
bash scripts/checks/perf-baseline.sh doc/reports/perf-baseline.md

cat > doc/reports/test-report.md <<'MD'
# DesignMeta 测试报告（汇总）

- 功能测试清单：`doc/reports/functional-checklist.md`
- 性能基线压测：`doc/reports/perf-baseline.md`

> 说明：性能压测脚本不引入重依赖，仅依赖 `bash + curl + xargs + time`。
> 若未设置 WordPress 认证环境变量，会生成占位报告并给出可复现命令。
MD

echo "Combined report written to: doc/reports/test-report.md"
