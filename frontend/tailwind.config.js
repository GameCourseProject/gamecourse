module.exports = {
  purge: {
    content: [
      './src/**/*.{html,ts,css,scss,sass,less,styl}',
    ]
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('daisyui')
  ],
  theme: {
    extend: {
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
          ...require('daisyui/src/colors/themes')['[data-theme=light]'],
          "primary": "#5e72e4",
          "secondary": "#f4f5f7",
          "accent": "#EA6FAC",
          "neutral": "#172b4d",
          "base-100": "#FFFFFF",
          "info": "#38BDF8",
          "success": "#34D399",
          "warning": "#FBBF24",
          "error": "#EF4444",
        },
      },
      {
        dark: {
          ...require('daisyui/src/colors/themes')['[data-theme=dark]'],
          "primary": "#5e72e4",
          "secondary": "#f4f5f7",
          "accent": "#EA6FAC",
          "neutral": "#172b4d",
          "base-100": "#2A303C",
          "info": "#38BDF8",
          "success": "#34D399",
          "warning": "#FBBF24",
          "error": "#EF4444",
        },
      },
      "cupcake", "bumblebee", "emerald", "corporate", "synthwave", "retro", "cyberpunk", "valentine", "halloween",
      "garden", "forest", "aqua", "lofi", "pastel", "fantasy", "wireframe", "black", "luxury", "dracula", "cmyk",
      "autumn", "business", "acid", "lemonade", "night", "coffee", "winter"
    ],
  },
};
