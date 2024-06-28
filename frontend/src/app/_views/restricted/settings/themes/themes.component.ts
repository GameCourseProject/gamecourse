import { Component, OnInit } from '@angular/core';
import { Theme } from 'src/app/_services/theming/themes-available';

@Component({
  selector: 'app-themes',
  templateUrl: './themes.component.html'
})
export class ThemesComponent implements OnInit {

  loading = true;

  themes: string[];
  filteredThemes: string[];

  constructor(
  ) { }

  async ngOnInit(): Promise<void> {
    this.themes = Object.values(Theme);
    this.filteredThemes = this.themes;
    this.loading = false;
  }

}
