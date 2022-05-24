import { Component } from '@angular/core';
import {Router} from "@angular/router";

@Component({
  selector: 'app-restricted',
  templateUrl: './restricted.component.html'
})
export class RestrictedComponent {

  constructor(private router: Router) { }

  hasFooter(): boolean {
    const urlParts = this.router.url.substr(1).split('/');
    return urlParts.includes('courses') && urlParts.length >= 2;
  }
}
