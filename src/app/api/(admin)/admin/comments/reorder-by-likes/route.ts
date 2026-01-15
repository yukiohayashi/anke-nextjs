import { NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';

export async function POST() {
  try {
    // 1. コメントのいいね数を集計
    const { data: likeCounts, error: likeError } = await supabase
      .from('likes')
      .select('target_id')
      .eq('like_type', 'comment');

    if (likeError) {
      return NextResponse.json({ error: 'いいね数の取得に失敗しました', details: likeError }, { status: 500 });
    }

    // コメントIDごとにいいね数を集計
    const likeCountMap = new Map<number, number>();
    likeCounts?.forEach(like => {
      const count = likeCountMap.get(like.target_id) || 0;
      likeCountMap.set(like.target_id, count + 1);
    });

    // 2. 全コメントを取得
    const { data: comments, error: commentsError } = await supabase
      .from('comments')
      .select('id, post_id, user_id, content, status, created_at, updated_at')
      .order('id', { ascending: true });

    if (commentsError) {
      return NextResponse.json({ error: 'コメントの取得に失敗しました', details: commentsError }, { status: 500 });
    }

    if (!comments || comments.length === 0) {
      return NextResponse.json({ message: 'コメントがありません' });
    }

    // 3. いいね数でソート（多い順）、同数の場合は元のID順
    const sortedComments = comments
      .map(comment => ({
        ...comment,
        likeCount: likeCountMap.get(comment.id) || 0
      }))
      .sort((a, b) => {
        if (b.likeCount !== a.likeCount) {
          return b.likeCount - a.likeCount; // いいね数が多い順
        }
        return a.id - b.id; // 同数の場合は元のID順
      });

    // 4. 一時テーブルを使用してIDを振り直す
    // まず、既存のコメントを削除
    const { error: deleteError } = await supabase
      .from('comments')
      .delete()
      .neq('id', 0); // 全削除

    if (deleteError) {
      return NextResponse.json({ error: 'コメントの削除に失敗しました', details: deleteError }, { status: 500 });
    }

    // 5. 新しいIDで再挿入（IDは1から順番に振られる）
    const newComments = sortedComments.map((comment, index) => ({
      id: index + 1, // 新しいID（1から順番）
      post_id: comment.post_id,
      user_id: comment.user_id,
      content: comment.content,
      status: comment.status,
      created_at: comment.created_at,
      updated_at: comment.updated_at
    }));

    // バッチで挿入（100件ずつ）
    const batchSize = 100;
    for (let i = 0; i < newComments.length; i += batchSize) {
      const batch = newComments.slice(i, i + batchSize);
      const { error: insertError } = await supabase
        .from('comments')
        .insert(batch);

      if (insertError) {
        return NextResponse.json({ 
          error: `コメントの挿入に失敗しました (batch ${Math.floor(i / batchSize) + 1})`, 
          details: insertError 
        }, { status: 500 });
      }
    }

    // 6. シーケンスをリセット（次のIDが正しく採番されるように）
    // PostgreSQLの場合
    const { error: seqError } = await supabase.rpc('reset_comments_sequence');

    return NextResponse.json({
      success: true,
      message: `${comments.length}件のコメントIDを振り直しました`,
      reorderedCount: comments.length,
      topComments: sortedComments.slice(0, 10).map(c => ({
        newId: sortedComments.indexOf(c) + 1,
        oldId: c.id,
        likeCount: c.likeCount,
        content: c.content.substring(0, 50)
      }))
    });

  } catch (error) {
    console.error('Error reordering comments:', error);
    return NextResponse.json({ error: 'コメントIDの振り直しに失敗しました', details: error }, { status: 500 });
  }
}
