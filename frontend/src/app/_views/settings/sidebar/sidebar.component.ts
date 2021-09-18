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
      link: '/settings/global'
    },
    {
      name: 'Installed Modules',
      link: '/settings/modules'
    },{
      name: 'About',
      link: '/settings/about'
    }
  ];

  constructor() { }

  ngOnInit(): void {
  }

}
