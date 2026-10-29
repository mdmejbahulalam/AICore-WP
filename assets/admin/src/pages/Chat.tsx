import { useEffect, useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Brain, Loader2, MessageSquarePlus, Pencil, Send, Square, Trash2 } from 'lucide-react';
import {
  createConversation, deleteConversation, getConversations, getMessages, getSettings, renameConversation, streamMessage,
  type ChatMessage, type Conversation
} from '../lib/api';
import { Button, Card } from '../components/ui';
import { ErrorNotice } from '../components/ErrorNotice';

const field = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-slate-900';

function Bubble({ role, children, meta }: { role: 'user' | 'assistant'; children: string; meta?: string }) {
  const mine = role === 'user';
  return <div className={`flex ${mine ? 'justify-end' : 'justify-start'}`}>
    <div className={`max-w-[85%] rounded-2xl px-4 py-3 text-sm ${mine ? 'bg-aicore-500 text-white' : 'bg-slate-100 dark:bg-white/5'}`}>
      <p className="whitespace-pre-wrap break-words">{children}</p>
      {meta && <p className="mt-1 text-[11px] opacity-60">{meta}</p>}
    </div>
  </div>;
}

export function Chat() {
  const queryClient = useQueryClient();
  const [activeId, setActiveId] = useState<number | null>(null);
  const [draft, setDraft] = useState('');
  const [provider, setProvider] = useState('');
  const [model, setModel] = useState('');
  const [busy, setBusy] = useState(false);
  const [pendingUser, setPendingUser] = useState<string | null>(null);
  const [live, setLive] = useState('');
  const [error, setError] = useState<unknown>(null);
  const abort = useRef<AbortController | null>(null);
  const bottom = useRef<HTMLDivElement | null>(null);

  const settings = useQuery({ queryKey: ['settings'], queryFn: getSettings });
  const conversations = useQuery({ queryKey: ['conversations'], queryFn: getConversations });
  const messages = useQuery({ queryKey: ['messages', activeId], queryFn: () => getMessages(activeId as number), enabled: activeId !== null });

  const usable = (settings.data?.providers ?? []).filter((p) => p.has_key || !p.needs_key);
  const selected = settings.data?.providers.find((p) => p.id === (provider || settings.data?.default_provider));

  useEffect(() => { bottom.current?.scrollIntoView({ block: 'end' }); }, [messages.data, live, pendingUser]);

  const rename = useMutation({
    mutationFn: ({ id, title }: { id: number; title: string }) => renameConversation(id, title),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['conversations'] })
  });
  const remove = useMutation({
    mutationFn: (id: number) => deleteConversation(id),
    onSuccess: (_, id) => {
      if (activeId === id) { setActiveId(null); }
      queryClient.invalidateQueries({ queryKey: ['conversations'] });
    }
  });

  const open = (conversation: Conversation | null) => {
    setActiveId(conversation?.id ?? null);
    setProvider(conversation?.provider ?? '');
    setModel(conversation?.model ?? '');
    setError(null);
  };

  async function send() {
    const content = draft.trim();
    if (!content || busy) { return; }
    const options = { ...(provider ? { provider } : {}), ...(model.trim() ? { model: model.trim() } : {}) };
    setBusy(true); setError(null); setDraft(''); setPendingUser(content); setLive('');
    const controller = new AbortController();
    abort.current = controller;
    let id = activeId;
    try {
      if (id === null) {
        id = (await createConversation(options)).id;
        setActiveId(id);
      }
      await streamMessage(id, { content, ...options }, (text) => setLive((current) => current + text), controller.signal);
    } catch (caught) {
      if (!(caught instanceof DOMException && caught.name === 'AbortError')) { setError(caught); }
    } finally {
      setBusy(false); setPendingUser(null); setLive('');
      abort.current = null;
      if (id !== null) { await queryClient.invalidateQueries({ queryKey: ['messages', id] }); }
      queryClient.invalidateQueries({ queryKey: ['conversations'] });
    }
  }

  const shown: ChatMessage[] = messages.data ?? [];
  const noProvider = settings.data && usable.length === 0;

  return <div className="grid gap-4 lg:grid-cols-[260px_1fr]">
    <Card className="flex max-h-[calc(100vh-12rem)] flex-col">
      <Button onClick={() => open(null)} className="mb-3 flex items-center justify-center gap-2 text-sm"><MessageSquarePlus size={16} />New chat</Button>
      <ul className="-mx-2 flex-1 space-y-1 overflow-y-auto px-2">
        {conversations.data?.map((c) => <li key={c.id} className={`group flex items-center gap-1 rounded-xl px-2 py-2 text-sm ${c.id === activeId ? 'bg-aicore-500/10 text-aicore-500' : 'hover:bg-slate-100 dark:hover:bg-white/5'}`}>
          <button className="min-w-0 flex-1 truncate text-left" onClick={() => open(c)}>{c.title || 'New chat'}</button>
          <button aria-label="Rename conversation" className="opacity-0 group-hover:opacity-70" onClick={() => { const title = window.prompt('Rename conversation', c.title); if (title !== null) { rename.mutate({ id: c.id, title }); } }}><Pencil size={13} /></button>
          <button aria-label="Delete conversation" className="opacity-0 group-hover:opacity-70" onClick={() => { if (window.confirm('Delete this conversation?')) { remove.mutate(c.id); } }}><Trash2 size={13} /></button>
        </li>)}
        {conversations.data?.length === 0 && <li className="px-2 text-xs text-slate-500">No conversations yet.</li>}
      </ul>
      <ErrorNotice error={conversations.error ?? rename.error ?? remove.error} className="mt-2" />
    </Card>

    <Card className="flex min-h-[28rem] flex-col">
      <div className="mb-3 flex flex-wrap items-center gap-2 text-sm">
        <Brain size={16} className="text-aicore-500" />
        <select aria-label="Provider" className={field} value={provider} onChange={(e) => { setProvider(e.target.value); setModel(''); }}>
          <option value="">Default provider{settings.data ? ` (${settings.data.providers.find((p) => p.id === settings.data.default_provider)?.label ?? ''})` : ''}</option>
          {usable.map((p) => <option key={p.id} value={p.id}>{p.label}</option>)}
        </select>
        <input aria-label="Model" className={`${field} w-56`} value={model} onChange={(e) => setModel(e.target.value)} placeholder={selected?.model || selected?.default_model || 'Model'} />
      </div>

      {noProvider && <ErrorNotice error={new Error('No AI provider is configured yet. Add an API key under Provider Settings.')} className="mb-3" />}

      <div className="flex-1 space-y-3 overflow-y-auto pr-1" style={{ maxHeight: 'calc(100vh - 21rem)' }}>
        {activeId === null && !pendingUser && <p className="py-10 text-center text-sm text-slate-500">Ask anything about your site. Your saved memories are used automatically.</p>}
        {messages.isLoading && <Loader2 className="animate-spin" />}
        {shown.map((m) => <Bubble key={m.id} role={m.role} meta={m.role === 'assistant' && m.model ? `${m.model} · ${m.tokens} tokens` : undefined}>{m.content}</Bubble>)}
        {pendingUser && <Bubble role="user">{pendingUser}</Bubble>}
        {busy && (live ? <Bubble role="assistant">{live}</Bubble> : <div className="flex items-center gap-2 text-sm text-slate-500"><Loader2 size={14} className="animate-spin" />Thinking…</div>)}
        <ErrorNotice error={error ?? messages.error} />
        <div ref={bottom} />
      </div>

      <form className="mt-3 flex items-end gap-2" onSubmit={(e) => { e.preventDefault(); void send(); }}>
        <textarea
          aria-label="Message" rows={2} value={draft} onChange={(e) => setDraft(e.target.value)} disabled={busy}
          onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); void send(); } }}
          placeholder="Message AICore… (Enter to send, Shift+Enter for a new line)" className={`${field} min-h-[3rem] flex-1 resize-none`}
        />
        {busy
          ? <Button type="button" onClick={() => abort.current?.abort()} className="flex items-center gap-2 bg-slate-700"><Square size={14} />Stop</Button>
          : <Button type="submit" disabled={!draft.trim()} className="flex items-center gap-2"><Send size={14} />Send</Button>}
      </form>
    </Card>
  </div>;
}
