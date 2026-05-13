import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useAppStore } from './stores/app-store';
import { Shell } from './components/Shell';
import { FloatingAssistant } from './components/FloatingAssistant';
import { Dashboard } from './pages/Dashboard';
import { FeaturePage } from './pages/FeaturePage';

const queryClient = new QueryClient();

export function App() {
  const activePage = useAppStore((state) => state.activePage);
  const theme = useAppStore((state) => state.theme);
  return <QueryClientProvider client={queryClient}>
    <div className={theme === 'dark' ? 'dark' : ''}>
      <Shell>{activePage === 'Dashboard' ? <Dashboard/> : <FeaturePage title={activePage}/>}</Shell>
      <FloatingAssistant />
    </div>
  </QueryClientProvider>;
}
