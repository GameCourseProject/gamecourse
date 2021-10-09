import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-views-shell',
  template: '<router-outlet></router-outlet>'
})
export class ViewsShellComponent implements OnInit {

  constructor() { }

  ngOnInit(): void {
  }

}
