import { Component, OnInit } from '@angular/core';
import { NavigationEnd, Router } from "@angular/router";

import { ApiHttpService } from "../../../_services/api/api-http.service";
import { UpdateService, UpdateType } from "../../../_services/update.service";
import { SidebarService } from "../../../_services/sidebar.service";

import { User } from "../../../_domain/users/user";
import { Course } from "../../../_domain/courses/course";
import { Page } from "../../../_domain/views/pages/page";

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
  visibleUserPages: Page[];

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
        this.visibleUserPages = null;
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
      this.navigation = await this.getMainNavigation();
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

  async getMainNavigation(): Promise<Navigation[]> {
    this.mainNavigation = [];
    const pages: { [key: string]: Navigation } = {
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

    // load default theme
    await this.themeService.loadTheme();

    return this.mainNavigation;
  }

  async getCourseNavigation(): Promise<Navigation[]> {
    if (!this.course || this.course.id !== this.getCourseIDFromURL() || !this.visibleUserPages) {
      const courseID = this.getCourseIDFromURL();

      this.course = await this.api.getCourseById(courseID).toPromise();
      this.visibleUserPages = await this.api.getUserPages(courseID, this.user.id, true).toPromise();
      const isAdminOrTeacher = this.user.isAdmin || await this.api.isTeacher(courseID, this.user.id).toPromise();

      this.courseNavigation = buildCourseNavigation(this.course, this.user.id, this.visibleUserPages, isAdminOrTeacher);

      // load course theme
      await this.themeService.loadTheme(this.course.id);
    }
    return this.courseNavigation;

    function buildCourseNavigation(course: Course, userId: number, visiblePages: Page[], isAdminOrTeacher: boolean): Navigation[] {
      const path = '/courses/' + course.id + '/';
      let navigation: Navigation[] = [];

      // Get started (students)
      if (!isAdminOrTeacher) {
        navigation.push({
          link: path + 'main',
          name: 'Get Started',
          icon: 'tabler-bell-school'
        });
      }

      // Course pages
      navigation.push(
        {
          category: 'Course Pages',
          children: visiblePages.map(page => {
            return {
              link: path + 'pages/' + page.id + '/user/' + userId,
              name: page.name
            };
          })
        }
      );

      // Admin pages
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
                link: path + 'settings/db-explorer',
                name: 'DB Explorer',
                icon: 'tabler-database'
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
                icon: 'feather-file'
              }
            ]
          },
          {
            link: path + 'overview',
            name: 'Overview',
            icon: 'feather-info'
          },
          {
            link: path + 'settings',
            name: 'Course Settings',
            icon: 'tablerSettings'
          }
        ];
        navigation = navigation.concat(fixed);
      }

      const adaptation: Navigation = {
        link: isAdminOrTeacher ? path + 'settings/adaptation' : path + 'adaptation',
        name: 'Adaptation',
        icon: 'tabler-puzzle'
      };

      const notifications: Navigation = {
        link: isAdminOrTeacher ? path + 'settings/notifications' : path + 'notifications',
        name: 'Notifications',
        icon: 'tabler-bell'
      };

      if (isAdminOrTeacher) {
        navigation[2].children.splice(4, 0, notifications);
        navigation[3].children.splice(2, 0, adaptation);
      }
      else {
        navigation.push({
          category: 'User Settings',
          children: [adaptation]
        });
      }

      return navigation;
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getDefaultLogoImg(): string {
    const html = document.querySelector('html');
    const theme = html.getAttribute('data-theme');

    switch (theme) {
      case Theme.DARK:
      case Theme.SYNTHWAVE:
      case Theme.DRACULA:
      case Theme.HALLOWEEN:
      case Theme.FOREST:
      case Theme.BLACK:
      case Theme.LUXURY:
      case Theme.NIGHT:
      case Theme.COFFEE:
      case Theme.BUSINESS:
        return environment.logoPicture.dark;
      default:
        return environment.logoPicture.light;
    }
  }

  getCourseIDFromURL(): number {
    const urlParts = this.router.url.substr(1).split('/');
    if (urlParts.includes('courses') && urlParts.length >= 2) return parseInt(urlParts[1]);
    else return null;
  }

  showDivider(index: number, navigation: Navigation[]): boolean {
    if (index === navigation.length - 1) return false;

    const item = navigation[index];
    return (item.category && item.children?.length > 0) || navigation[index + 1].children?.length > 0;
  }
}

export interface Navigation {
  link?: string,
  name?: string,
  icon?: string,
  category?: string,
  children?: Navigation[]
}
