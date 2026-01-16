import { NextResponse } from 'next/server';
import { supabaseAdmin } from '@/lib/supabase';

export async function POST(request: Request) {
  try {
    const { userId, notificationType, notificationId } = await request.json();

    if (!userId || !notificationType || !notificationId) {
      return NextResponse.json(
        { success: false, error: '必要なパラメータが不足しています' },
        { status: 400 }
      );
    }

    // 既読レコードを挿入（重複の場合は無視）
    const { error } = await supabaseAdmin
      .from('notification_reads')
      .insert({
        user_id: userId,
        notification_type: notificationType,
        notification_id: notificationId,
        read_at: new Date().toISOString()
      });

    // 重複エラー（23505）は無視
    if (error && error.code !== '23505') {
      console.error('Mark read error:', error);
      console.error('Error code:', error.code);
      console.error('Error message:', error.message);
      console.error('Error details:', JSON.stringify(error, null, 2));
      return NextResponse.json(
        { success: false, error: '既読マークに失敗しました', details: error.message },
        { status: 500 }
      );
    }

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error('Mark read error:', error);
    return NextResponse.json(
      { success: false, error: '既読マークに失敗しました' },
      { status: 500 }
    );
  }
}
