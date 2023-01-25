import { Component, OnInit } from '@angular/core';
import { NavigationEnd, Router } from "@angular/router";

import { ApiHttpService } from "../../../_services/api/api-http.service";
import { UpdateService, UpdateType } from "../../../_services/update.service";
import { SidebarService } from "../../../_services/sidebar.service";

import { User } from "../../../_domain/users/user";
import { Course } from "../../../_domain/courses/course";
import { Page } from "../../../_domain/pages & templates/page";

import {Theme} from "../../../_services/theming/themes-available";
import {ThemingService} from "../../../_services/theming/theming.service";
import {environment} from "../../../../environments/environment";

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html'
})
export class SidebarComponent implements OnInit {

  navigation: Navigation[];
  mainNavigation: Navigation[];
  courseNavigation: Navigation[];
  docsNavigation: Navigation[];

  isDocs: boolean;

  user: User;
  isCourseAdmin: boolean;

  course: Course;
  activePages: Page[];

  constructor(
    private api: ApiHttpService,
    private router: Router,
    private updateManager: UpdateService,
    public sidebar: SidebarService,

    private themeService: ThemingService
  ) { }

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
      if (type === UpdateType.ACTIVE_PAGES) {
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
    this.isCourseAdmin = await this.api.isATeacher(this.user.id).toPromise() || this.user.isAdmin;
  }


  /*** --------------------------------------------- ***/
  /*** ---------------- Navigation ----------------- ***/
  /*** --------------------------------------------- ***/

  async initNavigations(): Promise<void> {
    this.isDocs = this.router.url.includes('docs');
    const isInCourse = this.router.url.includes('courses/');

    if (this.isDocs) this.navigation = this.getDocsNavigation();
    else if (!isInCourse) {
      this.navigation = this.getMainNavigation();
      this.course = null;

    } else if (isInCourse) {
      this.navigation = await this.getCourseNavigation();

    } else this.navigation = [];
  }

  getDocsNavigation(): Navigation[] { // FIXME: needs refactoring
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
        link: '/home',
        name: 'Homepage',
        icon: 'feather-home'
      },
      coursesPage: {
        link: '/courses',
        name: 'Courses',
        icon: 'tabler-books'
      },
      usersPage: {
        link: '/users',
        name: 'System Users',
        icon: 'feather-users'
      },
      settings: {
        category: 'Settings',
        children: [
          {
            link: '/settings/modules',
            name: 'Installed Modules',
            icon: 'tabler-plug'
          },
          {
            link: '/settings/themes',
            name: 'Themes Available',
            icon: 'tabler-color-swatch'
          }
        ]
      },
      aboutPage: {
        link: '/about',
        name: 'About',
        icon: 'feather-info'
      }
    }

    this.mainNavigation.push(pages.mainPage);
    this.mainNavigation.push(pages.coursesPage);
    if (this.user.isAdmin) {
      this.mainNavigation.push(pages.usersPage);
      this.mainNavigation.push(pages.settings);
    }
    this.mainNavigation.push(pages.aboutPage);

    return this.mainNavigation;
  }

  async getCourseNavigation(): Promise<Navigation[]> {
    if (!this.course || this.course.id !== this.getCourseIDFromURL() || !this.activePages) {
      const courseID = this.getCourseIDFromURL();

      this.course = await this.api.getCourseById(courseID).toPromise();
      this.activePages = []; // FIXME: get pages for user
      const isAdminOrTeacher = this.user.isAdmin || await this.api.isTeacher(courseID, this.user.id).toPromise();

      this.courseNavigation = buildCourseNavigation(this.course, this.activePages, isAdminOrTeacher);
    }
    return this.courseNavigation;

    function buildCourseNavigation(course: Course, activePages: Page[], isAdminOrTeacher: boolean): Navigation[] {
      const path = '/courses/' + course.id + '/';

      let navigation: Navigation[] = [];

      if (isAdminOrTeacher) {
        navigation.push(
          {
            category: 'Course Pages',
            children: []
          }
        );
      }

      const pages = activePages.map(page => {
        return {
          link: path + 'pages/' + page.id,
          name: page.name
        };
      });
      if (isAdminOrTeacher) navigation[0].children = pages;
      else navigation = navigation.concat(pages);

      if (isAdminOrTeacher) {
        const fixed: Navigation[] = [
          {
            category: 'Users',
            children: [
              {
                link: path + 'settings/users',
                name: 'Course Users',
                icon: 'feather-users'
              },
              {
                link: path + 'settings/roles',
                name: 'Roles',
                icon: 'tabler-id-badge-2'
              }
            ]
          },
          {
            category: 'Course Operation',
            children: [
              {
                link: path + 'settings/autogame',
                name: 'AutoGame',
                icon: 'tabler-prompt'
              },
              {
                link: path + 'settings/rule-system',
                name: 'Rule Editor',
                icon: 'tabler-clipboard-list'
              },
              {
                link: path + 'settings/modules',
                name: 'Modules',
                icon: 'tabler-plug'
              }
            ]
          },
          {
            category: 'User Interface',
            children: [
              {
                link: path + 'settings/pages',
                name: 'Pages',
                icon: 'feather-layout'
              },
              {
                link: path + 'settings/themes',
                name: 'Themes',
                icon: 'tabler-color-swatch'
              },
              {
                link: path + 'settings/adaptation',
                name: 'Adaptation',
                icon: 'tabler-puzzle'
              }
            ]
          },
          {
            link: path + 'overview',
            name: 'Overview',
            icon: 'feather-info'
          }
        ];
        navigation = navigation.concat(fixed);
      }

      return navigation;
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  get DefaultLogoImg(): string {
    const theme = this.themeService.getTheme();
    return theme === Theme.DARK ? environment.logoPicture.dark : environment.logoPicture.light;
  }

  getCourseIDFromURL(): number {
    const urlParts = this.router.url.substr(1).split('/');
    if (urlParts.includes('courses') && urlParts.length >= 2) return parseInt(urlParts[1]);
    else return null;
  }

  showDivider(position: 'top' | 'bottom', index: number, navigation: Navigation[]): boolean {
    const item = navigation[index];

    if (position === 'top') {
      if (index === 0) return false;
      for (let j = 0; j < index; j++) {
        if (navigation[j].children && navigation[j].children.length === 0)
          return false;
      }
      return item.category && item.children?.length > 0;

    } else {
      if (index === navigation.length - 1) return false;
      for (let j = index + 1; j < navigation.length; j++) {
        if (navigation[j].children && navigation[j].children.length === 0)
          return false;
      }
      return item.category && item.children?.length > 0;
    }
  }
}

export interface Navigation {
  link?: string,
  name?: string,
  icon?: string,
  category?: string,
  children?: Navigation[]
}
