import { NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';

export async function POST() {
  try {
    // 1. 現在の最大IDを取得
    const { data: maxIdData, error: maxIdError } = await supabase
      .from('comments')
      .select('id')
      .order('id', { ascending: false })
      .limit(1)
      .single();

    if (maxIdError) {
      return NextResponse.json({ 
        error: '最大IDの取得に失敗しました', 
        details: maxIdError 
      }, { status: 500 });
    }

    const maxId = maxIdData?.id || 0;

    // 2. シーケンスをリセット（PostgreSQL）
    // comments_id_seqというシーケンス名を使用（テーブル名_カラム名_seqが一般的）
    const { data, error } = await supabase.rpc('execute_sql', {
      sql: `SELECT setval('comments_id_seq', ${maxId}, true);`
    });

    if (error) {
      // RPCが存在しない場合は、直接SQLを実行
      // Supabaseの場合、直接SQLを実行する方法がないため、
      // データベースの管理画面で以下のSQLを実行する必要があります
      return NextResponse.json({
        success: false,
        message: 'シーケンスのリセットには、データベースの管理画面で以下のSQLを実行してください',
        sql: `SELECT setval('comments_id_seq', ${maxId}, true);`,
        currentMaxId: maxId,
        nextId: maxId + 1,
        instructions: [
          '1. Supabaseのダッシュボードにログイン',
          '2. SQL Editorを開く',
          '3. 以下のSQLを実行:',
          `   SELECT setval('comments_id_seq', ${maxId}, true);`,
          '4. 実行後、次のコメントIDは自動的に ' + (maxId + 1) + ' になります'
        ]
      });
    }

    return NextResponse.json({
      success: true,
      message: 'シーケンスをリセットしました',
      currentMaxId: maxId,
      nextId: maxId + 1
    });

  } catch (error) {
    console.error('Error resetting sequence:', error);
    return NextResponse.json({ 
      error: 'シーケンスのリセットに失敗しました', 
      details: error 
    }, { status: 500 });
  }
}
