import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-settings-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit {

  navigation: { name: string, link: string }[] = [
    {
      name: 'Global',
      link: 'global'
    },
    {
      name: 'Installed Modules',
      link: 'modules'
    },{
      name: 'About',
      link: 'about'
    }
  ];

  constructor() { }

  ngOnInit(): void {
  }

}
