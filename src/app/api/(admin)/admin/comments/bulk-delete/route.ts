import { NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';

export async function POST(request: Request) {
  try {
    const { commentIds } = await request.json();

    if (!commentIds || !Array.isArray(commentIds) || commentIds.length === 0) {
      return NextResponse.json({ error: '削除するコメントIDを指定してください' }, { status: 400 });
    }

    // コメントを一括削除
    const { error } = await supabase
      .from('comments')
      .delete()
      .in('id', commentIds);

    if (error) {
      console.error('Error bulk deleting comments:', error);
      return NextResponse.json({ error: 'コメントの削除に失敗しました', details: error }, { status: 500 });
    }

    return NextResponse.json({
      success: true,
      message: `${commentIds.length}件のコメントを削除しました`,
      deletedCount: commentIds.length
    });

  } catch (error) {
    console.error('Error in bulk delete:', error);
    return NextResponse.json({ error: 'コメントの削除に失敗しました' }, { status: 500 });
  }
}
