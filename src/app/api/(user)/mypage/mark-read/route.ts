import { NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';

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
    const { error } = await supabase
      .from('notification_reads')
      .upsert({
        user_id: userId,
        notification_type: notificationType,
        notification_id: notificationId,
        read_at: new Date().toISOString()
      }, {
        onConflict: 'user_id,notification_type,notification_id'
      });

    if (error) {
      console.error('Mark read error:', error);
      return NextResponse.json(
        { success: false, error: '既読マークに失敗しました' },
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
