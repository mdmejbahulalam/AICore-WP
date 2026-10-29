export type CommandResponse = {
  conversation_id: number;
  message: string;
  usage: { input: number; output: number };
};

export type ProviderInfo = {
  id: string;
  label: string;
  default_model: string;
  needs_key: boolean;
  has_key: boolean;
  key_hint: string;
  model: string;
  max_tokens: number;
  base_url: string;
};

export type SettingsResponse = { providers: ProviderInfo[]; default_provider: string };

export type ProviderUpdate = Partial<{ api_key: string; clear_key: boolean; model: string; max_tokens: number; base_url: string }>;

export type TestResult = { ok: true; model: string; latency_ms: number; reply: string } | { ok: false; error: string };

export type ModuleInfo = {
  id: string;
  name: string;
  description: string;
  enabled: boolean;
  requires: string[];
  required_by: string[];
  capabilities: string[];
};

export type UsageSummary = {
  days: number;
  totals: { calls: number; input_tokens: number; output_tokens: number; cost: number };
  by_model: { provider: string; model: string; calls: number; input_tokens: number; output_tokens: number; cost: number }[];
};

/** Error carrying the HTTP status and the message WordPress/the plugin returned (WP_Error JSON: {code, message}). */
export class ApiError extends Error {
  constructor(message: string, readonly status: number, readonly code?: string) {
    super(message);
    this.name = 'ApiError';
  }
}

const config = () => window.AICoreWP;

function stripTags(html: string): string {
  return html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  let response: Response;
  try {
    response = await fetch(`${config().restUrl}${path}`, {
      ...init,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': config().nonce,
        ...(init.headers ?? {})
      }
    });
  } catch {
    throw new ApiError('Could not reach the server. Check your connection and try again.', 0);
  }

  const text = await response.text();
  let body: unknown = null;
  try {
    body = text ? JSON.parse(text) : null;
  } catch {
    body = null;
  }

  if (!response.ok) {
    const error = (body ?? {}) as { message?: string; code?: string };
    const message = error.message ? stripTags(error.message) : `Request failed (${response.status}).`;
    throw new ApiError(message, response.status, error.code);
  }
  return body as T;
}

export function runCommand(prompt: string): Promise<CommandResponse> {
  return api<CommandResponse>('/command', { method: 'POST', body: JSON.stringify({ prompt }) });
}

export const getSettings = () => api<SettingsResponse>('/settings');
export const saveProvider = (id: string, update: ProviderUpdate) =>
  api<ProviderInfo>(`/settings/providers/${id}`, { method: 'POST', body: JSON.stringify(update) });
export const testProvider = (id: string) => api<TestResult>(`/settings/providers/${id}/test`, { method: 'POST' });
export const setDefaultProvider = (provider: string) =>
  api<{ default_provider: string }>('/settings/default-provider', { method: 'POST', body: JSON.stringify({ provider }) });
export const getModules = () => api<ModuleInfo[]>('/modules');
export const toggleModule = (id: string, enabled: boolean) =>
  api<{ id: string; enabled: boolean }>(`/modules/${id}`, { method: 'POST', body: JSON.stringify({ enabled }) });
export const getUsage = (days = 30) => api<UsageSummary>(`/usage/summary?days=${days}`);
