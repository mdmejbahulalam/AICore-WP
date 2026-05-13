import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { clsx } from 'clsx';

export function Card({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <section className={clsx('rounded-3xl border border-white/10 bg-white/80 p-5 shadow-xl backdrop-blur dark:bg-slate-950/70', className)}>{children}</section>;
}

export function Button({ children, className = '', ...props }: ButtonHTMLAttributes<HTMLButtonElement>) {
  return <button className={clsx('rounded-2xl bg-aicore-500 px-4 py-2 font-semibold text-white shadow-glow transition hover:bg-aicore-700 disabled:opacity-50', className)} {...props}>{children}</button>;
}

export function Badge({ children }: { children: ReactNode }) {
  return <span className="rounded-full border border-aicore-500/30 bg-aicore-500/10 px-3 py-1 text-xs font-semibold text-aicore-500">{children}</span>;
}
