import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

export async function POST(request: NextRequest) {
  try {
    const { type } = await request.json();

    const deletedCounts: { [key: string]: number } = {};

    // 開発用: 直近100件の投稿のみを対象（デフォルト1000件制限を回避）
    const { data: allPosts } = await supabase
      .from('posts')
      .select('id')
      .order('id', { ascending: false })
      .limit(100);

    const postIds = new Set((allPosts || []).map(p => p.id));
    
    // 削除された投稿も含めて範囲を計算
    const { data: maxVoteChoice } = await supabase
      .from('vote_choices')
      .select('post_id')
      .order('post_id', { ascending: false })
      .limit(1)
      .single();
    
    const maxPostIdFromVotes = maxVoteChoice?.post_id || 0;
    const maxPostIdFromPosts = allPosts && allPosts.length > 0 ? Math.max(...allPosts.map(p => p.id)) : 0;
    const maxPostId = Math.max(maxPostIdFromVotes, maxPostIdFromPosts);
    const minPostId = allPosts && allPosts.length > 0 ? Math.min(...allPosts.map(p => p.id)) : 0;

    if (type === 'all' || type === 'vote_choices') {
      // 孤立した投票選択肢を削除（直近100件の範囲内のみ）
      const { data: voteChoices } = await supabase
        .from('vote_choices')
        .select('id, post_id')
        .gte('post_id', minPostId)
        .lte('post_id', maxPostId);

      const orphanedChoices = voteChoices?.filter(
        vc => !postIds.has(vc.post_id)
      ) || [];

      if (orphanedChoices.length > 0) {
        const ids = orphanedChoices.map(c => c.id);
        const { error } = await supabase
          .from('vote_choices')
          .delete()
          .in('id', ids);

        if (!error) {
          deletedCounts['投票選択肢'] = orphanedChoices.length;
        }
      }
    }

    if (type === 'all' || type === 'vote_options') {
      // 孤立した投票オプションを削除（直近100件の範囲内のみ）
      const { data: voteOptions } = await supabase
        .from('vote_options')
        .select('id, post_id')
        .gte('post_id', minPostId)
        .lte('post_id', maxPostId);

      const orphanedOptions = voteOptions?.filter(
        vo => !postIds.has(vo.post_id)
      ) || [];

      if (orphanedOptions.length > 0) {
        const ids = orphanedOptions.map(o => o.id);
        const { error } = await supabase
          .from('vote_options')
          .delete()
          .in('id', ids);

        if (!error) {
          deletedCounts['投票オプション'] = orphanedOptions.length;
        }
      }
    }

    if (type === 'all' || type === 'comments') {
      // 孤立したコメントを削除（直近100件の範囲内のみ）
      const { data: comments } = await supabase
        .from('comments')
        .select('id, post_id')
        .gte('post_id', minPostId)
        .lte('post_id', maxPostId);

      const orphanedComments = comments?.filter(
        c => !postIds.has(c.post_id)
      ) || [];

      if (orphanedComments.length > 0) {
        const ids = orphanedComments.map(c => c.id);
        const { error } = await supabase
          .from('comments')
          .delete()
          .in('id', ids);

        if (!error) {
          deletedCounts['コメント'] = orphanedComments.length;
        }
      }
    }

    if (type === 'all' || type === 'keywords') {
      // 投稿が存在しないキーワードを削除（カテゴリタイプは除外）
      const { data: orphanedKeywords } = await supabase
        .from('keywords')
        .select('id')
        .neq('keyword_type', 'category')
        .eq('post_count', 0);

      if (orphanedKeywords && orphanedKeywords.length > 0) {
        const ids = orphanedKeywords.map(k => k.id);
        const { error } = await supabase
          .from('keywords')
          .delete()
          .in('id', ids);

        if (!error) {
          deletedCounts['キーワード'] = orphanedKeywords.length;
        }
      }
    }

    if (type === 'all' || type === 'likes') {
      // 孤立したいいねを削除（直近100件の範囲内のみ）
      const { data: likes } = await supabase
        .from('likes')
        .select('id, like_type, target_id')
        .eq('like_type', 'post')
        .gte('target_id', minPostId)
        .lte('target_id', maxPostId);

      const orphanedLikes = likes?.filter(
        l => !postIds.has(l.target_id)
      ) || [];

      if (orphanedLikes.length > 0) {
        const ids = orphanedLikes.map(l => l.id);
        const { error } = await supabase
          .from('likes')
          .delete()
          .in('id', ids);

        if (!error) {
          deletedCounts['いいね'] = orphanedLikes.length;
        }
      }
    }

    if (type === 'all' || type === 'favorites') {
      // 孤立したお気に入りを削除（直近100件の範囲内のみ）
      const { data: favorites } = await supabase
        .from('favorites')
        .select('id, post_id')
        .gte('post_id', minPostId)
        .lte('post_id', maxPostId);

      const orphanedFavorites = favorites?.filter(
        f => !postIds.has(f.post_id)
      ) || [];

      if (orphanedFavorites.length > 0) {
        const ids = orphanedFavorites.map(f => f.id);
        const { error } = await supabase
          .from('favorites')
          .delete()
          .in('id', ids);

        if (!error) {
          deletedCounts['お気に入り'] = orphanedFavorites.length;
        }
      }
    }

    const message = Object.entries(deletedCounts)
      .map(([key, count]) => `${key}: ${count}件`)
      .join(', ');

    return NextResponse.json({
      success: true,
      message: message || '削除するデータがありませんでした',
      deletedCounts,
    });
  } catch (error) {
    console.error('Cleanup error:', error);
    return NextResponse.json(
      { error: 'クリーンアップ処理に失敗しました' },
      { status: 500 }
    );
  }
}
