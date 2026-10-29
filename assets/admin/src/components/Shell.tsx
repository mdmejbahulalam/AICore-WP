import type { ReactNode } from 'react';
import { Bell, Boxes, Bot, BrainCircuit, CreditCard, Database, Gauge, KeyRound, LayoutDashboard, Lock, MessagesSquare, PlugZap, Moon, Search, Settings, ShoppingCart, Sparkles, Sun, Workflow } from 'lucide-react';
import { motion } from 'framer-motion';
import { useAppStore } from '../stores/app-store';

const nav = [
  ['Dashboard', LayoutDashboard], ['Provider Settings', KeyRound], ['Modules', Boxes], ['Usage', Gauge], ['AI Chat Center', MessagesSquare], ['Content Studio', Sparkles], ['SEO Center', Search],
  ['Workflow Automation', Workflow], ['Chatbot Builder', Bot], ['AI Playground', BrainCircuit], ['Analytics', LayoutDashboard],
  ['MCP Settings', PlugZap], ['API Settings', Settings], ['Integrations', PlugZap], ['WooCommerce AI', ShoppingCart],
  ['Billing', CreditCard], ['Security Logs', Lock], ['Knowledge Base', Database], ['AI Memory Manager', BrainCircuit]
] as const;

export function Shell({ children }: { children: ReactNode }) {
  const { activePage, setActivePage, notifications } = useAppStore();
  const { theme, setTheme } = useAppStore();
  // The admin app lives inside WordPress's own chrome, so the sidebar is an in-flow sticky column
  // (offset by the 32px admin bar) rather than fixed to the viewport edge, where it would sit under the WP menu.
  return <div className="min-h-[calc(100vh-2rem)] bg-slate-50 text-slate-950 dark:bg-[#050814] dark:text-white xl:flex">
    <aside className="sticky top-8 max-xl:hidden max-h-[calc(100vh-2rem)] w-72 shrink-0 self-start overflow-y-auto border-r border-slate-200 bg-white p-4 text-slate-900 dark:border-white/10 dark:bg-slate-950/95 dark:text-white">
      <div className="mb-8 flex items-center gap-3 px-2"><div className="rounded-2xl bg-aicore-500 p-2"><BrainCircuit /></div><div><b>AICore WP</b><p className="text-xs text-slate-400">AI Operating System</p></div></div>
      <nav className="space-y-1">{nav.map(([label, Icon]) => <button key={label} onClick={() => setActivePage(label)} className={`flex w-full items-center gap-3 rounded-2xl px-3 py-2 text-left text-sm transition ${activePage === label ? 'bg-aicore-500 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10'}`}><Icon size={17}/>{label}</button>)}</nav>
    </aside>
    <main className="min-w-0 flex-1">
      <header className="sticky top-8 z-10 flex items-center justify-between border-b border-slate-200 dark:border-white/10 bg-white/70 px-6 py-4 backdrop-blur dark:bg-slate-950/70">
        <div><h1 className="text-2xl font-bold">{activePage}</h1><p className="text-sm text-slate-500">Welcome, {window.AICoreWP.user} · {window.AICoreWP.site}</p></div>
        <div className="flex items-center gap-3">
          <select aria-label="Go to page" value={activePage} onChange={(event) => setActivePage(event.target.value)} className="rounded-2xl border border-slate-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm xl:hidden">{nav.map(([label]) => <option key={label} value={label} className="text-slate-950">{label}</option>)}</select>
          <button aria-label={theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'} onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')} className="rounded-2xl border border-slate-200 p-3 dark:border-white/10">{theme === 'dark' ? <Sun size={18}/> : <Moon size={18}/>}</button>
          <button aria-label="Notifications" className="rounded-2xl border border-slate-200 dark:border-white/10 p-3"><Bell size={18}/></button><span className="rounded-full bg-emerald-500/15 px-3 py-1 text-xs text-emerald-500">{notifications.length} alerts</span></div>
      </header>
      <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="p-6">{children}</motion.div>
    </main>
  </div>;
}
