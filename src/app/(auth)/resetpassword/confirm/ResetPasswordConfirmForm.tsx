'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';

interface ResetPasswordConfirmFormProps {
  token: string;
}

export default function ResetPasswordConfirmForm({ token }: ResetPasswordConfirmFormProps) {
  const router = useRouter();
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess('');

    // バリデーション
    if (!token) {
      setError('無効なリセットトークンです');
      return;
    }

    if (!newPassword || !confirmPassword) {
      setError('すべての項目を入力してください');
      return;
    }

    if (newPassword.length < 8) {
      setError('パスワードは8文字以上で入力してください');
      return;
    }

    if (newPassword !== confirmPassword) {
      setError('パスワードが一致しません');
      return;
    }

    setLoading(true);

    try {
      const response = await fetch('/api/password/reset/confirm', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          token,
          newPassword,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        setError(data.error || 'パスワードのリセットに失敗しました');
        return;
      }

      setSuccess('パスワードをリセットしました。ログインページに移動します...');
      
      // 3秒後にログインページにリダイレクト
      setTimeout(() => {
        router.push('/login');
      }, 3000);
    } catch (err) {
      setError('パスワードリセット中にエラーが発生しました');
    } finally {
      setLoading(false);
    }
  };

  if (!token) {
    return (
      <div className="text-center">
        <p className="mb-4 text-red-600">無効なリセットトークンです</p>
        <Link href="/resetpassword" className="text-[#ff6b35] hover:underline">
          パスワードリセットページに戻る
        </Link>
      </div>
    );
  }

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
        <label htmlFor="newPassword" className="block mb-2 font-medium text-gray-700 text-sm">
          新しいパスワード（8文字以上）
        </label>
        <div className="relative">
          <input
            type={showNewPassword ? 'text' : 'password'}
            id="newPassword"
            value={newPassword}
            onChange={(e) => setNewPassword(e.target.value)}
            className="border-gray-300 px-3 py-2 pr-10 border rounded-md focus:ring-2 focus:ring-orange-500 w-full focus:outline-none"
            disabled={loading}
          />
          <button
            type="button"
            onClick={() => setShowNewPassword(!showNewPassword)}
            className="top-1/2 right-3 absolute text-gray-500 hover:text-gray-700 -translate-y-1/2"
          >
            <i className={`fas ${showNewPassword ? 'fa-eye-slash' : 'fa-eye'}`}></i>
          </button>
        </div>
      </div>

      <div>
        <label htmlFor="confirmPassword" className="block mb-2 font-medium text-gray-700 text-sm">
          新しいパスワード（確認）
        </label>
        <div className="relative">
          <input
            type={showConfirmPassword ? 'text' : 'password'}
            id="confirmPassword"
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            className="border-gray-300 px-3 py-2 pr-10 border rounded-md focus:ring-2 focus:ring-orange-500 w-full focus:outline-none"
            disabled={loading}
          />
          <button
            type="button"
            onClick={() => setShowConfirmPassword(!showConfirmPassword)}
            className="top-1/2 right-3 absolute text-gray-500 hover:text-gray-700 -translate-y-1/2"
          >
            <i className={`fas ${showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'}`}></i>
          </button>
        </div>
      </div>

      <button
        type="submit"
        disabled={loading}
        className="bg-[#ff6b35] hover:bg-[#e55a24] disabled:opacity-50 px-6 py-3 rounded-md w-full font-bold text-white transition-colors disabled:cursor-not-allowed"
      >
        {loading ? 'リセット中...' : 'パスワードをリセット'}
      </button>

      <div className="pt-4 text-center text-sm">
        <Link href="/login" className="text-[#ff6b35] hover:underline">
          ログインページに戻る
        </Link>
      </div>
    </form>
  );
}
