import { auth } from '@/lib/auth';
import { redirect } from 'next/navigation';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import MyPageMenu from '@/components/MyPageMenu';
import PointExchangeForm from './PointExchangeForm';

export default async function PointExchangePage() {
  const session = await auth();
  
  if (!session) {
    redirect('/login');
  }

  return (
    <div className="bg-gray-50 min-h-screen">
      <Header />
      
      <div className="md:flex mx-auto md:max-w-[1260px] mt-0 md:mt-4 md:justify-center">
        <main className="md:min-w-[690px] mx-0 md:mx-[5px] px-4 md:px-0 pt-16 md:pt-0">
          <h1 className="mb-4 p-0 font-bold text-[#ff6b35] text-2xl">
            ポイント交換
          </h1>
          
          <PointExchangeForm />
        </main>
        
        <aside className="hidden md:block md:w-[280px] md:min-w-[280px]">
          <MyPageMenu />
        </aside>
      </div>
      
      <Footer />
    </div>
  );
}
