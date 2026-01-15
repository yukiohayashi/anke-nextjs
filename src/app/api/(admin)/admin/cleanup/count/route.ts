import { NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!
);

export async function GET() {
  try {
    // 開発用: 直近100件の投稿のみを対象（デフォルト1000件制限を回避）
    const { data: allPosts, error: postsError } = await supabase
      .from('posts')
      .select('id')
      .order('id', { ascending: false })
      .limit(100);

    if (postsError) {
      console.error('Posts fetch error:', postsError);
      return NextResponse.json({ error: postsError.message }, { status: 500 });
    }

    const postIds = new Set((allPosts || []).map(p => p.id));
    
    // 削除された投稿も含めて範囲を計算するため、vote_choicesから最大post_idを取得
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
    
    console.log('Total posts fetched (最新100件):', postIds.size);
    console.log('Post ID range:', minPostId, '-', maxPostId);
    console.log('Max post_id from votes:', maxPostIdFromVotes);

    // 孤立した投票選択肢（直近100件の投稿範囲内のみ）
    const { data: voteChoices, error: voteChoicesError } = await supabase
      .from('vote_choices')
      .select('id, post_id')
      .gte('post_id', minPostId)
      .lte('post_id', maxPostId);

    if (voteChoicesError) {
      console.error('Vote choices fetch error:', voteChoicesError);
    }

    console.log('Total vote_choices:', voteChoices?.length);
    const orphaned_vote_choices = voteChoices?.filter(
      vc => !postIds.has(vc.post_id)
    ).length || 0;
    console.log('Orphaned vote_choices:', orphaned_vote_choices);

    // 孤立した投票オプション（直近100件の投稿範囲内のみ）
    const { data: voteOptions, error: voteOptionsError } = await supabase
      .from('vote_options')
      .select('id, post_id')
      .gte('post_id', minPostId)
      .lte('post_id', maxPostId);

    if (voteOptionsError) {
      console.error('Vote options fetch error:', voteOptionsError);
    }

    console.log('Total vote_options:', voteOptions?.length);
    const orphaned_vote_options = voteOptions?.filter(
      vo => !postIds.has(vo.post_id)
    ).length || 0;
    console.log('Orphaned vote_options:', orphaned_vote_options);

    // 孤立したコメント（直近100件の投稿範囲内のみ）
    const { data: comments, error: commentsError } = await supabase
      .from('comments')
      .select('id, post_id')
      .gte('post_id', minPostId)
      .lte('post_id', maxPostId);

    if (commentsError) {
      console.error('Comments fetch error:', commentsError);
    }

    console.log('Total comments:', comments?.length);
    const orphaned_comments = comments?.filter(
      c => !postIds.has(c.post_id)
    ).length || 0;
    console.log('Orphaned comments:', orphaned_comments);

    // 投稿が存在しないキーワード（カテゴリタイプは除外）
    const { data: keywords } = await supabase
      .from('keywords')
      .select('id, keyword_type, post_count')
      .neq('keyword_type', 'category')
      .eq('post_count', 0);

    const orphaned_keywords = keywords?.length || 0;

    // 孤立したいいね（直近100件の投稿範囲内のみ）
    const { data: likes, error: likesError } = await supabase
      .from('likes')
      .select('id, like_type, target_id')
      .eq('like_type', 'post')
      .gte('target_id', minPostId)
      .lte('target_id', maxPostId);

    if (likesError) {
      console.error('Likes fetch error:', likesError);
    }

    const orphaned_likes = likes?.filter(
      l => !postIds.has(l.target_id)
    ).length || 0;

    // 孤立したお気に入り（直近100件の投稿範囲内のみ）
    const { data: favorites, error: favoritesError } = await supabase
      .from('favorites')
      .select('id, post_id')
      .gte('post_id', minPostId)
      .lte('post_id', maxPostId);

    if (favoritesError) {
      console.error('Favorites fetch error:', favoritesError);
    }

    const orphaned_favorites = favorites?.filter(
      f => !postIds.has(f.post_id)
    ).length || 0;

    return NextResponse.json({
      orphaned_vote_choices,
      orphaned_vote_options,
      orphaned_comments,
      orphaned_keywords,
      orphaned_likes,
      orphaned_favorites,
    });
  } catch (error) {
    console.error('Count error:', error);
    return NextResponse.json(
      { error: '件数取得に失敗しました' },
      { status: 500 }
    );
  }
}
