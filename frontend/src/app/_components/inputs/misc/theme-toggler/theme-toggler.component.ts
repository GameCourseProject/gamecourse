import {Component, OnInit} from '@angular/core';
import {ThemingService} from "../../../../_services/theming/theming.service";
import {Theme} from "../../../../_services/theming/themes-available";

@Component({
  selector: 'app-theme-toggler',
  templateUrl: './theme-toggler.component.html',
  styleUrls: ['./theme-toggler.component.scss']
})
export class ThemeTogglerComponent implements OnInit {

  theme: Theme;

  constructor(public themeService: ThemingService) {
    this.theme = themeService.getTheme();
  }

  ngOnInit(): void {
  }

  toggleTheme(): void {
    const theme = this.theme === Theme.LIGHT ? Theme.DARK : Theme.LIGHT;
    this.theme = theme;

    this.themeService.saveTheme(theme);
    this.themeService.apply(theme);
  }

  get Theme(): typeof Theme {
    return Theme;
  }

}
