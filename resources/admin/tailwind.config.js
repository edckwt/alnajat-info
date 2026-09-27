/** @type {import('tailwindcss').Config} */

/*
 * كل الألوان معرّفة كمتغيرات CSS (RGB triplets) داخل src/css/app.css
 * حتى يمكن تبديل الثيم بالكامل وقت التشغيل بتغيير data-theme على <html>
 * مع الحفاظ على دعم opacity في Tailwind:  bg-primary-500/20
 */
const withVar = (name) => ({ opacityValue }) =>
  opacityValue === undefined
    ? `rgb(var(${name}))`
    : `rgb(var(${name}) / ${opacityValue})`;

const scale = (prefix) =>
  [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].reduce(
    (acc, step) => ({ ...acc, [step]: withVar(`--c-${prefix}-${step}`) }),
    { DEFAULT: withVar(`--c-${prefix}-600`) }
  );

module.exports = {
  darkMode: 'class',
  content: [
    '../views/admin/**/*.blade.php',
    '../views/components/admin/**/*.blade.php',
    '../../app/Http/Controllers/Admin/**/*.php',
    '../../public/admin/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        primary: scale('primary'),
        success: scale('success'),
        warning: scale('warning'),
        danger: scale('danger'),
        info: scale('info'),

        /* ألوان الأسطح — تتغيّر مع الوضع الليلي */
        canvas: withVar('--c-canvas'),
        surface: withVar('--c-surface'),
        'surface-2': withVar('--c-surface-2'),
        line: withVar('--c-line'),
        ink: withVar('--c-ink'),
        muted: withVar('--c-muted'),
        faint: withVar('--c-faint'),

        /* ألوان القائمة الجانبية — مشتقّة من الثيم */
        sidebar: {
          DEFAULT: withVar('--c-sidebar'),
          soft: withVar('--c-sidebar-soft'),
          hover: withVar('--c-sidebar-hover'),
          active: withVar('--c-sidebar-active'),
          'active-ink': withVar('--c-sidebar-active-ink'),
          ink: withVar('--c-sidebar-ink'),
          muted: withVar('--c-sidebar-muted'),
          line: withVar('--c-sidebar-line'),
        },
      },
      opacity: {
        4: '0.04', 6: '0.06', 8: '0.08', 12: '0.12', 15: '0.15', 18: '0.18', 35: '0.35', 65: '0.65',
      },
      fontFamily: {
        sans: ['Inter', 'Cairo', 'system-ui', 'sans-serif'],
        ar: ['Cairo', 'Tajawal', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        xl: '0.75rem',
        '2xl': '1rem',
        '3xl': '1.5rem',
      },
      boxShadow: {
        card: '0 3px 20px rgb(var(--c-shadow) / 0.06)',
        'card-hover': '0 12px 30px rgb(var(--c-shadow) / 0.12)',
        pop: '0 10px 40px rgb(var(--c-shadow) / 0.18)',
        inner1: 'inset 0 1px 0 rgb(255 255 255 / 0.06)',
      },
      spacing: {
        sidebar: '17rem',
        'sidebar-mini': '5rem',
      },
      zIndex: {
        header: '40',
        sidebar: '50',
        overlay: '60',
        modal: '70',
        toast: '80',
      },
      keyframes: {
        'fade-in': { from: { opacity: 0 }, to: { opacity: 1 } },
        'pop-in': {
          from: { opacity: 0, transform: 'translateY(-6px) scale(.97)' },
          to: { opacity: 1, transform: 'translateY(0) scale(1)' },
        },
        'slide-in-start': {
          from: { opacity: 0, transform: 'translateX(var(--slide-from, 1rem))' },
          to: { opacity: 1, transform: 'translateX(0)' },
        },
        'slide-up': {
          from: { opacity: 0, transform: 'translateY(14px)' },
          to: { opacity: 1, transform: 'translateY(0)' },
        },
        shimmer: { '100%': { transform: 'translateX(100%)' } },
        'bar-grow': { from: { transform: 'scaleY(.2)' }, to: { transform: 'scaleY(1)' } },
      },
      animation: {
        'fade-in': 'fade-in .2s ease-out both',
        'pop-in': 'pop-in .16s cubic-bezier(.2,.9,.3,1.2) both',
        'slide-in-start': 'slide-in-start .3s ease-out both',
        'slide-up': 'slide-up .35s cubic-bezier(.2,.8,.3,1) both',
        shimmer: 'shimmer 1.6s infinite',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms')({ strategy: 'class' }),
    require('@tailwindcss/typography'),
  ],
};
