'use client';

import { useState } from 'react';
import Link from 'next/link';

export default function ResetPasswordForm() {
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess('');

    // バリデーション
    if (!email) {
      setError('メールアドレスを入力してください');
      return;
    }

    if (!email.includes('@')) {
      setError('有効なメールアドレスを入力してください');
      return;
    }

    setLoading(true);

    try {
      const response = await fetch('/api/password/reset', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email }),
      });

      const data = await response.json();

      if (!response.ok) {
        setError(data.error || 'パスワードリセットに失敗しました');
        return;
      }

      setSuccess('パスワードリセット用のメールを送信しました。メールをご確認ください。');
      setEmail('');
    } catch (err) {
      setError('パスワードリセット中にエラーが発生しました');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
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
        <label htmlFor="email" className="block mb-2 font-medium text-gray-700 text-sm">
          メールアドレス
        </label>
        <input
          type="email"
          id="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="your@email.com"
          className="border-gray-300 px-3 py-2 border rounded-md focus:ring-2 focus:ring-orange-500 w-full focus:outline-none"
          disabled={loading}
        />
      </div>

      <button
        type="submit"
        disabled={loading}
        className="bg-[#ff6b35] hover:bg-[#e55a24] disabled:opacity-50 px-6 py-3 rounded-md w-full font-bold text-white transition-colors disabled:cursor-not-allowed"
      >
        {loading ? '送信中...' : 'リセットメールを送信'}
      </button>

      <div className="pt-4 text-center text-sm">
        <Link href="/login" className="text-[#ff6b35] hover:underline">
          ログインページに戻る
        </Link>
      </div>
    </form>
  );
}
