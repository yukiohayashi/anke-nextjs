import { auth } from '@/lib/auth';
import { redirect } from 'next/navigation';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import MyPageMenu from '@/components/MyPageMenu';
import ReportForm from './ReportForm';

export default async function ReportPage() {
  const session = await auth();
  
  if (!session) {
    redirect('/login');
  }

  return (
    <div className="bg-gray-50 min-h-screen">
      <Header />
      
      <div className="wrapper" style={{ display: 'flex', maxWidth: '1260px', margin: '16px auto 0', justifyContent: 'center' }}>
        <main className="article__contents" style={{ minWidth: '690px', margin: '0 5px' }}>
          <h1 className="mb-4 p-0 font-bold text-[#ff6b35] text-2xl">
            通報する
          </h1>
          
          <ReportForm />
        </main>
        
        <aside className="hidden md:block md:shrink-0 md:w-80">
          <MyPageMenu />
        </aside>
      </div>
      
      <Footer />
    </div>
  );
}
