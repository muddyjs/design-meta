# DesignMeta 基础性能压测（占位）

未执行真实压测：缺少环境变量 `WP_BASE_URL` / `WP_USER` / `WP_APP_PASSWORD`。

可执行命令：

`WP_BASE_URL=http://localhost WP_USER=admin WP_APP_PASSWORD=xxxx POST_ID=1 bash scripts/checks/perf-baseline.sh`
