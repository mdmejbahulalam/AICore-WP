import { useMutation } from '@tanstack/react-query';
import { Send } from 'lucide-react';
import { useState } from 'react';
import { runCommand } from '../lib/api';
import { Button, Card } from './ui';

export function FloatingAssistant() {
  const [prompt, setPrompt] = useState('Analyze my site and suggest the next best growth workflow.');
  const [messages, setMessages] = useState<string[]>([]);
  const command = useMutation({ mutationFn: runCommand, onSuccess: (data) => setMessages((items) => [data.message, ...items]) });
  return <Card className="fixed bottom-6 right-6 z-30 w-[420px] max-w-[calc(100vw-2rem)]">
    <div className="mb-3 flex items-center justify-between"><b>Floating AI Assistant</b><span className="text-xs text-aicore-500">stream-ready</span></div>
    <textarea value={prompt} onChange={(event) => setPrompt(event.target.value)} className="h-24 w-full rounded-2xl border border-slate-200 bg-white/80 p-3 text-sm dark:border-white/10 dark:bg-slate-900" />
    <Button disabled={command.isPending} onClick={() => command.mutate(prompt)} className="mt-3 flex items-center gap-2"><Send size={16}/>Run command</Button>
    <div className="mt-4 max-h-48 space-y-2 overflow-auto text-sm">{messages.map((message, index) => <p key={index} className="rounded-2xl bg-slate-100 p-3 dark:bg-white/5">{message}</p>)}</div>
  </Card>;
}
