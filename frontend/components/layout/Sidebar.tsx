'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { cn } from '@/lib/utils';
import { LayoutDashboard, ListTodo, LogOut, Filter } from 'lucide-react';

interface SidebarProps {
  className?: string;
}

export function Sidebar({ className }: SidebarProps) {
  const pathname = usePathname();

  const navItems = [
    {
      title: 'Dashboard',
      href: '/dashboard',
      icon: LayoutDashboard,
    },
    {
      title: 'All Tasks',
      href: '/dashboard/tasks',
      icon: ListTodo,
    },
  ];

  const bottomItems = [
    {
      title: 'Logout',
      href: '/auth/login',
      icon: LogOut,
      onClick: () => {
        localStorage.removeItem('token');
        window.location.href = '/auth/login';
      },
    },
  ];

  return (
    <aside
      className={cn(
        'flex flex-col h-full bg-[var(--color-primary)] text-white',
        className
      )}
      style={{ width: 'var(--sidebar-width)' }}
    >
      {/* Logo */}
      <div className="flex items-center h-16 px-4 border-b border-white/10">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded-lg bg-[var(--color-accent)] flex items-center justify-center">
            <ListTodo className="h-5 w-5 text-white" />
          </div>
          <span className="font-semibold text-lg">Task Manager</span>
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-3 py-4 space-y-1">
        {navItems.map((item) => {
          const isActive = pathname === item.href;
          return (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                'flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors',
                isActive
                  ? 'bg-white/10 text-white'
                  : 'text-white/70 hover:bg-white/5 hover:text-white'
              )}
            >
              <item.icon className="h-5 w-5" />
              {item.title}
            </Link>
          );
        })}

        {/* Filter Section */}
        <div className="pt-4 mt-4 border-t border-white/10">
          <div className="px-3 py-2 text-xs font-semibold text-white/50 uppercase tracking-wider">
            Filters
          </div>
          <button
            className={cn(
              'w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors text-white/70 hover:bg-white/5 hover:text-white'
            )}
          >
            <Filter className="h-5 w-5" />
            Status
          </button>
          <button
            className={cn(
              'w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors text-white/70 hover:bg-white/5 hover:text-white'
            )}
          >
            <Filter className="h-5 w-5" />
            Priority
          </button>
        </div>
      </nav>

      {/* Bottom Navigation */}
      <div className="px-3 py-4 border-t border-white/10 space-y-1">
        {bottomItems.map((item) => (
          <button
            key={item.title}
            onClick={item.onClick}
            className={cn(
              'w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors text-white/70 hover:bg-white/5 hover:text-white'
            )}
          >
            <item.icon className="h-5 w-5" />
            {item.title}
          </button>
        ))}
      </div>
    </aside>
  );
}
