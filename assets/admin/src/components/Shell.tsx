import type { ReactNode } from 'react';
import { Bell, Bot, BrainCircuit, CreditCard, Database, LayoutDashboard, Lock, MessagesSquare, PlugZap, Search, Settings, ShoppingCart, Sparkles, Workflow } from 'lucide-react';
import { motion } from 'framer-motion';
import { useAppStore } from '../stores/app-store';

const nav = [
  ['Dashboard', LayoutDashboard], ['AI Chat Center', MessagesSquare], ['Content Studio', Sparkles], ['SEO Center', Search],
  ['Workflow Automation', Workflow], ['Chatbot Builder', Bot], ['AI Playground', BrainCircuit], ['Analytics', LayoutDashboard],
  ['MCP Settings', PlugZap], ['API Settings', Settings], ['Integrations', PlugZap], ['WooCommerce AI', ShoppingCart],
  ['Billing', CreditCard], ['Security Logs', Lock], ['Knowledge Base', Database], ['AI Memory Manager', BrainCircuit]
] as const;

export function Shell({ children }: { children: ReactNode }) {
  const { activePage, setActivePage, notifications } = useAppStore();
  return <div className="min-h-screen bg-slate-100 text-slate-950 dark:bg-[#050814] dark:text-white">
    <aside className="fixed inset-y-0 left-0 z-20 hidden w-72 border-r border-white/10 bg-slate-950/95 p-4 text-white xl:block">
      <div className="mb-8 flex items-center gap-3 px-2"><div className="rounded-2xl bg-aicore-500 p-2"><BrainCircuit /></div><div><b>AICore WP</b><p className="text-xs text-slate-400">AI Operating System</p></div></div>
      <nav className="space-y-1">{nav.map(([label, Icon]) => <button key={label} onClick={() => setActivePage(label)} className={`flex w-full items-center gap-3 rounded-2xl px-3 py-2 text-left text-sm transition ${activePage === label ? 'bg-aicore-500 text-white' : 'text-slate-300 hover:bg-white/10'}`}><Icon size={17}/>{label}</button>)}</nav>
    </aside>
    <main className="xl:pl-72">
      <header className="sticky top-0 z-10 flex items-center justify-between border-b border-white/10 bg-white/70 px-6 py-4 backdrop-blur dark:bg-slate-950/70">
        <div><h1 className="text-2xl font-bold">{activePage}</h1><p className="text-sm text-slate-500">Welcome, {window.AICoreWP.user} · {window.AICoreWP.site}</p></div>
        <div className="flex items-center gap-3"><button className="rounded-2xl border border-white/10 p-3"><Bell size={18}/></button><span className="rounded-full bg-emerald-500/15 px-3 py-1 text-xs text-emerald-500">{notifications.length} alerts</span></div>
      </header>
      <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="p-6">{children}</motion.div>
    </main>
  </div>;
}
