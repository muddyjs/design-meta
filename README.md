# DesignMeta

WordPress 插件骨架与实现（字段管理、后台面板、REST、SEO、上传目录规则）。

## 本地测试与检查

### 1) 一键生成测试报告

```bash
bash scripts/checks/generate-test-report.sh
```

生成：
- `doc/reports/functional-checklist.md`
- `doc/reports/perf-baseline.md`
- `doc/reports/test-report.md`

### 2) 功能测试清单（spec 第11节）

```bash
bash scripts/checks/functional-checklist.sh
```

包含：
- PHP 语法检查
- 关键实现点静态扫描
- REST 读写/merge 手测 curl 命令
- 媒体上传 `X-Target-Date` 手测 curl 命令

### 3) 基础性能压测（spec 第11节）

```bash
WP_BASE_URL=http://localhost \
WP_USER=admin \
WP_APP_PASSWORD=xxxx \
POST_ID=1 \
REQUESTS=100 \
CONCURRENCY=20 \
bash scripts/checks/perf-baseline.sh
```

压测维度：
- 读取（缓存命中）
- 读取（未命中）
- 并发写冲突（slug 冲突重试路径）

> 脚本不引入重依赖，仅使用 `bash`、`curl`、`xargs`、`time`、`rg`、`php`。
