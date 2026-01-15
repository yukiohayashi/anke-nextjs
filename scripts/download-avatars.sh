#!/bin/bash

# WordPressサーバーからアバター画像をダウンロード
AVATAR_DIR="public/avatars"
mkdir -p "$AVATAR_DIR"

# 画像URLのリスト
urls=(
  "https://anke.jp/wp-content/uploads/anke-11-06-07-1764594858-1765265511.jpg"
  "https://anke.jp/wp-content/uploads/b6106fba1e6d8c8a8b5dd06a620f0de0-1-300x300-1.png"
  "https://anke.jp/wp-content/uploads/1000004767.jpg"
  "https://anke.jp/wp-content/uploads/bbs-20241122054107.jpg"
  "https://anke.jp/wp-content/uploads/222.jpg"
  "https://anke.jp/wp-content/uploads/0d2fdd42fb8d47e536646bdd367e8740_t-1.jpg"
  "https://anke.jp/wp-content/uploads/111-2.png"
  "https://anke.jp/wp-content/uploads/anke-11-06-07-1764594858.jpg"
  "https://anke.jp/wp-content/uploads/1111-3.png"
  "https://anke.jp/wp-content/uploads/wpmembers/user_files/3742/download-1.jpg"
  "https://anke.jp/wp-content/uploads/pineapple-1.png"
  "https://anke.jp/wp-content/uploads/000c9399e3d92a844f462a3158c1dd61.png"
  "https://anke.jp/wp-content/uploads/P1080187.jpg"
  "https://anke.jp/wp-content/uploads/IMG_3718.jpeg"
  "https://anke.jp/wp-content/uploads/CIMG9722.jpg"
  "https://anke.jp/wp-content/uploads/1000000710.jpg"
  "https://anke.jp/wp-content/uploads/IMG_7470-rotated.jpeg"
  "https://anke.jp/wp-content/uploads/SDT_LOGO.png"
  "https://anke.jp/wp-content/uploads/IMG_5435.jpeg"
  "https://anke.jp/wp-content/uploads/azisai-01.jpg"
  "https://anke.jp/wp-content/uploads/none.gif"
  "https://anke.jp/wp-content/uploads/111.jpg"
  "https://anke.jp/wp-content/uploads/download20241102103546.png"
  "https://anke.jp/wp-content/uploads/anke-11-06-07-1765950626.jpg"
)

echo "アバター画像をダウンロード中..."
count=0
total=${#urls[@]}

for url in "${urls[@]}"; do
  ((count++))
  filename=$(basename "$url")
  echo "[$count/$total] ダウンロード中: $filename"
  
  curl -s -o "$AVATAR_DIR/$filename" "$url"
  
  if [ $? -eq 0 ]; then
    echo "  ✓ 成功: $filename"
  else
    echo "  ✗ 失敗: $filename"
  fi
done

echo ""
echo "ダウンロード完了！"
echo "保存先: $AVATAR_DIR"
echo "ダウンロード数: $count 件"
