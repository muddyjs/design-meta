# DesignMeta 插件技术规格（spec）

> 目标：实现一个面向内容型站点的高性能 WordPress 插件，满足 `50,000 Posts + 日 100,000 IP` 场景。

## 1. 产品目标

1. 为文章提供结构化扩展字段管理能力。
2. 提供后台编辑体验与 REST API 能力。
3. 在前台输出 SEO 相关元信息。
4. 提供可控的媒体上传目录规则。
5. 在高并发流量下保持低延迟与稳定性。

---

## 2. 功能定义

### 2.1 字段模型
插件维护以下 8 个字段：

- `designer`（设计师名称）
- `src_designer_url`（设计师外链源地址）
- `designer_url`（站内设计师跳转 slug）
- `src_pattern_url`（下载外链源地址）
- `pattern_url`（站内下载跳转 slug）
- `pin_url`（资源图片地址）
- `pin_info`（资源描述）
- `meta_description`（SEO 描述）

### 2.2 后台管理功能
1. 在文章编辑页提供单独信息面板。
2. 支持填写/更新 8 个字段并即时展示图片预览。
3. 支持 WordPress 媒体库选择图片并自动回填 URL。
4. 支持站内跳转链接预览。

### 2.3 REST API 功能
1. 8 个字段全部可通过 REST 读取。
2. 8 个字段支持 REST 更新。
3. REST 更新采用 merge 语义：只更新传入字段，未传字段保持原值。

### 2.4 前台输出功能
1. 单篇文章页面输出 `<meta name="description">`。
2. 内容来源为 `meta_description`，为空时不输出该标签。

### 2.5 媒体上传目录功能
1. 在 REST 媒体上传请求中读取请求头 `X-Target-Date`。
2. 按 `YYYY/MM` 规则动态写入 uploads 子目录。
3. 未提供请求头时走 WordPress 默认目录策略。

---

## 3. 非功能需求（性能与容量）

### 3.1 容量目标
- 数据规模：50,000 篇文章。
- 访问规模：日 100,000 IP。

### 3.2 性能目标（P95）
- 单条扩展数据读取（缓存命中）：< 2ms。
- 单条扩展数据读取（数据库）：< 10ms。
- 单条保存（后台或 REST）：< 30ms。

### 3.3 稳定性目标
- 关键读路径错误率 < 0.1%。
- 插件逻辑不引入额外远程依赖。
- 并发写入保持最终一致。

---

## 4. 系统架构

### 4.1 目录结构（建议）
```text
DesignMeta/
├── designmeta.php                    # 插件入口
├── includes/
│   ├── class-dm-helper.php           # 字段、清洗、路径工具
│   ├── class-dm-db.php               # 表结构与底层 SQL
│   ├── class-dm-repository.php       # 业务读写聚合层
│   ├── class-dm-rest.php             # REST 字段注册与更新
│   └── class-dm-seo.php              # 前台 SEO 输出
└── admin/
    └── admin-panel.php               # 编辑页 UI
```

### 4.2 分层职责
1. **Helper 层**：字段白名单、sanitize 规则、路径转换。
2. **DB 层**：建表、索引、预编译 SQL。
3. **Repository 层**：统一读写 API、缓存处理、冲突重试。
4. **REST/Admin/SEO 层**：只处理入口协议，不直接拼 SQL。

---

## 5. 数据存储设计

### 5.1 存储策略
- 使用独立业务表存储插件数据。
- 每篇文章对应一行，避免多行聚合查询开销。

### 5.2 数据表结构
表名：`{$wpdb->prefix}dm_meta`

```sql
CREATE TABLE `wp_dm_meta` (
  `post_id` BIGINT UNSIGNED NOT NULL,
  `designer` VARCHAR(191) NOT NULL DEFAULT '',
  `src_designer_url` TEXT NULL,
  `designer_slug` VARCHAR(191) NULL,
  `src_pattern_url` TEXT NULL,
  `pattern_slug` VARCHAR(191) NULL,
  `pin_path` VARCHAR(512) NOT NULL DEFAULT '',
  `pin_info` MEDIUMTEXT NULL,
  `meta_description` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  UNIQUE KEY `uniq_designer_slug` (`designer_slug`),
  UNIQUE KEY `uniq_pattern_slug` (`pattern_slug`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5.3 字段映射
- `designer_url` ↔ `designer_slug`
- `pattern_url` ↔ `pattern_slug`
- `pin_url` ↔ `pin_path`

---

## 6. 数据处理规则

### 6.1 清洗规则
- `designer`: `sanitize_text_field`
- `src_designer_url`: `esc_url_raw`
- `designer_url`: `sanitize_title`
- `src_pattern_url`: `esc_url_raw`
- `pattern_url`: `sanitize_title`
- `pin_url`: `sanitize_text_field`（后续做路径转换）
- `pin_info`: `sanitize_textarea_field`
- `meta_description`: `sanitize_textarea_field`

### 6.2 slug 生成规则
1. 对 `designer_url`、`pattern_url` 执行 slug 化。
2. 设计师 slug 后缀改为基于**无天然同秒碰撞**的组合输入：
   - `designer_hash_input = post_id + "|" + gmdate('Y-m-d H:i:s')`
   - `designer_hash = substr(md5(designer_hash_input), 0, 4)`
   - 生成：`designer_slug = sanitize_title(designer_url) + "-" + designer_hash`
3. 下载 slug（`pattern_url`）沿用 source URL 4~6 位短哈希。
4. 若唯一索引冲突，追加递增后缀 `-2`, `-3`...（最多 20 次）。
5. 超过重试上限时返回可观测错误日志。

> 说明：单独 `md5(time)` 会在同一秒天然碰撞；把 `post_id`（全局唯一）并入输入后，同秒写入不同 pattern 也不会因时间相同而碰撞。

### 6.3 图片路径规则
1. 入库时将 uploads 绝对 URL 转为相对路径 `pin_path`。
2. 出库时还原绝对 URL `pin_url` 用于展示。

---

## 7. 核心 API 设计

### 7.1 统一保存 API
`dm_save_all_data(int $post_id, array $input): void`

流程：
1. 白名单提取。
2. sanitize。
3. slug 规则处理与冲突重试。
4. `pin_url` 转 `pin_path`。
5. `INSERT ... ON DUPLICATE KEY UPDATE` 原子写入。
6. 清理缓存。

### 7.2 统一读取 API
`dm_get_data(int $post_id): array`

流程：
1. 读取缓存。
2. 未命中则查表。
3. 字段映射回 8 字段输出。
4. 缺省值补空字符串。

---

## 8. 缓存与并发策略

### 8.1 缓存策略
- Object Cache Key：`dm:post:{post_id}`
- Group：`designmeta`
- 写入后删除该 key，读时回填。

### 8.2 并发策略
1. 以 `post_id` 为幂等主键做 upsert。
2. slug 冲突按唯一索引 + 重试机制解决。
3. 对热点文章更新可增加短暂互斥锁（可选）。

---

## 9. 安全要求

1. 后台保存必须校验 nonce。
2. 后台保存必须校验 `edit_post` 能力。
3. 所有 SQL 通过 `$wpdb->prepare`。
4. 所有前端输出执行 `esc_*`。
5. REST 写入仅允许白名单字段。

---

## 10. 生命周期与事件

1. 插件激活时执行建表（`dbDelta`）。
2. 文章保存时触发统一保存。
3. REST 更新时触发统一保存。
4. 文章删除时删除对应数据行，避免孤儿数据。

---

## 11. 测试计划

### 11.1 功能测试
1. 后台编辑保存 8 字段并回显一致。
2. REST 创建/更新/读取字段一致。
3. 单篇页面 SEO 描述输出正确。
4. 媒体目录按 `X-Target-Date` 生效。

### 11.2 性能测试
1. 准备 50,000 post 数据集。
2. 压测读取场景：缓存命中与未命中。
3. 压测写入场景：并发更新 + slug 冲突。

### 11.3 稳定性测试
1. 并发更新同一 `post_id` 验证最终一致。
2. 连续高频读写 24h 观察错误率与延迟。
3. 异常输入（非法 URL/超长文本）验证清洗效果。

---

## 12. 交付物

1. 可安装插件代码包。
2. `spec.md`（本技术规格）。
3. `README.md`（安装、配置、REST 示例）。
4. 压测脚本与测试报告。
