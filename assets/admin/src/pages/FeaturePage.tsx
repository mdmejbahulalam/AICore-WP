import { Card, Badge } from '../components/ui';

const capabilities: Record<string, string[]> = {
  'AI Chat Center': ['Natural language WordPress control', 'Streaming responses', 'Tool calling', 'Conversation memory'],
  'Content Studio': ['Long-form generation', 'Brand voice memory', 'Bulk scheduling', 'Schema and FAQs'],
  'SEO Center': ['AI audits', 'Keyword clustering', 'Internal link recommendations', 'Yoast/RankMath/AIOSEO integration'],
  'Workflow Automation': ['Triggers and actions', 'AI decision nodes', 'Retries and queues', 'Webhooks'],
  'Chatbot Builder': ['RAG training', 'Floating widget', 'Human handoff', 'Lead tracking'],
  'AI Playground': ['Chat mode', 'Agent mode', 'Developer mode', 'Vision and OCR'],
  Analytics: ['Traffic insights', 'Usage analytics', 'Forecasts', 'Anomaly detection'],
  'MCP Settings': ['Tool registry', 'API key auth', 'OAuth-ready scopes', 'External agent access'],
  'API Settings': ['REST API', 'Webhooks', 'Versioning', 'Internal SDK'],
  Integrations: ['Slack', 'Discord', 'Zapier', 'Make', 'Notion', 'HubSpot'],
  'WooCommerce AI': ['Descriptions', 'Upsells', 'Forecasting', 'Support chatbot'],
  Billing: ['Token credits', 'Stripe/Paddle adapters', 'Plan restrictions', 'License activation'],
  'Security Logs': ['Audit logs', 'Rate limits', 'Permission scopes', 'Moderation'],
  'Knowledge Base': ['PDF/DOCX/URL indexing', 'Vector DB adapters', 'Semantic search', 'Chunking'],
  'AI Memory Manager': ['Long-term memories', 'Shared agent context', 'Ranking', 'Expiry controls']
};

export function FeaturePage({ title }: { title: string }) {
  const items = capabilities[title] ?? ['Modular feature pack', 'REST routes', 'Events', 'Future extension points'];
  return <div className="grid gap-6 xl:grid-cols-3">
    <Card className="xl:col-span-2"><Badge>{title}</Badge><h2 className="mt-4 text-3xl font-bold">Production-ready {title}</h2><p className="mt-3 max-w-3xl text-slate-500">This module is isolated, permission-aware, API-first, and built for SaaS commercialization with enterprise observability and extensibility.</p><div className="mt-8 grid gap-4 md:grid-cols-2">{items.map((item) => <div key={item} className="rounded-3xl border border-white/10 bg-slate-100 p-5 dark:bg-white/5"><b>{item}</b><p className="mt-2 text-sm text-slate-500">Configured through module manifests, REST endpoints, queue workers, and secure service abstractions.</p></div>)}</div></Card>
    <Card><h3 className="text-xl font-bold">Recommended setup</h3><ol className="mt-4 list-decimal space-y-3 pl-5 text-sm text-slate-500"><li>Connect preferred AI provider.</li><li>Enable vector database adapter.</li><li>Run knowledge training.</li><li>Review permission scopes.</li></ol></Card>
  </div>;
}
