export type CommandResponse = {
  conversation_id: number;
  message: string;
  usage: { input: number; output: number };
};

export type ProviderInfo = {
  id: string;
  label: string;
  default_model: string;
  default_embedding_model: string;
  needs_key: boolean;
  has_key: boolean;
  key_hint: string;
  model: string;
  embedding_model: string;
  max_tokens: number;
  base_url: string;
};

export type SettingsResponse = { providers: ProviderInfo[]; default_provider: string };

export type ProviderUpdate = Partial<{ api_key: string; clear_key: boolean; model: string; embedding_model: string; max_tokens: number; base_url: string }>;

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

export type Conversation = { id: number; title: string; provider: string; model: string; updated_at: string };

export type ChatMessage = { id: number; role: 'user' | 'assistant'; content: string; provider: string; model: string; tokens: number; created_at: string };

export type ChatOptions = { provider?: string; model?: string };

export type Memory = {
  id: number;
  scope: 'site' | 'user';
  key: string;
  value: string;
  confidence: number;
  expires_at: string | null;
  updated_at: string;
  embedded: boolean;
};

export type MemoryInput = Partial<{ scope: 'site' | 'user'; key: string; value: string; confidence: number; expires_at: string }>;

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

function parseJson(text: string): unknown {
  try {
    return text ? JSON.parse(text) : null;
  } catch {
    return null;
  }
}

function toApiError(status: number, body: unknown): ApiError {
  const error = (body ?? {}) as { message?: string; code?: string };
  const message = error.message ? stripTags(error.message) : `Request failed (${status}).`;
  return new ApiError(message, status, error.code);
}

const NETWORK_ERROR = 'Could not reach the server. Check your connection and try again.';

function request(path: string, init: RequestInit): Promise<Response> {
  return fetch(`${config().restUrl}${path}`, {
    ...init,
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config().nonce, ...(init.headers ?? {}) }
  });
}

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  let response: Response;
  try {
    response = await request(path, init);
  } catch {
    throw new ApiError(NETWORK_ERROR, 0);
  }
  const body = parseJson(await response.text());
  if (!response.ok) {
    throw toApiError(response.status, body);
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

export const getConversations = () => api<Conversation[]>('/chat/conversations');
export const createConversation = (options: ChatOptions = {}) =>
  api<Conversation>('/chat/conversations', { method: 'POST', body: JSON.stringify({ title: '', ...options }) });
export const getMessages = (id: number) => api<{ messages: ChatMessage[] }>(`/chat/conversations/${id}`).then((r) => r.messages);
export const renameConversation = (id: number, title: string) =>
  api<{ id: number; title: string }>(`/chat/conversations/${id}`, { method: 'POST', body: JSON.stringify({ title }) });
export const deleteConversation = (id: number) => api<{ deleted: boolean }>(`/chat/conversations/${id}`, { method: 'DELETE' });

export const getMemories = () => api<Memory[]>('/memories');
export const createMemory = (input: MemoryInput) => api<Memory>('/memories', { method: 'POST', body: JSON.stringify(input) });
export const updateMemory = (id: number, input: MemoryInput) => api<Memory>(`/memories/${id}`, { method: 'POST', body: JSON.stringify(input) });
export const deleteMemory = (id: number) => api<{ deleted: boolean }>(`/memories/${id}`, { method: 'DELETE' });
export const reindexMemories = () => api<{ embedded: number; failed: number; error: string }>('/memories/reindex', { method: 'POST' });

export type StreamDone = { message: { id: number; content: string; model: string; provider: string; tokens: number }; memories_used: number };

/**
 * Sends a chat message and reads the reply as Server-Sent Events, calling onDelta for each piece of text.
 * Resolves with the final event; rejects with ApiError (including provider errors reported inside the stream).
 */
export async function streamMessage(
  conversationId: number,
  body: ChatOptions & { content: string },
  onDelta: (text: string) => void,
  signal?: AbortSignal
): Promise<StreamDone> {
  let response: Response;
  try {
    response = await request(`/chat/conversations/${conversationId}/stream`, { method: 'POST', body: JSON.stringify(body), signal });
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      throw error;
    }
    throw new ApiError(NETWORK_ERROR, 0);
  }
  if (!response.ok || !response.body) {
    throw toApiError(response.status, parseJson(await response.text()));
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';
  let done: StreamDone | null = null;

  const handle = (raw: string) => {
    const event = /^event: (.*)$/m.exec(raw)?.[1];
    const data = parseJson(/^data: (.*)$/m.exec(raw)?.[1] ?? '') as Record<string, unknown> | null;
    if (!event || !data) {
      return;
    }
    if (event === 'delta') {
      onDelta(String(data.text ?? ''));
    } else if (event === 'done') {
      done = data as unknown as StreamDone;
    } else if (event === 'error') {
      throw new ApiError(String(data.message ?? 'The AI provider returned an error.'), 502, 'aicore_provider_error');
    }
  };

  for (;;) {
    const { done: finished, value } = await reader.read();
    if (finished) {
      break;
    }
    buffer += decoder.decode(value, { stream: true });
    let end: number;
    while ((end = buffer.indexOf('\n\n')) >= 0) {
      handle(buffer.slice(0, end));
      buffer = buffer.slice(end + 2);
    }
  }
  if (!done) {
    throw new ApiError('The reply was interrupted before it finished.', 0);
  }
  return done;
}
