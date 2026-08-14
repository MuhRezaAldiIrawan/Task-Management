'use client';

import { Lock } from 'lucide-react';
import { LoginForm } from '@/components/auth';
import { ToastContainer } from '@/components/ui';

export default function LoginPage() {
  return (
    <div className="min-h-screen flex">
      {/* Left Side - Branding */}
      <div className="hidden lg:flex lg:w-1/2 bg-[var(--color-primary)] p-12 flex-col justify-between">
        <div>
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-xl bg-[var(--color-accent)] flex items-center justify-center">
              <Lock className="h-6 w-6 text-white" />
            </div>
            <span className="text-2xl font-bold text-white">Task Manager</span>
          </div>
        </div>

        <div className="space-y-6">
          <h1 className="text-4xl font-bold text-white leading-tight">
            Streamline your<br />
            workflow with<br />
            <span className="text-[var(--color-accent)]">Task Manager</span>
          </h1>
          <p className="text-lg text-white/70 max-w-md">
            Organize, track, and manage all your tasks in one place.
            Collaborate with your team and never miss a deadline.
          </p>
        </div>

        <div className="flex items-center gap-8 text-white/60 text-sm">
          <span>© 2024 Task Manager</span>
          <span>All rights reserved</span>
        </div>
      </div>

      {/* Right Side - Login Form */}
      <div className="flex-1 flex items-center justify-center p-8 bg-[var(--color-background)]">
        <div className="w-full max-w-md">
          {/* Mobile Logo */}
          <div className="lg:hidden flex items-center justify-center gap-3 mb-8">
            <div className="w-10 h-10 rounded-lg bg-[var(--color-accent)] flex items-center justify-center">
              <Lock className="h-5 w-5 text-white" />
            </div>
            <span className="text-xl font-bold text-[var(--color-text-primary)]">Task Manager</span>
          </div>

          <div className="text-center lg:text-left mb-8">
            <h2 className="text-2xl font-bold text-[var(--color-text-primary)]">
              Welcome back
            </h2>
            <p className="mt-2 text-[var(--color-text-secondary)]">
              Sign in to access your dashboard
            </p>
          </div>

          <LoginForm />
        </div>
      </div>

      <ToastContainer />
    </div>
  );
}
