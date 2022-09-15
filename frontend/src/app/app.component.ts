import { Component } from '@angular/core';
import { NavigationCancel, NavigationEnd, NavigationStart, Router } from "@angular/router";

import { ErrorService } from "./_services/error.service";
import { ThemingService } from "./_services/theming/theming.service";

import '@extensions/array.extensions';
import '@extensions/number.extensions';
import '@extensions/string.extensions';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html'
})
export class AppComponent {
  loading: boolean = true;

  constructor(
    private router: Router,
    private themeService: ThemingService
  ) {

    // Wait for guard checks
    this.router.events.subscribe(event => {
      if (event instanceof NavigationStart)
        this.loading = true;

      if (event instanceof NavigationEnd || event instanceof NavigationCancel)
        this.loading = false;
    });

    // Apply theming
    const theme = themeService.getTheme();
    themeService.apply(theme);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Errors ------------------- ***/
  /*** --------------------------------------------- ***/

  hasError(): boolean {
    return !!ErrorService.error.full;
  }

  getError(): {message: string, stack: string, full: string} {
    return ErrorService.error;
  }

  closeError(): void {
    if (ErrorService.callback) ErrorService.callback();
    ErrorService.clear();
  }

}
