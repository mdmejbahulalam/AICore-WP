declare module '*.css';

export type AICoreConfig = {
  restUrl: string;
  nonce: string;
  site: string;
  user: string;
};

declare global {
  interface Window {
    AICoreWP: AICoreConfig;
  }
}
