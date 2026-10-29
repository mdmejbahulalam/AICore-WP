import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Loader2 } from 'lucide-react';
import { getUsage } from '../lib/api';
import { Card } from '../components/ui';
import { ErrorNotice } from '../components/ErrorNotice';

const number = new Intl.NumberFormat();

export function Usage() {
  const [days, setDays] = useState(30);
  const usage = useQuery({ queryKey: ['usage', days], queryFn: () => getUsage(days) });

  if (usage.isLoading) {
    return <Card><Loader2 className="animate-spin" /></Card>;
  }
  if (usage.error || !usage.data) {
    return <ErrorNotice error={usage.error ?? new Error('Could not load usage.')} />;
  }
  const { totals, by_model: rows } = usage.data;
  return <div className="space-y-4">
    <div className="flex items-center gap-2 text-sm">
      <span className="text-slate-500">Period</span>
      {[7, 30, 90].map((value) => <button key={value} onClick={() => setDays(value)} className={`rounded-xl px-3 py-1 ${days === value ? 'bg-aicore-500 text-white' : 'border border-slate-200 dark:border-white/10'}`}>{value} days</button>)}
    </div>
    <div className="grid gap-4 md:grid-cols-4">
      {[['Requests', number.format(totals.calls)], ['Input tokens', number.format(totals.input_tokens)], ['Output tokens', number.format(totals.output_tokens)], ['Estimated cost', `$${totals.cost.toFixed(4)}`]].map(([label, value]) =>
        <Card key={label}><p className="text-xs text-slate-500">{label}</p><b className="text-2xl">{value}</b></Card>)}
    </div>
    <Card>
      {rows.length === 0 ? <p className="text-sm text-slate-500">No AI requests recorded in this period.</p> :
        <table className="w-full text-left text-sm">
          <thead className="text-xs text-slate-500"><tr><th className="py-2">Provider</th><th>Model</th><th className="text-right">Requests</th><th className="text-right">Input</th><th className="text-right">Output</th><th className="text-right">Est. cost</th></tr></thead>
          <tbody>{rows.map((row) => <tr key={`${row.provider}:${row.model}`} className="border-t border-slate-200 dark:border-white/10">
            <td className="py-2">{row.provider}</td><td>{row.model}</td><td className="text-right">{number.format(row.calls)}</td>
            <td className="text-right">{number.format(row.input_tokens)}</td><td className="text-right">{number.format(row.output_tokens)}</td><td className="text-right">${row.cost.toFixed(4)}</td>
          </tr>)}</tbody>
        </table>}
      <p className="mt-3 text-xs text-slate-500">Cost is an estimate and stays at $0 until per-model prices are set in the <code>aicore_wp_model_prices</code> option.</p>
    </Card>
  </div>;
}
