import { NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';
import bcrypt from 'bcryptjs';

export async function POST(request: Request) {
  try {
    const { token, newPassword } = await request.json();

    if (!token || !newPassword) {
      return NextResponse.json(
        { error: 'トークンとパスワードを入力してください' },
        { status: 400 }
      );
    }

    if (newPassword.length < 8) {
      return NextResponse.json(
        { error: 'パスワードは8文字以上で入力してください' },
        { status: 400 }
      );
    }

    // トークンでユーザーを検索
    const { data: user, error: userError } = await supabase
      .from('users')
      .select('id, reset_token, reset_token_expiry')
      .eq('reset_token', token)
      .single();

    if (userError || !user) {
      if (process.env.NODE_ENV === 'development') {
        console.error('ユーザー検索エラー:', userError);
      }
      return NextResponse.json(
        { error: '無効なリセットトークンです' },
        { status: 400 }
      );
    }

    // トークンの有効期限を確認
    const now = new Date();
    const expiryDate = user.reset_token_expiry ? new Date(user.reset_token_expiry) : null;
    
    if (process.env.NODE_ENV === 'development') {
      console.log('トークン有効期限チェック:', {
        now: now.toISOString(),
        nowTime: now.getTime(),
        expiry: expiryDate?.toISOString(),
        expiryTime: expiryDate?.getTime(),
        isExpired: expiryDate ? expiryDate.getTime() < now.getTime() : true,
        diff: expiryDate ? (expiryDate.getTime() - now.getTime()) / 1000 / 60 : 0,
      });
    }

    if (!expiryDate || expiryDate.getTime() < now.getTime()) {
      if (process.env.NODE_ENV === 'development') {
        console.error('トークンの有効期限切れ:', {
          expiry: expiryDate?.toISOString(),
          now: now.toISOString(),
        });
      }
      return NextResponse.json(
        { error: 'リセットトークンの有効期限が切れています' },
        { status: 400 }
      );
    }

    // 新しいパスワードをハッシュ化
    const hashedPassword = await bcrypt.hash(newPassword, 10);

    // パスワードを更新し、トークンをクリア
    const { error: updateError } = await supabase
      .from('users')
      .update({
        user_pass: hashedPassword,
        reset_token: null,
        reset_token_expiry: null,
      })
      .eq('id', user.id);

    if (updateError) {
      console.error('パスワード更新エラー:', updateError);
      return NextResponse.json(
        { error: 'パスワードの更新に失敗しました' },
        { status: 500 }
      );
    }

    return NextResponse.json({
      success: true,
      message: 'パスワードをリセットしました',
    });
  } catch (error) {
    console.error('パスワードリセット確認エラー:', error);
    return NextResponse.json(
      { error: 'パスワードリセット中にエラーが発生しました' },
      { status: 500 }
    );
  }
}
