import {Component, OnInit} from '@angular/core';
import {ThemingService} from "../../../../_services/theming/theming.service";
import {Theme} from "../../../../_services/theming/themes-available";

@Component({
  selector: 'app-theme-toggler',
  templateUrl: './theme-toggler.component.html'
})
export class ThemeTogglerComponent implements OnInit {

  theme: Theme;

  constructor(public themeService: ThemingService) {
    this.theme = themeService.getTheme();
  }

  ngOnInit(): void {
  }

  async toggleTheme(): Promise<void> {
    const theme = this.theme === Theme.LIGHT ? Theme.DARK : Theme.LIGHT;
    this.theme = theme;

    await this.themeService.saveTheme(theme);
    await this.themeService.loadTheme();
  }

  get Theme(): typeof Theme {
    return Theme;
  }

}
