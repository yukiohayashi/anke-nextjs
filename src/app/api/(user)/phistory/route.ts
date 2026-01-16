import { NextResponse } from 'next/server';
import { supabaseAdmin } from '@/lib/supabase';

export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const userId = searchParams.get('userId');

    if (!userId) {
      return NextResponse.json(
        { success: false, error: 'ユーザーIDが必要です' },
        { status: 400 }
      );
    }

    // ポイント履歴を取得（全件取得）
    // Supabaseの1000件制限を回避するため、ページネーションで全件取得
    let allPointHistory: any[] = [];
    let from = 0;
    const pageSize = 1000;
    let hasMore = true;

    while (hasMore) {
      const { data, error } = await supabaseAdmin
        .from('points')
        .select('id, type, points, amount, created_at')
        .eq('user_id', userId)
        .order('created_at', { ascending: false })
        .range(from, from + pageSize - 1);

      if (error) {
        console.error('Point history fetch error:', error);
        return NextResponse.json(
          { success: false, error: 'ポイント履歴の取得に失敗しました: ' + error.message },
          { status: 500 }
        );
      }

      if (data && data.length > 0) {
        allPointHistory = allPointHistory.concat(data);
        from += pageSize;
        hasMore = data.length === pageSize;
      } else {
        hasMore = false;
      }
    }

    const pointHistory = allPointHistory;

    // 現在の所有ポイントを計算（全履歴の合計）
    // amountが存在する場合はamount、なければpointsを使用
    let totalPoints = 0;
    const debugRecords: any[] = [];
    
    (pointHistory || []).forEach((record) => {
      const pointValue = record.amount !== null && record.amount !== undefined ? record.amount : (record.points || 0);
      totalPoints += pointValue;
      
      // デバッグ用に最初の10件を記録
      if (debugRecords.length < 10) {
        debugRecords.push({
          id: record.id,
          type: record.type,
          points: record.points,
          amount: record.amount,
          usedValue: pointValue,
          runningTotal: totalPoints
        });
      }
    });
    
    return NextResponse.json({
      success: true,
      pointHistory: pointHistory || [],
      totalPoints,
      debug: {
        recordCount: pointHistory?.length || 0,
        userId,
        sampleRecords: debugRecords
      }
    });
  } catch (error) {
    console.error('Point history error:', error);
    return NextResponse.json(
      { success: false, error: 'ポイント履歴の取得に失敗しました' },
      { status: 500 }
    );
  }
}
