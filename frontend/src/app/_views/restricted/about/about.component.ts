import {Component, OnInit} from '@angular/core';
import { Theme } from 'src/app/_services/theming/themes-available';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'app-about',
  templateUrl: './about.component.html'
})
export class AboutComponent implements OnInit {

  constructor() { }

  ngOnInit(): void {
  }

  
  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getDefaultLogoImg(): string {
    const html = document.querySelector('html');
    const theme = html.getAttribute('data-theme');

    switch (theme) {
      case Theme.DARK:
      case Theme.SYNTHWAVE:
      case Theme.DRACULA:
      case Theme.HALLOWEEN:
      case Theme.FOREST:
      case Theme.BLACK:
      case Theme.LUXURY:
      case Theme.NIGHT:
      case Theme.COFFEE:
      case Theme.BUSINESS:
        return environment.logoPicture.dark;
      default:
        return environment.logoPicture.light;
    }
  }

}
