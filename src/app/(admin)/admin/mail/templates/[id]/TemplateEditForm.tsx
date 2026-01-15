'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';

interface Template {
  id: number;
  template_key: string;
  subject: string;
  body: string;
  is_active: boolean;
}

interface TemplateEditFormProps {
  template: Template;
}

export default function TemplateEditForm({ template }: TemplateEditFormProps) {
  const router = useRouter();
  const [subject, setSubject] = useState(template.subject);
  const [body, setBody] = useState(template.body);
  const [isActive, setIsActive] = useState(template.is_active);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const response = await fetch(`/api/admin/mail/templates/${template.id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          subject,
          body,
          is_active: isActive,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        setError(data.error || 'テンプレートの更新に失敗しました');
        return;
      }

      setSuccess('テンプレートを更新しました');
      
      setTimeout(() => {
        router.push('/admin/mail/templates');
        router.refresh();
      }, 1500);
    } catch (err) {
      setError('テンプレートの更新中にエラーが発生しました');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <form onSubmit={handleSubmit} className="space-y-6">
        {error && (
          <div className="bg-red-50 border border-red-200 p-3 rounded text-red-700 text-sm">
            {error}
          </div>
        )}

        {success && (
          <div className="bg-green-50 border border-green-200 p-3 rounded text-green-700 text-sm">
            {success}
          </div>
        )}

        <div>
          <label className="block mb-2 font-medium text-gray-700 text-sm">
            テンプレートキー
          </label>
          <input
            type="text"
            value={template.template_key}
            disabled
            className="bg-gray-100 border-gray-300 px-3 py-2 border rounded-md w-full cursor-not-allowed"
          />
          <p className="mt-1 text-gray-500 text-xs">テンプレートキーは変更できません</p>
        </div>

        <div>
          <label htmlFor="subject" className="block mb-2 font-medium text-gray-700 text-sm">
            件名
          </label>
          <input
            type="text"
            id="subject"
            value={subject}
            onChange={(e) => setSubject(e.target.value)}
            className="border-gray-300 px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 w-full focus:outline-none"
            required
            disabled={loading}
          />
        </div>

        <div>
          <label htmlFor="body" className="block mb-2 font-medium text-gray-700 text-sm">
            本文（HTML）
          </label>
          <textarea
            id="body"
            value={body}
            onChange={(e) => setBody(e.target.value)}
            rows={15}
            className="border-gray-300 px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 w-full font-mono text-sm focus:outline-none"
            required
            disabled={loading}
          />
          <p className="mt-1 text-gray-500 text-xs">
            変数: {'{{'} と {'}}'}で囲んで使用します（例: {'{{userName}}'}）
          </p>
        </div>

        <div className="flex items-center">
          <input
            type="checkbox"
            id="is_active"
            checked={isActive}
            onChange={(e) => setIsActive(e.target.checked)}
            className="mr-2 rounded"
            disabled={loading}
          />
          <label htmlFor="is_active" className="text-gray-700 text-sm">
            有効
          </label>
        </div>

        <div className="flex gap-4">
          <button
            type="submit"
            disabled={loading}
            className="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 px-6 py-2 rounded-md font-medium text-white transition-colors disabled:cursor-not-allowed"
          >
            {loading ? '更新中...' : '更新'}
          </button>
          <Link
            href="/admin/mail/templates"
            className="bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded-md font-medium text-gray-700 transition-colors"
          >
            キャンセル
          </Link>
        </div>
      </form>
    </div>
  );
}
