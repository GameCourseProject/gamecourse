const defaultTheme = require('tailwindcss/defaultTheme');
const lightThemeColors = require('daisyui/src/colors/themes')['[data-theme=light]'];
const darkThemeColors = require('daisyui/src/colors/themes')['[data-theme=dark]'];

module.exports = {
  mode: 'jit',
  content: [
    './src/**/*.{html,ts}',
    './src/index.html'
  ],
  plugins: [
    require('@tailwindcss/typography'),
    require('@tailwindcss/line-clamp'),
    require('tailwind-scrollbar')({ nocompatible: true }),
    require('daisyui')
  ],
  theme: {
    extend: {
      fontFamily: {
        'sans': ['Inter', ...defaultTheme.fontFamily.sans]
      },
      colors: {
        'info-focus': '#09AEf4',
        'success-focus': '#2BC48C',
        'warning-focus': '#E8A504',
        'error-focus': '#ED4949',
        'fenix': {
          light: '#00a7ee',
          DEFAULT: '#0095D5',
          dark: '#0083bb'
        },
        'google': {
          light: '#df584d',
          DEFAULT: '#DB4437',
          dark: '#d33426'
        },
        'facebook': {
          light: '#5074be',
          DEFAULT: '#4267B2',
          dark: '#3b5c9f'
        },
        'linkedin': {
          light: '#0088ce',
          DEFAULT: '#0077B5',
          dark: '#00669b'
        }
      },
      keyframes: {
        pulse: {
          '0%, 100%': { opacity: 0.9 },
          '50%': { opacity: 0.3 },
        }
      }
    }
  },
  daisyui: {
    themes: [
      {
        light: {
          ...lightThemeColors,
          'primary': '#5E72E4',
          'primary-focus': '#485FE0',
          'secondary': '#EA6FAC',
          'secondary-focus': '#E7599F',
          'accent': '#1EA896',
          'accent-focus': '#1a9283',
          'accent-content': lightThemeColors['base-100'],
          'neutral': '#172B4D',
          'neutral-focus': '#234174',
          'base-200': '#F9FAFB',
          'base-300': '#F4F5F7',
          'info': '#38BFF8',
          'success': '#36D399',
          'warning': '#FBB50A',
          'error': '#EF6060',
          'error-content': '#420000',
        },
      },
      {
        dark: {
          ...darkThemeColors,
          'primary': '#5E72E4',
          'primary-focus': '#485FE0',
          'secondary': '#EA6FAC',
          'secondary-focus': '#E7599F',
          'accent': '#1EA896',
          'accent-focus': '#1a9283',
          'accent-content': '#FFFFFF',
          'neutral': '#E5E7EB',
          'neutral-focus': '#C8CCD5',
          'neutral-content': darkThemeColors['base-100'],
          'base-content': '#BFC6D3',
          'info': '#38BFF8',
          'success': '#36D399',
          'warning': '#FBBD23',
          'error': '#EF6060',
          'error-content': '#420000',
        },
      },
      'cupcake', 'bumblebee', 'emerald', 'corporate', 'synthwave', 'retro', 'cyberpunk', 'valentine', 'halloween',
      'garden', 'forest', 'aqua', 'lofi', 'pastel', 'fantasy', 'wireframe', 'black', 'luxury', 'dracula', 'cmyk',
      'autumn', 'business', 'acid', 'lemonade', 'night', 'coffee', 'winter'
    ],
  },
};
