#!/usr/bin/env node

/**
 * Next.js アプリケーションのパフォーマンス測定スクリプト
 * ローカル環境でトップページの表示速度を計測します
 * 
 * 使用方法:
 * 1. npm run dev でサーバーを起動
 * 2. node src/scripts/measure-performance.mjs
 */

const TARGET_URL = 'http://localhost:3000';
const ITERATIONS = 10; // 測定回数

async function measurePageLoad(url, iteration) {
  const startTime = performance.now();
  
  try {
    const response = await fetch(url, {
      headers: {
        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
      },
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    // HTMLを完全に読み込む
    await response.text();
    
    const endTime = performance.now();
    const duration = endTime - startTime;
    
    return {
      iteration,
      duration,
      status: response.status,
      cached: response.headers.get('x-nextjs-cache') || 'MISS',
    };
  } catch (error) {
    return {
      iteration,
      duration: -1,
      error: error.message,
    };
  }
}

async function runPerformanceTest() {
  console.log('🚀 Next.js パフォーマンス測定開始\n');
  console.log(`📍 対象URL: ${TARGET_URL}`);
  console.log(`🔄 測定回数: ${ITERATIONS}回\n`);
  console.log('━'.repeat(60));
  
  const results = [];
  
  // ウォームアップ（1回目は除外）
  console.log('⏳ ウォームアップ中...');
  await measurePageLoad(TARGET_URL, 0);
  await new Promise(resolve => setTimeout(resolve, 1000));
  
  console.log('✅ ウォームアップ完了\n');
  console.log('📊 測定開始...\n');
  
  for (let i = 1; i <= ITERATIONS; i++) {
    const result = await measurePageLoad(TARGET_URL, i);
    results.push(result);
    
    if (result.error) {
      console.log(`❌ #${i}: エラー - ${result.error}`);
    } else {
      const cacheStatus = result.cached === 'HIT' ? '🟢 HIT' : '🔴 MISS';
      console.log(`✓ #${i}: ${result.duration.toFixed(2)}ms (Cache: ${cacheStatus})`);
    }
    
    // 次の測定まで少し待機
    if (i < ITERATIONS) {
      await new Promise(resolve => setTimeout(resolve, 500));
    }
  }
  
  console.log('\n' + '━'.repeat(60));
  console.log('\n📈 測定結果サマリー\n');
  
  // 統計計算
  const validResults = results.filter(r => r.duration > 0);
  const durations = validResults.map(r => r.duration);
  
  if (durations.length === 0) {
    console.log('❌ 有効な測定結果がありません');
    return;
  }
  
  const avg = durations.reduce((a, b) => a + b, 0) / durations.length;
  const min = Math.min(...durations);
  const max = Math.max(...durations);
  const median = durations.sort((a, b) => a - b)[Math.floor(durations.length / 2)];
  
  console.log(`平均: ${avg.toFixed(2)}ms`);
  console.log(`中央値: ${median.toFixed(2)}ms`);
  console.log(`最速: ${min.toFixed(2)}ms`);
  console.log(`最遅: ${max.toFixed(2)}ms`);
  
  // キャッシュヒット率
  const cacheHits = validResults.filter(r => r.cached === 'HIT').length;
  const cacheHitRate = (cacheHits / validResults.length * 100).toFixed(1);
  console.log(`\nキャッシュヒット率: ${cacheHitRate}% (${cacheHits}/${validResults.length})`);
  
  // パフォーマンス評価
  console.log('\n🎯 パフォーマンス評価:');
  if (avg < 100) {
    console.log('🌟 優秀: 100ms未満（Kusanagi級の速度！）');
  } else if (avg < 300) {
    console.log('✅ 良好: 300ms未満（高速）');
  } else if (avg < 1000) {
    console.log('⚠️  普通: 1秒未満（改善の余地あり）');
  } else {
    console.log('❌ 要改善: 1秒以上（最適化が必要）');
  }
  
  console.log('\n' + '━'.repeat(60));
  console.log('\n💡 ヒント:');
  console.log('- 2回目以降が速い場合、ISRが効いています');
  console.log('- Cache HITが多い場合、revalidate設定が機能しています');
  console.log('- 平均100ms未満を目指しましょう（Kusanagi超え）\n');
}

// 実行
runPerformanceTest().catch(console.error);
