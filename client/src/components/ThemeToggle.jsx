import { useTheme } from '../context/ThemeContext';

export default function ThemeToggle({ className = '' }) {
  const { theme, toggleTheme } = useTheme();
  const isDark = theme === 'dark';
  return (
    <button
      type="button"
      onClick={toggleTheme}
      title={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
      aria-label="Toggle theme"
      className={`flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#004242] shadow-[0_10px_25px_rgba(0,66,66,0.08)] transition-colors hover:bg-[#dff4f2] dark:bg-[#1e2530] dark:text-[#8bd0d1] dark:hover:bg-[#27303d] ${className}`}
    >
      <span className="material-symbols-outlined text-[20px]">
        {isDark ? 'light_mode' : 'dark_mode'}
      </span>
    </button>
  );
}
