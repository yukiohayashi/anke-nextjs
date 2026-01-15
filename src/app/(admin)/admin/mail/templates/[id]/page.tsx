import { supabase } from '@/lib/supabase';
import { notFound } from 'next/navigation';
import TemplateEditForm from './TemplateEditForm';

interface TemplateEditPageProps {
  params: Promise<{ id: string }>;
}

async function getTemplate(id: string) {
  const { data: template, error } = await supabase
    .from('mail_templates')
    .select('*')
    .eq('id', id)
    .single();

  if (error || !template) {
    return null;
  }

  return template;
}

export default async function TemplateEditPage({ params }: TemplateEditPageProps) {
  const { id } = await params;
  const template = await getTemplate(id);

  if (!template) {
    notFound();
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold text-gray-900">メールテンプレート編集</h1>
      </div>

      <TemplateEditForm template={template} />
    </div>
  );
}
