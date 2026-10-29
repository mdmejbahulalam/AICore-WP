import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Loader2, Pencil, RefreshCw, Trash2, X } from 'lucide-react';
import { createMemory, deleteMemory, getMemories, reindexMemories, updateMemory, type Memory } from '../lib/api';
import { Badge, Button, Card } from '../components/ui';
import { ErrorNotice } from '../components/ErrorNotice';

const field = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-slate-900';

export function MemoryManager() {
  const queryClient = useQueryClient();
  const [editing, setEditing] = useState<Memory | null>(null);
  const [key, setKey] = useState('');
  const [value, setValue] = useState('');
  const [scope, setScope] = useState<'site' | 'user'>('site');
  const [notice, setNotice] = useState('');

  const memories = useQuery({ queryKey: ['memories'], queryFn: getMemories });
  const refresh = () => queryClient.invalidateQueries({ queryKey: ['memories'] });
  const reset = () => { setEditing(null); setKey(''); setValue(''); setScope('site'); };

  const save = useMutation({
    mutationFn: () => (editing ? updateMemory(editing.id, { key, value }) : createMemory({ key, value, scope })),
    onSuccess: (memory) => {
      setNotice(memory.embedded ? 'Saved and indexed for semantic recall.' : 'Saved. It was not embedded (no embeddings provider available), so it is used by confidence and recency.');
      reset(); refresh();
    }
  });
  const remove = useMutation({ mutationFn: (id: number) => deleteMemory(id), onSuccess: refresh });
  const reindex = useMutation({
    mutationFn: reindexMemories,
    onSuccess: (r) => { setNotice(r.failed > 0 ? `Embedded ${r.embedded}, failed ${r.failed}: ${r.error}` : `Embedded ${r.embedded} memories.`); refresh(); }
  });

  const startEdit = (memory: Memory) => { setEditing(memory); setKey(memory.key); setValue(memory.value); setScope(memory.scope); setNotice(''); };

  return <div className="grid gap-4 xl:grid-cols-[360px_1fr]">
    <Card>
      <h2 className="text-lg font-bold">{editing ? 'Edit memory' : 'Add a memory'}</h2>
      <p className="mt-1 text-xs text-slate-500">Short facts the assistant should remember, e.g. brand voice, audience, product details.</p>
      <div className="mt-4 space-y-3">
        <label className="block text-xs text-slate-500">Title
          <input className={field} value={key} onChange={(e) => setKey(e.target.value)} maxLength={191} placeholder="Brand voice" />
        </label>
        <label className="block text-xs text-slate-500">Details
          <textarea className={`${field} min-h-[6rem]`} value={value} onChange={(e) => setValue(e.target.value)} maxLength={4000} placeholder="Friendly, concise, no jargon." />
        </label>
        {!editing && <label className="block text-xs text-slate-500">Applies to
          <select className={field} value={scope} onChange={(e) => setScope(e.target.value as 'site' | 'user')}>
            <option value="site">Whole site (all admins)</option>
            <option value="user">Only me</option>
          </select>
        </label>}
        <div className="flex items-center gap-2">
          <Button onClick={() => save.mutate()} disabled={save.isPending || !key.trim() || !value.trim()} className="text-sm">{save.isPending ? 'Saving…' : editing ? 'Save changes' : 'Add memory'}</Button>
          {editing && <button onClick={reset} className="flex items-center gap-1 text-xs text-slate-500"><X size={13} />Cancel</button>}
        </div>
        <ErrorNotice error={save.error} />
      </div>
    </Card>

    <Card>
      <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 className="text-lg font-bold">Saved memories</h2>
        <Button onClick={() => reindex.mutate()} disabled={reindex.isPending} className="flex items-center gap-2 bg-slate-700 text-sm">
          {reindex.isPending ? <Loader2 size={14} className="animate-spin" /> : <RefreshCw size={14} />}Embed missing
        </Button>
      </div>
      {notice && <p className="mb-3 rounded-xl bg-emerald-500/10 p-3 text-sm text-emerald-600 dark:text-emerald-400">{notice}</p>}
      <ErrorNotice error={memories.error ?? remove.error ?? reindex.error} className="mb-3" />
      {memories.isLoading && <Loader2 className="animate-spin" />}
      {memories.data?.length === 0 && <p className="text-sm text-slate-500">No memories yet. Add one on the left and it will be used in chats.</p>}
      <ul className="divide-y divide-slate-200 dark:divide-white/10">
        {memories.data?.map((m) => <li key={m.id} className="flex items-start justify-between gap-3 py-3">
          <div className="min-w-0">
            <div className="flex flex-wrap items-center gap-2"><b>{m.key}</b><Badge>{m.scope === 'site' ? 'Site' : 'Me'}</Badge>
              <span className={`text-[11px] ${m.embedded ? 'text-emerald-500' : 'text-slate-500'}`}>{m.embedded ? 'semantic recall' : 'not embedded'}</span></div>
            <p className="mt-1 whitespace-pre-wrap break-words text-sm text-slate-600 dark:text-slate-300">{m.value}</p>
          </div>
          <div className="flex shrink-0 gap-2">
            <button aria-label={`Edit ${m.key}`} onClick={() => startEdit(m)} className="rounded-lg p-1.5 hover:bg-slate-100 dark:hover:bg-white/10"><Pencil size={15} /></button>
            <button aria-label={`Delete ${m.key}`} onClick={() => { if (window.confirm(`Delete “${m.key}”?`)) { remove.mutate(m.id); } }} className="rounded-lg p-1.5 text-red-500 hover:bg-red-500/10"><Trash2 size={15} /></button>
          </div>
        </li>)}
      </ul>
    </Card>
  </div>;
}
