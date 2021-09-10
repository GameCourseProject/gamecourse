import {Component, HostListener, OnInit} from '@angular/core';
import {ApiHttpService} from "../../_services/api/api-http.service";
import {Router} from "@angular/router";
import {User} from "../../_domain/User";

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit {

  user: User;

  mainNavigation = [];

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.getUserInfo();
  }

  getUserInfo(): void {
    this.api.getLoggedUser()
      .subscribe(user => {
        this.user = user;
        this.initNavigations();
      })
  }

  initNavigations(): void {
    const pages = {
      mainPage: {
        sref: '/main',
          image: 'images/leaderboard.svg',
        text: 'Main Page',
        class: ''
      },

      coursesPage: {
        sref: '/courses',
        image: 'images/leaderboard.svg',
        text: 'Courses',
        class: ''
      },

      usersPage: {
        sref: '/users',
        image: 'images/leaderboard.svg',
        text: 'Users',
        class: ''
      },

      settings: {
        sref: '/settings',
        image: 'images/gear.svg',
        text: 'Settings',
        class:'dropdown',
        children: [
          {sref: '/settings/about', text: 'About'},
          {sref: '/settings/global', text: 'Global'},
          {sref: '/settings/modules', text: 'Modules'}
        ]
      }
    };
    this.mainNavigation = [];

    if (window.innerWidth < 915) {
      this.mainNavigation.push({
        text: 'Other Pages',
        class:'dropdown',
        children: [
          pages.mainPage,
          pages.coursesPage
        ]
      });

      if (this.user.isAdmin)
        this.mainNavigation[this.mainNavigation.length - 1].children.push(pages.usersPage);

    } else if (window.innerWidth < 1000) {
      this.mainNavigation.push(pages.mainPage);
      this.mainNavigation.push({
        text: 'Other Pages',
        class:'dropdown',
        children: [
          pages.coursesPage
        ]
      });

      if (this.user.isAdmin)
        this.mainNavigation[this.mainNavigation.length - 1].children.push(pages.usersPage);

    } else {
      this.mainNavigation.push(pages.mainPage);
      this.mainNavigation.push(pages.coursesPage);
      if (this.user.isAdmin) this.mainNavigation.push(pages.usersPage);
    }

    if (this.user.isAdmin)
      this.mainNavigation.push(pages.settings);
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

  @HostListener('window:resize', [])
  onWindowResize(): void {
    this.initNavigations();
  }

}
