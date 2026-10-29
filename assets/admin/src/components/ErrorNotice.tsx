import { Component, type ErrorInfo, type ReactNode } from 'react';
import { AlertTriangle } from 'lucide-react';

export function errorMessage(error: unknown): string {
  return error instanceof Error ? error.message : 'Something went wrong.';
}

/** Inline error banner used by pages and forms for failed requests. */
export function ErrorNotice({ error, className = '' }: { error: unknown; className?: string }) {
  if (!error) {
    return null;
  }
  return <div role="alert" className={`flex items-start gap-2 rounded-2xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-500 ${className}`}>
    <AlertTriangle size={16} className="mt-0.5 shrink-0" /><span>{errorMessage(error)}</span>
  </div>;
}

/** Catches render errors so one broken page does not blank the whole admin app. */
export class ErrorBoundary extends Component<{ children: ReactNode }, { error: Error | null }> {
  state: { error: Error | null } = { error: null };

  static getDerivedStateFromError(error: Error) {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error('AICore admin error', error, info.componentStack);
  }

  render() {
    if (this.state.error) {
      return <div className="p-6"><ErrorNotice error={this.state.error} />
        <button className="mt-3 rounded-2xl border border-white/10 px-4 py-2 text-sm" onClick={() => this.setState({ error: null })}>Try again</button>
      </div>;
    }
    return this.props.children;
  }
}
