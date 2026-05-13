import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis } from 'recharts';
import ReactFlow, { Background, Controls, MiniMap } from 'reactflow';
import 'reactflow/dist/style.css';
import { Badge, Card } from '../components/ui';

const chart = [
  { name: 'Mon', tokens: 12000 }, { name: 'Tue', tokens: 18000 }, { name: 'Wed', tokens: 26000 },
  { name: 'Thu', tokens: 21000 }, { name: 'Fri', tokens: 34000 }, { name: 'Sat', tokens: 39000 }, { name: 'Sun', tokens: 46000 }
];

const nodes = [
  { id: 'trigger', position: { x: 0, y: 80 }, data: { label: 'Woo order' }, type: 'input' },
  { id: 'ai', position: { x: 220, y: 40 }, data: { label: 'AI decision' } },
  { id: 'email', position: { x: 440, y: 80 }, data: { label: 'Send offer' }, type: 'output' }
];
const edges = [{ id: 'e1', source: 'trigger', target: 'ai' }, { id: 'e2', source: 'ai', target: 'email' }];

export function Dashboard() {
  return <div className="grid gap-6 2xl:grid-cols-[1.4fr_.9fr]">
    <section className="space-y-6">
      <div className="grid gap-4 md:grid-cols-4">
        {['AI commands', 'Chatbot leads', 'Workflow runs', 'SEO fixes'].map((label, index) => <Card key={label}><Badge>Live</Badge><h3 className="mt-4 text-3xl font-bold">{[248, 91, 1204, 37][index]}</h3><p className="text-sm text-slate-500">{label}</p></Card>)}
      </div>
      <Card><div className="mb-4 flex items-center justify-between"><h2 className="text-xl font-bold">Token usage & AI activity</h2><Badge>enterprise metering</Badge></div><div className="h-72"><ResponsiveContainer><AreaChart data={chart}><XAxis dataKey="name"/><Tooltip/><Area dataKey="tokens" stroke="#3485ff" fill="#3485ff33"/></AreaChart></ResponsiveContainer></div></Card>
      <Card><h2 className="mb-4 text-xl font-bold">Visual workflow builder</h2><div className="h-80 overflow-hidden rounded-3xl border border-white/10"><ReactFlow nodes={nodes} edges={edges} fitView><MiniMap/><Controls/><Background/></ReactFlow></div></Card>
    </section>
    <aside className="space-y-6">
      <Card><h2 className="text-xl font-bold">AI Activity Timeline</h2>{['SEO Agent optimized 12 posts', 'Content Agent drafted landing page', 'MCP create_post approved', 'RAG indexed product catalog'].map((event) => <div key={event} className="mt-4 rounded-2xl bg-slate-100 p-3 text-sm dark:bg-white/5">{event}</div>)}</Card>
      <Card><h2 className="text-xl font-bold">Workspace</h2><p className="mt-2 text-sm text-slate-500">Production · safe execution · preview-before-publish</p></Card>
    </aside>
  </div>;
}
