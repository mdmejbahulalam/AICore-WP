import type { Config } from 'tailwindcss';

export default {
  content: ['./assets/admin/src/**/*.{ts,tsx}'],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        aicore: {
          50: '#eef8ff',
          500: '#3485ff',
          700: '#2456d8',
          950: '#07172f'
        }
      },
      boxShadow: {
        glow: '0 0 80px rgba(52, 133, 255, 0.28)'
      }
    }
  },
  plugins: []
} satisfies Config;
