import { NextRequest, NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';
import { auth } from '@/lib/auth';

export async function POST(request: NextRequest) {
  try {
    const session = await auth();
    
    if (!session?.user || session.user.status !== 3) {
      return NextResponse.json(
        { error: '管理者権限が必要です' },
        { status: 403 }
      );
    }

    const body = await request.json();
    const { title, content, vote_budget, guest_check, status } = body;

    const { data, error } = await supabase
      .from('workers')
      .insert({
        title,
        content,
        user_id: parseInt(session.user.id),
        vote_per_price: 5,
        vote_budget,
        guest_check,
        status
      })
      .select()
      .single();

    if (error) {
      console.error('Error creating ankework:', error);
      return NextResponse.json(
        { error: '作成に失敗しました' },
        { status: 500 }
      );
    }

    return NextResponse.json(data);
  } catch (error) {
    console.error('Error:', error);
    return NextResponse.json(
      { error: 'サーバーエラーが発生しました' },
      { status: 500 }
    );
  }
}
