# DesignMeta 功能测试清单（可执行）

生成时间：2026-02-20 11:08:27

## 1) PHP 语法检查
```bash
for f in $(rg --files -g '*.php'); do php -l "$f"; done
```

No syntax errors detected in admin/admin-panel.php
No syntax errors detected in includes/class-dm-rest.php
No syntax errors detected in includes/class-dm-seo.php
No syntax errors detected in includes/class-dm-db.php
No syntax errors detected in includes/class-dm-helper.php
No syntax errors detected in includes/class-dm-repository.php
No syntax errors detected in designmeta.php

## 2) 结构与关键能力静态检查
```bash
rg -n 'register_activation_hook|dbDelta|dm_save_all_data|dm_get_data|wp_verify_nonce|current_user_can|register_rest_field|upload_dir|wp_head' designmeta.php includes/*.php admin/*.php
```

designmeta.php:44:function dm_save_all_data(int $post_id, array $input): void
designmeta.php:55:function dm_get_data(int $post_id): array
designmeta.php:60:register_activation_hook(__FILE__, ['DM_DB', 'activate']);
includes/class-dm-db.php:62:        dbDelta($sql);
includes/class-dm-db.php:65:            error_log('[DesignMeta] dbDelta error: ' . $wpdb->last_error);
includes/class-dm-db.php:82:     * Build CREATE TABLE SQL for dbDelta.
includes/class-dm-repository.php:33:        $uploads = wp_upload_dir();
includes/class-dm-repository.php:96:        $uploads = wp_upload_dir();
includes/class-dm-seo.php:14:        add_action('wp_head', [__CLASS__, 'render_meta_description']);
includes/class-dm-seo.php:15:        add_filter('upload_dir', [__CLASS__, 'filter_upload_dir']);
includes/class-dm-seo.php:34:        $data = dm_get_data((int) $post_id);
includes/class-dm-seo.php:49:    public static function filter_upload_dir(array $dirs): array
admin/admin-panel.php:73:        $data = dm_get_data((int) $post->ID);
admin/admin-panel.php:130:        if (! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
admin/admin-panel.php:134:        if (! current_user_can('edit_post', $post_id)) {
admin/admin-panel.php:143:        dm_save_all_data($post_id, $raw_fields);

## 3) REST 手工测试命令（需本地 WP 环境）
请先设置环境变量：WP_BASE_URL、WP_USER、WP_APP_PASSWORD、POST_ID

```bash
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
```

## 4) 媒体上传目录 X-Target-Date 手测
```bash
# 需准备本地文件 ./tmp/test.png
curl -sS -X POST -H "Authorization: Basic $AUTH" \
  -H 'Content-Disposition: attachment; filename="test.png"' \
  -H 'Content-Type: image/png' \
  -H 'X-Target-Date: 2024-12-25' \
  --data-binary @./tmp/test.png \
  "$WP_BASE_URL/wp-json/wp/v2/media" | jq '.id, .source_url'
```
