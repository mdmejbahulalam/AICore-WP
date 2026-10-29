import { create } from 'zustand';

export type ThemeMode = 'light' | 'dark';

const THEME_KEY = 'aicore-wp-theme';

// Light (white) is the default; the choice is remembered per browser when storage is available.
function initialTheme(): ThemeMode {
  try {
    return window.localStorage.getItem(THEME_KEY) === 'dark' ? 'dark' : 'light';
  } catch {
    return 'light';
  }
}

type AppState = {
  theme: ThemeMode;
  activePage: string;
  notifications: string[];
  setTheme: (theme: ThemeMode) => void;
  setActivePage: (page: string) => void;
  notify: (message: string) => void;
};

export const useAppStore = create<AppState>((set) => ({
  theme: initialTheme(),
  activePage: 'Dashboard',
  notifications: ['Setup wizard ready', 'MCP endpoint secured'],
  setTheme: (theme) => {
    try {
      window.localStorage.setItem(THEME_KEY, theme);
    } catch {
      // Storage can be unavailable (private mode); the theme still changes for this session.
    }
    set({ theme });
  },
  setActivePage: (activePage) => set({ activePage }),
  notify: (message) => set((state) => ({ notifications: [message, ...state.notifications].slice(0, 8) }))
}));
