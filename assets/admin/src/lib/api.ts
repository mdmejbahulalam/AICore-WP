export type CommandResponse = {
  conversation_id: number;
  message: string;
  usage: { input: number; output: number };
};

const config = () => window.AICoreWP;

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`${config().restUrl}${path}`, {
    ...init,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': config().nonce,
      ...(init.headers ?? {})
    }
  });
  if (!response.ok) {
    throw new Error(`AICore API request failed: ${response.status}`);
  }
  return (await response.json()) as T;
}

export function runCommand(prompt: string): Promise<CommandResponse> {
  return api<CommandResponse>('/command', { method: 'POST', body: JSON.stringify({ prompt }) });
}
