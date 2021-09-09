import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../_services/api/api-http.service";
import {Router} from "@angular/router";

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

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.initNavigations();
  }

  initNavigations(): void {
    this.mainNavigation = [
      {
        sref: '/main',
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
    this.api.logout().subscribe(
      isLoggedIn => {
        if (!isLoggedIn) this.router.navigate(['/login'])
      },
      error => {
        // TODO: alert
        console.error(error.message)
      }
    )
  }

}
