import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Loader2 } from 'lucide-react';
import { getModules, toggleModule, type ModuleInfo } from '../lib/api';
import { Card } from '../components/ui';
import { ErrorNotice } from '../components/ErrorNotice';

function ModuleRow({ module, onToggle, busy }: { module: ModuleInfo; onToggle: (enabled: boolean) => void; busy: boolean }) {
  return <li className="flex items-start justify-between gap-4 py-4">
    <div>
      <b>{module.name}</b>
      <p className="text-sm text-slate-500">{module.description}</p>
      {module.requires.length > 0 && <p className="mt-1 text-xs text-slate-500">Requires: {module.requires.join(', ')}</p>}
      {module.required_by.length > 0 && <p className="text-xs text-slate-500">Needed by: {module.required_by.join(', ')}</p>}
    </div>
    <button
      role="switch" aria-checked={module.enabled} aria-label={`${module.enabled ? 'Disable' : 'Enable'} ${module.name}`}
      disabled={busy} onClick={() => onToggle(!module.enabled)}
      className={`relative h-6 w-11 shrink-0 rounded-full transition disabled:opacity-50 ${module.enabled ? 'bg-aicore-500' : 'bg-slate-400/50'}`}
    ><span className={`absolute top-0.5 h-5 w-5 rounded-full bg-white transition-all ${module.enabled ? 'left-[22px]' : 'left-0.5'}`} /></button>
  </li>;
}

export function Modules() {
  const queryClient = useQueryClient();
  const modules = useQuery({ queryKey: ['modules'], queryFn: getModules });
  const toggle = useMutation({
    mutationFn: ({ id, enabled }: { id: string; enabled: boolean }) => toggleModule(id, enabled),
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['modules'] })
  });

  if (modules.isLoading) {
    return <Card><Loader2 className="animate-spin" /></Card>;
  }
  if (modules.error || !modules.data) {
    return <ErrorNotice error={modules.error ?? new Error('Could not load modules.')} />;
  }
  return <Card>
    <p className="mb-2 max-w-3xl text-sm text-slate-500">Turn features on or off. A disabled module registers no endpoints. Modules that depend on each other must be switched off dependents-first.</p>
    <ErrorNotice error={toggle.error} className="mb-2" />
    <ul className="divide-y divide-slate-200 dark:divide-white/10">
      {modules.data.map((module) => <ModuleRow key={module.id} module={module} busy={toggle.isPending} onToggle={(enabled) => toggle.mutate({ id: module.id, enabled })} />)}
    </ul>
  </Card>;
}
