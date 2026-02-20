# DesignMeta

## 安装
1. 将 `DesignMeta` 目录放入 `wp-content/plugins/`。
2. 在 WordPress 后台启用 **DesignMeta** 插件。
3. 激活后会自动创建 `{$wpdb->prefix}dm_meta` 数据表。

## 后台使用
- 在文章编辑页可见 **DesignMeta Fields** 面板。
- 可编辑 8 个字段：
  - `designer`
  - `src_designer_url`
  - `designer_url`
  - `src_pattern_url`
  - `pattern_url`
  - `pin_url`
  - `pin_info`
  - `meta_description`
- `pin_url` 支持媒体库选择并即时图片预览。

## REST 示例
读取：
```bash
curl https://example.com/wp-json/wp/v2/posts/123
```
返回中可读取 8 个字段。

更新（merge 语义，仅更新传入字段）：
```bash
curl -X POST https://example.com/wp-json/wp/v2/posts/123 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "designer": "Alice",
    "meta_description": "SEO text"
  }'
```

## 媒体上传目录
REST 上传媒体时可传请求头：
```text
X-Target-Date: 2026/02
```
插件会将上传路径定向到 `uploads/2026/02`。

## SEO 输出
- 单篇文章页面自动输出 `<meta name="description">`。
- 数据来源 `meta_description`，为空时不输出。
