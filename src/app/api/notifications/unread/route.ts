import { NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';

export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const userId = searchParams.get('userId');

    if (!userId) {
      return NextResponse.json(
        { error: 'ユーザーIDが必要です' },
        { status: 400 }
      );
    }

    // 未読通知数を取得
    const { count, error } = await supabase
      .from('notifications')
      .select('*', { count: 'exact', head: true })
      .eq('user_id', userId)
      .eq('is_read', false);

    if (error) {
      console.error('未読通知数取得エラー:', error);
      return NextResponse.json(
        { error: '未読通知数の取得に失敗しました' },
        { status: 500 }
      );
    }

    return NextResponse.json({
      count: count || 0,
    });
  } catch (error) {
    console.error('未読通知API エラー:', error);
    return NextResponse.json(
      { error: '未読通知数の取得中にエラーが発生しました' },
      { status: 500 }
    );
  }
}
