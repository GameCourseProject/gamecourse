import {Injectable} from '@angular/core';

import {Theme} from "./themes-available";

import {ApiHttpService} from "../api/api-http.service";
import {UpdateService, UpdateType} from "../update.service";

@Injectable({
  providedIn: 'root'
})
export class ThemingService {

  private theme: Theme;

  constructor(
    private api: ApiHttpService,
    private updateManager: UpdateService,
  ) { }

  getTheme(): Theme {
    return this.theme;
  }

  async loadTheme(courseId? : number): Promise<void> {

    // If course has theme preference in database, use it
    if (courseId) {
      const course =  await this.api.getCourseById(courseId).toPromise();
      if (course && course.theme) {
        this.theme = course.theme as Theme;
        this.apply(this.theme);
        return;
      }
    }

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
    this.updateManager.triggerUpdate(UpdateType.THEME);
  }

  private prefersDark(): boolean {
    return !!window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  colorToHexa(color: string) : string {
      switch (color) {
        case "primary" : return "#5E72E4";
        case "secondary" : return "#EA6FAC";
        case "accent" : return "#1EA896";
        case "info" : return "#38BFF8";
        case "success" : return "#36D399";
        case "warning" : return "#FBBD23";
        case "error" : return "#EF6060";
      }
    return "";
  }

  hexaToColor(color: string) : string {
      switch (color) {
        case "#5E72E4" : return "primary";
        case "#EA6FAC" : return "secondary";
        case "#1EA896" : return "accent";
        case "#38BFF8" : return "info";
        case "#36D399" : return "success";
        case "#FBBD23" : return "warning";
        case "#EF6060" : return "error";
      }
    return "";
  }
}
