import { Injectable } from '@angular/core';
import {Theme} from "./themes-available";

@Injectable({
  providedIn: 'root'
})
export class ThemingService {

  private theme: Theme;

  constructor() { }

  getTheme(): Theme {
    // If user already changed the theme, use it
    const themeStored = window.localStorage.getItem('theme');
    if (themeStored) this.theme = themeStored as Theme;
    // TODO: get user preference from DB if set

    // else return their preferences
    else this.theme = this.prefersDark() ? Theme.DARK : Theme.LIGHT;

    return this.theme;
  }

  saveTheme(theme: Theme): void {
    this.theme = theme;
    window.localStorage.setItem('theme', theme);
    // TODO: save user preference to DB
  }

  apply(theme: Theme) {
    const html = document.querySelector('html');
    html.setAttribute('data-theme', theme);
  }

  private prefersDark(): boolean {
    return !!window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }
}
