import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useAppStore } from './stores/app-store';
import { Shell } from './components/Shell';
import { FloatingAssistant } from './components/FloatingAssistant';
import { ErrorBoundary } from './components/ErrorNotice';
import { Dashboard } from './pages/Dashboard';
import { FeaturePage } from './pages/FeaturePage';
import { ProviderSettings } from './pages/ProviderSettings';
import { Modules } from './pages/Modules';
import { Usage } from './pages/Usage';
import { Chat } from './pages/Chat';
import { MemoryManager } from './pages/Memory';

const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false, refetchOnWindowFocus: false } } });

function Page({ name }: { name: string }): ReactNode {
  switch (name) {
    case 'Dashboard': return <Dashboard />;
    case 'Provider Settings': return <ProviderSettings />;
    case 'Modules': return <Modules />;
    case 'Usage': return <Usage />;
    case 'AI Chat Center': return <Chat />;
    case 'AI Memory Manager': return <MemoryManager />;
    default: return <FeaturePage title={name} />;
  }
}

export function App() {
  const activePage = useAppStore((state) => state.activePage);
  const theme = useAppStore((state) => state.theme);
  return <QueryClientProvider client={queryClient}>
    <div className={theme === 'dark' ? 'dark' : ''}>
      <Shell><ErrorBoundary key={activePage}><Page name={activePage} /></ErrorBoundary></Shell>
      {activePage !== 'AI Chat Center' && <FloatingAssistant />}
    </div>
  </QueryClientProvider>;
}
