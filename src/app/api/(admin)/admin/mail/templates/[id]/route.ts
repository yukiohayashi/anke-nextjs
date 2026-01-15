import { NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';

interface RouteParams {
  params: Promise<{ id: string }>;
}

export async function PUT(request: Request, { params }: RouteParams) {
  try {
    const { id } = await params;
    const { subject, body, is_active } = await request.json();

    if (!subject || !body) {
      return NextResponse.json(
        { error: '件名と本文は必須です' },
        { status: 400 }
      );
    }

    const { error } = await supabase
      .from('mail_templates')
      .update({
        subject,
        body,
        is_active,
        updated_at: new Date().toISOString(),
      })
      .eq('id', id);

    if (error) {
      console.error('Template update error:', error);
      return NextResponse.json(
        { error: 'テンプレートの更新に失敗しました' },
        { status: 500 }
      );
    }

    return NextResponse.json({
      success: true,
      message: 'テンプレートを更新しました',
    });
  } catch (error) {
    console.error('Template update error:', error);
    return NextResponse.json(
      { error: 'テンプレートの更新中にエラーが発生しました' },
      { status: 500 }
    );
  }
}
