import {Component, OnInit} from '@angular/core';
import { NavigationCancel, NavigationEnd, NavigationStart, Router } from "@angular/router";

import { ErrorService } from "./_services/error.service";
import { ThemingService } from "./_services/theming/theming.service";

import { AlertType } from "./_services/alert.service";

import '@extensions/array.extensions';
import '@extensions/number.extensions';
import '@extensions/string.extensions';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html'
})
export class AppComponent implements OnInit {
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
  }

  async ngOnInit() {
    // Apply theming
    await this.themeService.loadTheme();
  }

  get AlertType(): typeof AlertType {
    return AlertType;
  }

  get ErrorService(): typeof ErrorService {
    return ErrorService;
  }

}
