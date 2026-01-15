#!/usr/bin/env node

/**
 * Nginx経由のパフォーマンス測定スクリプト
 * Docker環境でNginx経由の速度を計測します
 * 
 * 使用方法:
 * 1. docker-compose up -d でNginx+Next.jsを起動
 * 2. node src/scripts/measure-nginx.mjs
 */

const TARGET_URL = 'http://localhost:8080';  // Nginx経由（ポート8080）
const ITERATIONS = 10;

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
    
    await response.text();
    
    const endTime = performance.now();
    const duration = endTime - startTime;
    
    return {
      iteration,
      duration,
      status: response.status,
      cacheStatus: response.headers.get('x-cache-status') || 'UNKNOWN',
      servedBy: response.headers.get('x-served-by') || 'UNKNOWN',
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
  console.log('🐳 Nginx経由パフォーマンス測定開始\n');
  console.log(`📍 対象URL: ${TARGET_URL} (Nginx経由)`);
  console.log(`🔄 測定回数: ${ITERATIONS}回\n`);
  console.log('━'.repeat(60));
  
  const results = [];
  
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
      const cacheIcon = result.cacheStatus === 'HIT' ? '🟢' : 
                       result.cacheStatus === 'MISS' ? '🔴' : '⚪';
      console.log(`✓ #${i}: ${result.duration.toFixed(2)}ms (Cache: ${cacheIcon} ${result.cacheStatus})`);
    }
    
    if (i < ITERATIONS) {
      await new Promise(resolve => setTimeout(resolve, 500));
    }
  }
  
  console.log('\n' + '━'.repeat(60));
  console.log('\n📈 測定結果サマリー\n');
  
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
  
  const cacheHits = validResults.filter(r => r.cacheStatus === 'HIT').length;
  const cacheHitRate = (cacheHits / validResults.length * 100).toFixed(1);
  console.log(`\nNginxキャッシュヒット率: ${cacheHitRate}% (${cacheHits}/${validResults.length})`);
  
  console.log('\n🎯 パフォーマンス評価:');
  if (avg < 50) {
    console.log('🌟🌟🌟 超優秀: 50ms未満（Nginx効果絶大！）');
  } else if (avg < 100) {
    console.log('🌟 優秀: 100ms未満（Kusanagi級の速度！）');
  } else if (avg < 300) {
    console.log('✅ 良好: 300ms未満（高速）');
  } else {
    console.log('⚠️  要改善: 300ms以上');
  }
  
  console.log('\n' + '━'.repeat(60));
  console.log('\n💡 ヒント:');
  console.log('- Cache HITが多いほどNginxのキャッシュが効いています');
  console.log('- 静的ファイルはNginxが直接配信しています');
  console.log('- 平均50ms未満を目指しましょう（Nginx効果最大化）\n');
}

runPerformanceTest().catch(console.error);
