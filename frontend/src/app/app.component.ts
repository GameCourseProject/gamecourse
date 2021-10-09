import { Component } from '@angular/core';
import {ErrorService} from "./_services/error.service";
import {
  NavigationCancel,
  NavigationEnd,
  NavigationStart,
  Router
} from "@angular/router";

import '@extensions/string';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html'
})
export class AppComponent {
  title = 'gamecourse-v2';
  loading: boolean = true;

  constructor(private router: Router) {
    // Wait for guard checks
    this.router.events.subscribe(event => {
      if (event instanceof NavigationStart)
        this.loading = true;

      if (event instanceof NavigationEnd || event instanceof NavigationCancel)
        this.loading = false;
    });
  }

  hasNavbar(): boolean {
    return this.router.url !== '/login' && this.router.url !== '/setup';
  }

  hasFooter(): boolean {
    const urlParts = this.router.url.substr(1).split('/');
    return urlParts.includes('courses') && urlParts.length >= 3;
  }

  hasError(): boolean {
    return !!ErrorService.error;
  }

  getError(): string {
    return ErrorService.error;
  }

  closeError(): void {
    if (ErrorService.callback) ErrorService.callback();
    ErrorService.clear();
  }

}