#!/bin/bash

# タイムゾーンを日本時間（JST）に設定
export TZ=Asia/Tokyo

echo "CRON scheduler started"
echo "AI Auto-Creator: http://nextjs:3000/api/auto-creator/execute-auto (6 minutes)"
echo "AI Auto-Voter: http://nextjs:3000/api/auto-voter/execute-auto (10 minutes)"
echo "Timezone: JST (Asia/Tokyo)"

# カウンター初期化
counter=0

while true; do
  echo "---"
  current_time=$(date '+%Y-%m-%d %H:%M:%S JST')
  
  # AI自動投稿（6分ごと）
  echo "[$current_time] Executing AI auto-creator..."
  response=$(curl -s -X POST http://nextjs:3000/api/auto-creator/execute-auto)
  if [ $? -eq 0 ]; then
    echo "[$current_time] Auto-creator response: $response"
  else
    echo "[$current_time] Auto-creator error: Failed to execute"
  fi
  
  # AI自動投票（10分ごと = 60秒 * 10 = 600秒、6分の倍数で実行）
  # counter が 0, 10, 20, 30... の時に実行（6分 * counter が 10分の倍数）
  if [ $((counter % 10)) -eq 0 ] || [ $counter -eq 0 ]; then
    echo "[$current_time] Executing AI auto-voter..."
    voter_response=$(curl -s -X POST http://nextjs:3000/api/auto-voter/execute-auto)
    if [ $? -eq 0 ]; then
      echo "[$current_time] Auto-voter response: $voter_response"
    else
      echo "[$current_time] Auto-voter error: Failed to execute"
    fi
  fi
  
  counter=$((counter + 6))
  echo "[$current_time] Next execution in 6 minutes..."
  sleep 360  # 6分 = 360秒
done
