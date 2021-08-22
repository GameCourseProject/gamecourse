import { Component } from '@angular/core';
import {Router} from "@angular/router";

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html'
})
export class AppComponent {
  title = 'gamecourse-v2';

  noNavbar = ['/login', '/setup'];

  constructor(public router: Router) {
  }

  hasNavbar(url: string): boolean {
    return !this.noNavbar.includes(url);
  }
}
