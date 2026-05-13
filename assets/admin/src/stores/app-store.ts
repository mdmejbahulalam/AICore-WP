import { create } from 'zustand';

export type ThemeMode = 'light' | 'dark';

type AppState = {
  theme: ThemeMode;
  activePage: string;
  notifications: string[];
  setTheme: (theme: ThemeMode) => void;
  setActivePage: (page: string) => void;
  notify: (message: string) => void;
};

export const useAppStore = create<AppState>((set) => ({
  theme: 'dark',
  activePage: 'Dashboard',
  notifications: ['Setup wizard ready', 'MCP endpoint secured'],
  setTheme: (theme) => set({ theme }),
  setActivePage: (activePage) => set({ activePage }),
  notify: (message) => set((state) => ({ notifications: [message, ...state.notifications].slice(0, 8) }))
}));
