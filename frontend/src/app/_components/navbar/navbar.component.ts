import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit {

  user = {username: 'ist181583'}; // TODO: get actual user
  isAdmin = true;

  mainNavigation = [];
  settingsNavigation = [];

  constructor() { }

  ngOnInit(): void {
    this.initNavigations();
  }

  initNavigations(): void {
    this.mainNavigation = [
      {
        sref: '',
        image: 'images/leaderboard.svg',
        text: 'Main Page',
        class: ''
      },
      {
        sref: '/courses',
        image: 'images/leaderboard.svg',
        text: 'Courses',
        class: ''
      }
    ];

    if (this.isAdmin) {
      this.mainNavigation.push({
        sref: '/users',
        image: 'images/leaderboard.svg',
        text: 'Users',
        class: ''
      });

      this.mainNavigation.push({
        sref: '/settings',
        image: 'images/gear.svg',
        text: 'Settings',
        class:'dropdown',
        children:'true'
      });

      this.settingsNavigation = [
        {sref: '/settings/about', text: 'About'},
        {sref: '/settings/global', text: 'Global'},
        {sref: '/settings/modules', text: 'Modules'}
      ];
    }
  }

  logout(): void {
    // TODO
  }

}
