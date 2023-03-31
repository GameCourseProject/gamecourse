import { config } from "./config";

export const environment = {
  production: false,
  url: config.URL,
  apiEndpoint: config.API_URL,
  userPicture: {
    light: 'assets/imgs/user-light.png',
    dark: 'assets/imgs/user-dark.png'
  },
  logoPicture: {
    light : 'assets/logo/logo_horz.png',
    dark : 'assets/logo/logo_horz_dark.png'
  },
  defaultAvatar: 'assets/imgs/default-avatar.svg',
  img: {
    light: 'assets/imgs/img-light.png',
    dark: 'assets/imgs/img-dark.png'
  },
};
