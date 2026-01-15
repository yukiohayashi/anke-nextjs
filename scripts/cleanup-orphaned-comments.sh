#!/bin/bash

# 孤立したコメントを削除するスクリプト
# 削除記事に対するコメントをすべて削除します

echo "=========================================="
echo "孤立したコメント削除スクリプト"
echo "=========================================="
echo ""

# データベースコンテナ名
DB_CONTAINER="supabase_db_anke-nextjs-dev"

# 削除前の件数を確認
echo "【削除前の状態】"
echo "総投稿数:"
docker exec $DB_CONTAINER psql -U postgres -d postgres -t -c "SELECT COUNT(*) FROM posts;"

echo "総コメント数:"
docker exec $DB_CONTAINER psql -U postgres -d postgres -t -c "SELECT COUNT(*) FROM comments;"

echo "孤立したコメント数:"
ORPHANED_COUNT=$(docker exec $DB_CONTAINER psql -U postgres -d postgres -t -c "SELECT COUNT(*) FROM comments WHERE post_id NOT IN (SELECT id FROM posts);")
echo $ORPHANED_COUNT

echo ""
echo "【孤立したコメントの詳細】"
docker exec $DB_CONTAINER psql -U postgres -d postgres -c "
SELECT c.id, c.post_id, c.user_id, c.created_at 
FROM comments c 
LEFT JOIN posts p ON c.post_id = p.id 
WHERE p.id IS NULL 
ORDER BY c.id;
"

echo ""
read -p "これらの孤立したコメントを削除しますか？ (y/N): " confirm

if [[ $confirm != [yY] ]]; then
    echo "削除をキャンセルしました。"
    exit 0
fi

echo ""
echo "【削除実行中...】"

# 孤立したコメントを削除
docker exec $DB_CONTAINER psql -U postgres -d postgres -c "
DELETE FROM comments 
WHERE post_id NOT IN (SELECT id FROM posts);
"

echo ""
echo "【削除後の状態】"
echo "総コメント数:"
docker exec $DB_CONTAINER psql -U postgres -d postgres -t -c "SELECT COUNT(*) FROM comments;"

echo "孤立したコメント数:"
docker exec $DB_CONTAINER psql -U postgres -d postgres -t -c "SELECT COUNT(*) FROM comments WHERE post_id NOT IN (SELECT id FROM posts);"

echo ""
echo "=========================================="
echo "削除完了しました！"
echo "=========================================="
