import {Component, Input, OnInit} from '@angular/core';

@Component({
  selector: 'app-docs-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit {

  @Input() selected: string;
  @Input() tabs: {name: string, link: string}[];

  constructor() { }

  ngOnInit(): void {
  }

}
