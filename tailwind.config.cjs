/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        heading: ['Outfit', 'sans-serif'],
        body: ['Outfit', 'sans-serif'],
      },
      fontSize: {
        display: ['clamp(3rem, 8vw, 6rem)', { lineHeight: '1.05', fontWeight: '900' }],
      },
      colors: {
        background: '#030712',
        surface: '#111827',
        primary: '#0ea5e9',
        secondary: '#8b5cf6',
        accent: '#f43f5e',
      },
    },
  },
  plugins: [],
};
