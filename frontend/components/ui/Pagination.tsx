'use client';

import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from './Button';

interface PaginationProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
  className?: string;
}

export function Pagination({ currentPage, lastPage, onPageChange, className }: PaginationProps) {
  if (lastPage <= 1) return null;

  const pages = getPageNumbers(currentPage, lastPage);

  return (
    <div className={cn('flex items-center justify-center gap-1', className)}>
      <Button
        variant="ghost"
        size="sm"
        onClick={() => onPageChange(currentPage - 1)}
        disabled={currentPage === 1}
        className="h-8 w-8 p-0"
      >
        <ChevronLeft className="h-4 w-4" />
      </Button>

      {pages.map((page, i) => {
        if (page === '...') {
          return (
            <span key={`ellipsis-${i}`} className="px-2 text-[var(--color-text-muted)]">
              ...
            </span>
          );
        }

        return (
          <Button
            key={page}
            variant={currentPage === page ? 'primary' : 'ghost'}
            size="sm"
            onClick={() => onPageChange(page as number)}
            className="h-8 w-8 p-0"
          >
            {page}
          </Button>
        );
      })}

      <Button
        variant="ghost"
        size="sm"
        onClick={() => onPageChange(currentPage + 1)}
        disabled={currentPage === lastPage}
        className="h-8 w-8 p-0"
      >
        <ChevronRight className="h-4 w-4" />
      </Button>
    </div>
  );
}

function getPageNumbers(current: number, last: number): (number | '...')[] {
  const delta = 1;
  const range: number[] = [];
  const rangeWithDots: (number | '...')[] = [];

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
      range.push(i);
    }
  }

  let prev = 0;
  for (const item of range) {
    if (prev !== 0) {
      if (item - prev === 2) {
        rangeWithDots.push(prev + 1);
      } else if (item - prev !== 1) {
        rangeWithDots.push('...');
      }
    }
    rangeWithDots.push(item);
    prev = item;
  }

  return rangeWithDots;
}
