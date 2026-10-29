import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Check, KeyRound, Loader2, PlugZap } from 'lucide-react';
import { getSettings, saveProvider, setDefaultProvider, testProvider, type ProviderInfo, type TestResult } from '../lib/api';
import { Badge, Button, Card } from '../components/ui';
import { ErrorNotice } from '../components/ErrorNotice';

const field = 'w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-2 text-sm dark:border-white/10 dark:bg-slate-900';

function ProviderCard({ provider, isDefault }: { provider: ProviderInfo; isDefault: boolean }) {
  const queryClient = useQueryClient();
  const [apiKey, setApiKey] = useState('');
  const [model, setModel] = useState(provider.model);
  const [maxTokens, setMaxTokens] = useState(provider.max_tokens ? String(provider.max_tokens) : '');
  const [baseUrl, setBaseUrl] = useState(provider.base_url);
  const [saved, setSaved] = useState(false);
  const [result, setResult] = useState<TestResult | null>(null);

  const refresh = () => queryClient.invalidateQueries({ queryKey: ['settings'] });
  const save = useMutation({
    mutationFn: () => saveProvider(provider.id, { ...(apiKey ? { api_key: apiKey } : {}), model, max_tokens: Number(maxTokens) || 0, base_url: baseUrl }),
    onSuccess: () => { setApiKey(''); setSaved(true); setResult(null); refresh(); }
  });
  const clear = useMutation({ mutationFn: () => saveProvider(provider.id, { clear_key: true }), onSuccess: () => { setResult(null); refresh(); } });
  const test = useMutation({ mutationFn: () => testProvider(provider.id), onSuccess: setResult });
  const makeDefault = useMutation({ mutationFn: () => setDefaultProvider(provider.id), onSuccess: refresh });

  const configured = provider.has_key || !provider.needs_key;
  return <Card>
    <div className="flex flex-wrap items-center justify-between gap-2">
      <div className="flex items-center gap-2"><b className="text-lg">{provider.label}</b>{isDefault && <Badge>Default</Badge>}</div>
      <span className={`text-xs ${configured ? 'text-emerald-500' : 'text-slate-500'}`}>{provider.needs_key ? (provider.has_key ? `Key saved ${provider.key_hint}` : 'No key saved') : 'No key needed'}</span>
    </div>
    <div className="mt-4 grid gap-3 md:grid-cols-2">
      {provider.needs_key && <label className="text-xs text-slate-500">API key
        <input type="password" autoComplete="new-password" value={apiKey} onChange={(e) => { setApiKey(e.target.value); setSaved(false); }} placeholder={provider.has_key ? 'Leave blank to keep the saved key' : 'Paste your API key'} className={field} />
      </label>}
      <label className="text-xs text-slate-500">Model
        <input value={model} onChange={(e) => { setModel(e.target.value); setSaved(false); }} placeholder={provider.default_model} className={field} />
      </label>
      <label className="text-xs text-slate-500">Max tokens per reply
        <input inputMode="numeric" value={maxTokens} onChange={(e) => { setMaxTokens(e.target.value.replace(/\D/g, '')); setSaved(false); }} placeholder="1024" className={field} />
      </label>
      <label className="text-xs text-slate-500">Custom endpoint (optional)
        <input value={baseUrl} onChange={(e) => { setBaseUrl(e.target.value); setSaved(false); }} placeholder="Leave blank for the default URL" className={field} />
      </label>
    </div>
    <div className="mt-4 flex flex-wrap items-center gap-2">
      <Button onClick={() => save.mutate()} disabled={save.isPending} className="flex items-center gap-2 text-sm"><KeyRound size={15} />Save</Button>
      <Button onClick={() => test.mutate()} disabled={test.isPending || !configured} className="flex items-center gap-2 bg-slate-700 text-sm">{test.isPending ? <Loader2 size={15} className="animate-spin" /> : <PlugZap size={15} />}Test connection</Button>
      {!isDefault && <Button onClick={() => makeDefault.mutate()} disabled={makeDefault.isPending || !configured} className="bg-transparent text-sm text-aicore-500 shadow-none ring-1 ring-aicore-500/40 hover:bg-aicore-500/10">Make default</Button>}
      {provider.has_key && <button onClick={() => clear.mutate()} disabled={clear.isPending} className="text-xs text-red-500 underline">Remove key</button>}
      {saved && <span className="flex items-center gap-1 text-xs text-emerald-500"><Check size={14} />Saved</span>}
    </div>
    {result?.ok === true && <p className="mt-3 text-sm text-emerald-500">Connected — {result.model} replied “{result.reply}” in {result.latency_ms} ms.</p>}
    {result?.ok === false && <ErrorNotice className="mt-3" error={new Error(result.error)} />}
    <ErrorNotice className="mt-3" error={save.error ?? clear.error ?? test.error ?? makeDefault.error} />
  </Card>;
}

export function ProviderSettings() {
  const settings = useQuery({ queryKey: ['settings'], queryFn: getSettings });
  if (settings.isLoading) {
    return <Card><Loader2 className="animate-spin" /></Card>;
  }
  if (settings.error || !settings.data) {
    return <ErrorNotice error={settings.error ?? new Error('Could not load settings.')} />;
  }
  return <div className="space-y-4">
    <p className="max-w-3xl text-sm text-slate-500">Keys are encrypted before they are stored and are never sent back to the browser. The default provider is used whenever a feature does not pick one.</p>
    <div className="grid gap-4 xl:grid-cols-2">
      {settings.data.providers.map((provider) => <ProviderCard key={provider.id} provider={provider} isDefault={settings.data.default_provider === provider.id} />)}
    </div>
  </div>;
}
