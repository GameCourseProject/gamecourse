import {Component, HostListener, OnInit} from '@angular/core';
import {ActivatedRoute, NavigationEnd, Router} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../_services/api/api-http.service";
import {ErrorService} from "../../_services/error.service";
import {UpdateService, UpdateType} from "../../_services/update.service";

import {User} from "../../_domain/users/user";
import {Course} from "../../_domain/courses/course";
import {ImageManager} from "../../_utils/images/image-manager";
import {Page} from "../../_domain/pages & templates/page";

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit {

  user: User;
  photo: ImageManager;

  navigation: Navigation[];
  mainNavigation: Navigation[];
  courseNavigation: Navigation[];
  docsNavigation: Navigation[];

  isDocs: boolean;

  course: Course;
  activePages: Page[];

  constructor(
    private api: ApiHttpService,
    private router: Router,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService
  ) {
    this.photo = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    this.getUserInfo();
    this.updateManager.update.subscribe(type => {
      if (type === UpdateType.AVATAR) {
        this.getUserInfo();

      } else if (type === UpdateType.ACTIVE_PAGES) {
        this.activePages = null;
        this.initNavigations();
      }
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getUserInfo(): void {
    this.api.getLoggedUser()
      .subscribe(user => {
        this.user = user;
        this.photo.set(user.photoUrl);
        this.initNavigations();

        // Whenever URL changes
        this.router.events.subscribe(event => {
          if (event instanceof NavigationEnd) {
            this.initNavigations();
          }
        });
      })
  }


  /*** --------------------------------------------- ***/
  /*** ---------------- Navigation ----------------- ***/
  /*** --------------------------------------------- ***/

  async initNavigations(): Promise<void> {
    this.isDocs = this.router.url.includes('docs');
    const isInCourse = this.router.url.includes('courses/');

    if (this.isDocs) this.navigation = this.getDocsNavigation();
    else if (!isInCourse) this.navigation = this.getMainNavigation();
    else if (isInCourse) this.navigation = await this.getCourseNavigation();
    else this.navigation = [];

    if (!isInCourse) this.course = null;
  }

  getMainNavigation(): Navigation[] {
    this.mainNavigation = [];
    const pages: {[key: string]: Navigation} = {
      mainPage: {
        link: '/main',
        name: 'Main Page'
      },
      coursesPage: {
        link: '/courses',
        name: 'Courses'
      },
      usersPage: {
        link: '/users',
        name: 'Users'
      },
      settings: {
        link: '/settings',
        name: 'Settings',
        children: [
          {link: '/settings/global', name: 'Global'},
          {link: '/settings/modules', name: 'Modules'},
          {link: '/settings/about', name: 'About'}
        ]
      },
    }

    if (window.innerWidth >= 915)
      this.mainNavigation.push(pages.mainPage);

    if (window.innerWidth < 1000) {
      this.mainNavigation.push({
        link: null,
        name: 'Other Pages',
        children: []
      });

      const otherPages = this.mainNavigation[this.mainNavigation.length - 1];
      if (window.innerWidth < 915) otherPages.children.push(pages.mainPage)
      otherPages.children.push(pages.coursesPage);

      if (this.user.isAdmin)
        this.mainNavigation[this.mainNavigation.length - 1].children.push(pages.usersPage);

    } else {
      this.mainNavigation.push(pages.coursesPage);
      if (this.user.isAdmin)
        this.mainNavigation.push(pages.usersPage);
    }

    if (this.user.isAdmin)
      this.mainNavigation.push(pages.settings);

    return this.mainNavigation;
  }

  async getCourseNavigation(): Promise<Navigation[]> {
    if (!this.course || this.course.id !== this.getCourseIDFromURL() || !this.activePages) {
      const courseInfo = await this.getCourseInfo();
      const isAdminOrTeacher = this.user.isAdmin || await this.isCourseTeacher();
      this.course = courseInfo.course;
      this.activePages = courseInfo.activePages;
      this.courseNavigation = buildCourseNavigation(isAdminOrTeacher, this.course.id, this.activePages);
    }
    return this.courseNavigation;

    function buildCourseNavigation(isAdminOrTeacher: boolean, courseID: number, activePages: Page[]): Navigation[] {
      const path = '/courses/' + courseID + '/';

      const pages = activePages.map(page => {
        return {link: path + 'pages/' + page.id, name: page.name};
      });

      if (isAdminOrTeacher) {
        const fixed = [
          {link: path + 'users', name: 'Users'},
          {link: path + 'settings', name: 'Course Settings', children: [
              {link: path + 'settings/global', name: 'This Course'},
              {link: path + 'settings/roles', name: 'Roles'},
              {link: path + 'settings/modules', name: 'Modules'},
              {link: path + 'settings/rules', name: 'Rules'},
              {link: path + 'settings/views', name: 'Views'}
            ]}
        ];
        return pages.concat(fixed);
      }

      return pages;
    }
  }

  getDocsNavigation(): Navigation[] {
    const pages: {[key: string]: Navigation} = {
      viewsPage: {
        link: '/docs/views',
        name: 'Views',
      },
      functionsPage: {
        link: '/docs/functions',
        name: 'Functions',
      },
      modulesPage: {
        link: '/docs/modules',
        name: 'Modules',
      }
    };

    this.docsNavigation = [
      pages.viewsPage,
      pages.functionsPage,
      pages.modulesPage
    ];
    return this.docsNavigation;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Logout ------------------- ***/
  /*** --------------------------------------------- ***/

  logout(): void {
    this.api.logout().subscribe(
      isLoggedIn => {
        if (!isLoggedIn) this.router.navigate(['/login'])
      },
      error => ErrorService.set(error)
    )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  async getCourseInfo(): Promise<{course: Course, activePages: Page[]}> {
    const courseID = this.getCourseIDFromURL();
    if (courseID) return await this.api.getCourseWithInfo(courseID).toPromise();
    return null;
  }

  async isCourseTeacher(): Promise<boolean> {
    const courseID = this.getCourseIDFromURL();
    if (courseID) return await this.api.isCourseTeacher(courseID).toPromise()
    return null;
  }

  getCourseIDFromURL(): number {
    const urlParts = this.router.url.substr(1).split('/');
    if (urlParts.includes('courses') && urlParts.length >= 2) {
      return parseInt(urlParts[1]);
    } else return null;
  }


  @HostListener('window:resize', [])
  onWindowResize(): void {
    this.initNavigations();
  }

}

export interface Navigation {
  link: string,
  name: string,
  children?: Navigation[]
}
