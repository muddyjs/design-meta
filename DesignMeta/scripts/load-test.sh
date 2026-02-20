#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://localhost}"
POST_ID="${2:-1}"

echo "[DesignMeta] Read test (cache miss/hit simulation)"
for i in $(seq 1 100); do
  curl -sS "${BASE_URL}/wp-json/wp/v2/posts/${POST_ID}" > /dev/null
done

echo "[DesignMeta] Write test (merge updates)"
for i in $(seq 1 50); do
  curl -sS -X POST "${BASE_URL}/wp-json/wp/v2/posts/${POST_ID}" \
    -H 'Content-Type: application/json' \
    -d '{"designer":"load-user-'"$i"'","meta_description":"load-meta-'"$i"'"}' > /dev/null
done

echo "Done"
