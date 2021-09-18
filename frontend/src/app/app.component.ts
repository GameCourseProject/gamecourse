import { Component } from '@angular/core';
import {ErrorService} from "./_services/error.service";
import {Router} from "@angular/router";

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html'
})
export class AppComponent {
  title = 'gamecourse-v2';

  constructor(private router: Router) {
  }

  showFooter(): boolean {
    const urlParts = this.router.url.substr(1).split('/');
    return urlParts.includes('courses') && urlParts.length >= 3;
  }

  hasError(): boolean {
    return !!ErrorService.get();
  }

  getError(): string {
    return ErrorService.get();
  }

  clearError(): void {
    ErrorService.clear();
  }


}
