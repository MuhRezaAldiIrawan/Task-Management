'use client';

import { useState } from 'react';
import { cn } from '@/lib/utils';
import { Search, Bell, ChevronDown, LogOut, User } from 'lucide-react';
import { Input } from '@/components/ui/Input';
import { Avatar } from '@/components/ui/Avatar';

interface TopBarProps {
  user?: {
    name: string;
    email: string;
    avatar?: string;
  };
  className?: string;
}

export function TopBar({ user, className }: TopBarProps) {
  const [showUserMenu, setShowUserMenu] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  const handleLogout = () => {
    localStorage.removeItem('token');
    window.location.href = '/login';
  };

  return (
    <header
      className={cn(
        'flex items-center justify-between h-16 px-6 bg-white border-b border-[var(--color-border)]',
        className
      )}
    >
      {/* Left side - Search */}
      <div className="flex items-center gap-4 flex-1 max-w-md">
        <div className="relative w-full">
          <Input
            type="search"
            placeholder="Search tasks..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            icon={<Search className="h-4 w-4" />}
            className="w-full"
          />
        </div>
      </div>

      {/* Right side - Notifications & User */}
      <div className="flex items-center gap-4">
        {/* Notifications */}
        <button className="relative p-2 rounded-md text-[var(--color-text-secondary)] hover:bg-slate-100 transition-colors">
          <Bell className="h-5 w-5" />
          <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full" />
        </button>

        {/* User Menu */}
        <div className="relative">
          <button
            onClick={() => setShowUserMenu(!showUserMenu)}
            className="flex items-center gap-2 p-1.5 rounded-md hover:bg-slate-100 transition-colors"
          >
            <Avatar
              name={user?.name || 'User'}
              src={user?.avatar}
              size="sm"
            />
            <span className="text-sm font-medium text-[var(--color-text-primary)] hidden md:block">
              {user?.name || 'User'}
            </span>
            <ChevronDown className="h-4 w-4 text-[var(--color-text-muted)] hidden md:block" />
          </button>

          {/* Dropdown Menu */}
          {showUserMenu && (
            <>
              <div
                className="fixed inset-0 z-10"
                onClick={() => setShowUserMenu(false)}
              />
              <div className="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-[var(--color-border)] py-1 z-20">
                <div className="px-4 py-3 border-b border-[var(--color-border)]">
                  <p className="text-sm font-medium text-[var(--color-text-primary)]">
                    {user?.name || 'User'}
                  </p>
                  <p className="text-xs text-[var(--color-text-muted)]">
                    {user?.email || 'user@example.com'}
                  </p>
                </div>
                <button
                  onClick={handleLogout}
                  className="w-full flex items-center gap-2 px-4 py-2 text-sm text-[var(--color-text-secondary)] hover:bg-slate-50 transition-colors"
                >
                  <LogOut className="h-4 w-4" />
                  Sign out
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    </header>
  );
}
