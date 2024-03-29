import { Component, Input, OnInit } from '@angular/core';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html'
})
export class HeaderComponent implements OnInit {

  @Input() title: string;
  @Input() color?: string;
  @Input() icon?: string;

  @Input() subHeader?: boolean;
  @Input() classList?: string;

  @Input() loading?: boolean;

  constructor() { }

  ngOnInit(): void {
  }

}
