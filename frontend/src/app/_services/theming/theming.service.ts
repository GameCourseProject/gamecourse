import { Injectable } from '@angular/core';
import {ApiHttpService} from "../api/api-http.service";
import {Theme} from "./themes-available";

@Injectable({
  providedIn: 'root'
})
export class ThemingService {

  private theme: Theme;

  constructor(private api: ApiHttpService) { }

  getTheme(): Theme {
    return this.theme;
  }

  async loadTheme(): Promise<void> {
    // If user already changed the theme, use it
    const themeStored = window.localStorage.getItem('theme');
    if (themeStored)  {
      this.theme = themeStored as Theme;
      this.apply(this.theme);
      return;
    }

    // If user has theme preference saved in database, use it
    const loggedUser = await this.api.getLoggedUser().toPromise();
    const themePreference = await this.api.getUserTheme(loggedUser.id).toPromise();
    if (themePreference) {
      this.theme = themePreference;
      this.apply(this.theme);
      return;
    }

    // Else user their browser preference
    this.theme = this.prefersDark() ? Theme.DARK : Theme.LIGHT;
    this.apply(this.theme);
  }

  async saveTheme(theme: Theme): Promise<void> {
    this.theme = theme;

    // Save to local storage
    window.localStorage.setItem('theme', theme);

    // Save preference in database
    const loggedUser = await this.api.getLoggedUser().toPromise();
    await this.api.setUserTheme(loggedUser.id, theme).toPromise();
  }

  private apply(theme: Theme) {
    const html = document.querySelector('html');
    html.setAttribute('data-theme', theme);
  }

  private prefersDark(): boolean {
    return !!window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }
}
