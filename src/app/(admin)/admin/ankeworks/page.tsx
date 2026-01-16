'use client';

import { useState, useEffect } from 'react';
import { supabase } from '@/lib/supabase';
import Link from 'next/link';
import { Pencil, Trash2 } from 'lucide-react';

interface Ankework {
  id: number;
  title: string;
  status: string;
  vote_budget: number;
  guest_check: boolean;
  created_at: string;
  post_count?: number;
}

export default function AnkeworksAdminPage() {
  const [ankeworks, setAnkeworks] = useState<Ankework[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchAnkeworks();
  }, []);

  const fetchAnkeworks = async () => {
    const { data: workers } = await supabase
      .from('workers')
      .select('*')
      .order('created_at', { ascending: false });

    const workersWithCount = await Promise.all(
      (workers || []).map(async (worker) => {
        const { count } = await supabase
          .from('posts')
          .select('id', { count: 'exact', head: true })
          .eq('workid', worker.id);
        
        return {
          ...worker,
          post_count: count || 0
        };
      })
    );

    setAnkeworks(workersWithCount);
    setIsLoading(false);
  };

  const handleDelete = async (id: number, title: string) => {
    if (!confirm(`「${title}」を削除しますか？\n\nこの操作は取り消せません。`)) {
      return;
    }

    try {
      const response = await fetch(`/api/admin/ankeworks/${id}`, {
        method: 'DELETE',
      });

      if (!response.ok) {
        throw new Error('削除に失敗しました');
      }

      alert('削除しました');
      fetchAnkeworks();
    } catch (error) {
      console.error('Error:', error);
      alert('削除に失敗しました');
    }
  };

  if (isLoading) {
    return <div className="p-6">読み込み中...</div>;
  }

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">アンケワークス投稿管理</h1>
        <Link
          href="/admin/ankeworks/new"
          className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
        >
          新規作成
        </Link>
      </div>

      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ID
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                タイトル
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ステータス
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                作成数
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                残予算
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                支払い条件
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                作成日
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                操作
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {ankeworks.map((work) => (
              <tr key={work.id}>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {work.id}
                </td>
                <td className="px-6 py-4 text-sm text-gray-900">
                  <Link
                    href={`/ankeworks/${work.id}`}
                    className="text-blue-600 hover:underline"
                    target="_blank"
                  >
                    {work.title}
                  </Link>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                    work.status === 'published' 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-gray-100 text-gray-800'
                  }`}>
                    {work.status === 'published' ? '公開中' : '下書き'}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {work.post_count}件
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {work.vote_budget.toLocaleString()} pt
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                    work.guest_check
                      ? 'bg-blue-100 text-blue-800'
                      : 'bg-yellow-100 text-yellow-800'
                  }`}>
                    {work.guest_check ? 'ゲスト可' : 'ログインのみ'}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {new Date(work.created_at).toLocaleDateString('ja-JP')}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div className="flex gap-2">
                    <Link
                      href={`/admin/ankeworks/${work.id}/edit`}
                      className="text-blue-600 hover:text-blue-900"
                      title="編集"
                    >
                      <Pencil className="w-4 h-4" />
                    </Link>
                    <button
                      onClick={() => handleDelete(work.id, work.title)}
                      className="text-red-600 hover:text-red-900"
                      title="削除"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {ankeworks.length === 0 && (
          <div className="text-center py-12 text-gray-500">
            アンケワークス投稿がありません
          </div>
        )}
      </div>
    </div>
  );
}
