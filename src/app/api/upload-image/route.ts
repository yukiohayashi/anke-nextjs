import { NextRequest, NextResponse } from 'next/server';
import sharp from 'sharp';
import { writeFile, mkdir } from 'fs/promises';
import { existsSync } from 'fs';
import path from 'path';

export async function POST(request: NextRequest) {
  try {
    const formData = await request.formData();
    const file = formData.get('image') as File;

    if (!file) {
      return NextResponse.json(
        { success: false, error: '画像ファイルが見つかりません' },
        { status: 400 }
      );
    }

    // ファイルをBufferに変換
    const bytes = await file.arrayBuffer();
    const buffer = Buffer.from(bytes);

    // 画像を最適化（最大幅1200px、JPEG品質85%）
    const optimizedImage = await sharp(buffer)
      .resize(1200, null, {
        withoutEnlargement: true,
        fit: 'inside'
      })
      .jpeg({ quality: 85 })
      .toBuffer();

    // 保存先ディレクトリを作成
    const uploadDir = path.join(process.cwd(), 'public', 'uploads', 'posts');
    if (!existsSync(uploadDir)) {
      await mkdir(uploadDir, { recursive: true });
    }

    // ファイル名を生成（タイムスタンプ + ランダム文字列）
    const timestamp = Date.now();
    const randomString = Math.random().toString(36).substring(2, 15);
    const filename = `${timestamp}-${randomString}.jpg`;
    const filepath = path.join(uploadDir, filename);

    // ファイルを保存
    await writeFile(filepath, optimizedImage);

    // 公開URLを生成
    const publicUrl = `/uploads/posts/${filename}`;

    return NextResponse.json({
      success: true,
      url: publicUrl,
      filename: filename
    });

  } catch (error) {
    console.error('Image upload error:', error);
    return NextResponse.json(
      { success: false, error: '画像のアップロードに失敗しました' },
      { status: 500 }
    );
  }
}
