import { config } from "./config";

export const environment = {
  production: true,
  apiEndpoint: config.API_URL,
  userPicture: {
    light: 'assets/imgs/user-light.png',
    dark: 'assets/imgs/user-dark.png'
  },
  defaultAvatar: 'assets/imgs/default-avatar.svg'
};
