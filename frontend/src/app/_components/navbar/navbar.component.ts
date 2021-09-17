import {Component, HostListener, Input, OnInit} from '@angular/core';
import {ApiHttpService} from "../../_services/api/api-http.service";
import {Router} from "@angular/router";
import {User} from "../../_domain/User";
import {Observable} from "rxjs";
import {DomSanitizer} from "@angular/platform-browser";
import {ImageManager} from "../../_utils/image-manager";
import {ErrorService} from "../../_services/error.service";

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit {

  @Input() updatePhoto?: Observable<void>;
  @Input() docs?: boolean = false;     // Whether or not it is docs navbar

  user: User;
  photo: ImageManager;
  visible = true;

  mainNavigation = [];

  constructor(
    private api: ApiHttpService,
    private router: Router,
    private sanitizer: DomSanitizer
  ) {
    this.photo = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    if (!this.docs) this.getUserInfo();
    else this.initDocsNavigation();

    if (this.updatePhoto)
      this.updatePhoto.subscribe(() => this.getUserInfo());
  }

  getUserInfo(): void {
    this.api.getLoggedUser()
      .subscribe(user => {
        this.user = user;
        this.photo.set(user.photoUrl);
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

  initDocsNavigation(): void {
    const pages = {
      viewsPage: {
        sref: '/docs/views',
        text: 'Views',
      },
      functionsPage: {
        sref: '/docs/functions',
        text: 'Functions',
      },
      modulesPage: {
        sref: '/docs/modules',
        text: 'Modules',
      }
    };
    this.mainNavigation = [
      pages.viewsPage,
      pages.functionsPage,
      pages.modulesPage
    ]
  }

  logout(): void {
    this.api.logout().subscribe(
      isLoggedIn => {
        if (!isLoggedIn) this.router.navigate(['/login'])
      },
      error => ErrorService.set(error)
    )
  }

  @HostListener('window:resize', [])
  onWindowResize(): void {
    this.initNavigations();
  }

}
