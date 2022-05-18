import {Component, HostListener, OnInit} from '@angular/core';
import {ActivatedRoute, NavigationEnd, Router} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../_services/api/api-endpoints.service";
import {UpdateService, UpdateType} from "../../_services/update.service";

import {User} from "../../_domain/users/user";
import {Course} from "../../_domain/courses/course";
import {ResourceManager} from "../../_utils/resources/resource-manager";
import {Page} from "../../_domain/pages & templates/page";

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit {

  user: User;
  photo: ResourceManager;

  navigation: Navigation[];
  mainNavigation: Navigation[];
  courseNavigation: Navigation[];
  docsNavigation: Navigation[];

  isDocs: boolean;

  course: Course;
  activePages: Page[];

  // FIXME: navbar space should be configurable in modules
  hasTokensEnabled: boolean;
  isStudent: boolean;
  tokens: number;
  tokensImg: string = ApiEndpointsService.API_ENDPOINT + '/modules/' + ApiHttpService.VIRTUAL_CURRENCY + '/imgs/token.png'; // FIXME: should be configurable

  constructor(
    private api: ApiHttpService,
    private router: Router,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService
  ) {
    this.photo = new ResourceManager(sanitizer);
  }

  async ngOnInit(): Promise<void> {
    // Get logged user information
    await this.getLoggedUser();

    // Init navigations
    await this.initNavigations();

    // Whenever URL changes
    this.router.events.subscribe(event => {
      if (event instanceof NavigationEnd) {
        this.initNavigations();
      }
    });

    // Whenever updates are received
    this.updateManager.update.subscribe(type => {
      if (type === UpdateType.AVATAR) {
        this.getLoggedUser();

      } else if (type === UpdateType.ACTIVE_PAGES) {
        this.activePages = null;
        this.initNavigations();
      }
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- User ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
    this.photo.set(this.user.photoUrl);
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
    if (!this.course || this.course.id !== this.getCourseIDFromURL() || !this.activePages || (this.hasTokensEnabled && !this.tokens)) {
      const courseInfo = await this.getCourseInfo();
      const isAdminOrTeacher = this.user.isAdmin || await this.isCourseTeacher();

      this.hasTokensEnabled = await this.isVirtualCurrencyEnabled();
      if (this.hasTokensEnabled) {
        this.isStudent = await this.isCourseStudent();
        if (this.isStudent) this.tokens = await this.getUserTokens();
      }

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


  /*** --------------------------------------------- ***/
  /*** ------------------ Logout ------------------- ***/
  /*** --------------------------------------------- ***/

  logout(): void {
    this.api.logout().subscribe(
      isLoggedIn => {
        if (!isLoggedIn) this.router.navigate(['/login'])
      })
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

  async isCourseStudent(): Promise<boolean> {
    const courseID = this.getCourseIDFromURL();
    if (courseID) return await this.api.isCourseStudent(courseID).toPromise()
    return null;
  }

  async isVirtualCurrencyEnabled(): Promise<boolean> {
    const courseID = this.getCourseIDFromURL();
    if (courseID) return await this.api.isVirtualCurrencyEnabled(courseID).toPromise();
    return null;
  }

  async getUserTokens(): Promise<number> {
    const courseID = this.getCourseIDFromURL();
    if (courseID) return await this.api.getUserTokens(courseID, this.user.id).toPromise();
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
