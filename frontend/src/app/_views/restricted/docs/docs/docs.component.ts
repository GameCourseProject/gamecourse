import { Component, OnInit } from '@angular/core';
import {Router} from "@angular/router";

@Component({
  selector: 'app-docs',
  templateUrl: './docs.component.html',
  styleUrls: ['./docs.component.scss']
})
export class DocsComponent implements OnInit {

  readonly TABS: {[route: string]: {name: string, link: string}[]} = {
    'views': [
      { name: 'Views', link: 'views' },
      { name: 'View Part Types', link: 'part-types' },
      { name: 'Expression Language', link: 'expression-language' },
      { name: 'Part Configuration', link: 'part-configuration' },
    ],
    'functions': [
      { name: 'Object And Collection Manipulation', link: '' },
    ],
    'modules': [
      { name: 'Module Creation', link: 'creation' },
      { name: 'Module Initialization', link: 'initialization' },
      { name: 'Module Configuration', link: 'configuration' },
      { name: 'Resources & Interaction', link: 'resources' },
      { name: 'Accessible Data', link: 'data' },
    ]
  };

  constructor(
    private router: Router
  ) { }

  ngOnInit(): void {
  }

  get path(): string {
    const split = this.router.url.substr(1).split('/');
    return split[1];
  }

}
