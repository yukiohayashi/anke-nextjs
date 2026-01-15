import Header from '@/components/Header';
import Footer from '@/components/Footer';
import RightSidebar from '@/components/RightSidebar';
import MyPageMenu from '@/components/MyPageMenu';
import LoginRequired from '@/components/LoginRequired';
import PasswordChangeForm from './PasswordChangeForm';

export default async function PasswordPage() {
  return (
    <LoginRequired pageName="パスワード変更">
      <div className="bg-gray-50 min-h-screen">
        <Header />
      
      <div className="md:flex mx-auto md:max-w-[1260px] mt-0 md:mt-[70px] md:justify-center">
        <main className="md:min-w-[690px] mx-0 md:mx-[5px] px-4 md:px-0 pt-16 md:pt-0">
          <h1 className="mb-4 p-0 font-bold text-[#ff6b35] text-2xl">
            パスワード変更
          </h1>
          <PasswordChangeForm />
        </main>
        
        <aside className="hidden md:block md:w-[280px] md:min-w-[280px]">
          <div className="mb-4">
            <RightSidebar />
          </div>
          <MyPageMenu />
        </aside>
      </div>
      
        <Footer />
      </div>
    </LoginRequired>
  );
}
