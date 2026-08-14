'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { auth } from '@/lib/api';

export default function HomePage() {
  const router = useRouter();

  useEffect(() => {
    // Redirect based on auth status
    if (auth.isAuthenticated()) {
      router.push('/dashboard');
    } else {
      router.push('/auth/login');
    }
  }, [router]);

  // Loading state
  return (
    <div className="min-h-screen flex items-center justify-center bg-[var(--color-background)]">
      <div className="flex flex-col items-center gap-4">
        <div className="w-10 h-10 border-4 border-[var(--color-accent)] border-t-transparent rounded-full animate-spin" />
        <p className="text-[var(--color-text-secondary)]">Loading...</p>
      </div>
    </div>
  );
}
