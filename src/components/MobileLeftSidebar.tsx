'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';

interface MobileLeftSidebarProps {
  isOpen: boolean;
  onClose: () => void;
}

export default function MobileLeftSidebar({ isOpen, onClose }: MobileLeftSidebarProps) {
  const [popularKeywords, setPopularKeywords] = useState<string[]>([]);
  const [latestKeywords, setLatestKeywords] = useState<string[]>([]);

  useEffect(() => {
    // TODO: 実際のデータ取得ロジックを実装
    setPopularKeywords(['レーダー照射', '道産子', '結婚', '岡田克也', 'マエスケ', '鬼滅の刃', '大股']);
    setLatestKeywords(['横山', '横山市長', '市政', '新刀剣男士', '山田']);
  }, []);

  return (
    <>
      {/* オーバーレイ */}
      {isOpen && (
        <div 
          className="md:hidden top-0 left-0 z-[9997] fixed inset-0 bg-black/50 transition-opacity duration-500"
          onClick={onClose}
        >
          <button
            className="top-[10px] right-[5%] z-[9999] fixed text-white text-xl"
            onClick={onClose}
          >
            <i className="fas fa-window-close"></i>
          </button>
        </div>
      )}

      {/* 左サイドバー */}
      <div
        className={`md:hidden top-0 left-0 z-[9998] fixed w-[85%] h-full transition-all duration-500 ${
          isOpen ? 'visible opacity-100' : 'invisible opacity-0'
        }`}
      >
        <div
          className={`z-[9998] relative bg-white px-[5%] pb-5 h-full overflow-y-auto transition-all duration-500 transform ${
            isOpen ? 'translate-x-0 opacity-100' : '-translate-x-full opacity-0'
          }`}
        >
          <div className="py-4">
            {/* みんなの検索ワード */}
            <div className="mb-6">
              <h3 className="flex items-center gap-2 mb-3 font-bold text-orange-500">
                <span>🔥</span>
                <span>みんなの検索ワード：</span>
              </h3>
              <div className="flex flex-wrap gap-2">
                {popularKeywords.map((keyword, index) => (
                  <Link
                    key={index}
                    href={`/search?q=${encodeURIComponent(keyword)}`}
                    className="inline-block bg-white hover:bg-gray-50 px-3 py-1.5 border border-gray-300 rounded-full text-gray-700 text-sm transition-colors"
                    onClick={onClose}
                  >
                    {keyword}
                  </Link>
                ))}
              </div>
            </div>

            {/* 最新キーワード */}
            <div className="mb-6">
              <h3 className="flex items-center gap-2 mb-3 font-bold text-blue-500">
                <span>🔵</span>
                <span>最新キーワード</span>
              </h3>
              <div className="flex flex-wrap gap-2">
                {latestKeywords.map((keyword, index) => (
                  <Link
                    key={index}
                    href={`/search?q=${encodeURIComponent(keyword)}`}
                    className="inline-block bg-white hover:bg-gray-50 px-3 py-1.5 border border-gray-300 rounded-full text-gray-700 text-sm transition-colors"
                    onClick={onClose}
                  >
                    {keyword}
                  </Link>
                ))}
              </div>
            </div>

            {/* アンケートいいね!獲得 */}
            <div className="mb-6">
              <h3 className="mb-2 font-bold text-orange-500">アンケートいいね!獲得</h3>
              <p className="text-gray-600 text-sm">データがありません</p>
            </div>

            {/* コメントいいね!獲得 */}
            <div className="mb-6">
              <h3 className="mb-2 font-bold text-orange-500">コメントいいね!獲得</h3>
              <p className="text-gray-600 text-sm">データがありません</p>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
