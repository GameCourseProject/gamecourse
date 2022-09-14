import {Component, OnInit} from '@angular/core';
import {Course} from "../../../_domain/courses/course";
import {Page} from "../../../_domain/pages & templates/page";
import {NavigationEnd, Router} from "@angular/router";
import {UpdateService, UpdateType} from "../../../_services/update.service";
import {User} from "../../../_domain/users/user";
import {ApiHttpService} from "../../../_services/api/api-http.service";

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
  isATeacher: boolean;

  course: Course;
  activePages: Page[];

  constructor(
    private api: ApiHttpService,
    private router: Router,
    private updateManager: UpdateService
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
    // this.user.isAdmin = false; // FIXME: remove
    this.isATeacher = await this.api.isATeacher(this.user.id).toPromise();
    // this.isATeacher = false; // FIXME: remove
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
        name: 'Users',
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
      this.activePages = []; // FIXME
      const isAdminOrTeacher = this.user.isAdmin || await this.api.isTeacher(courseID, this.user.id).toPromise();

      this.courseNavigation = buildCourseNavigation(this.course, this.activePages, isAdminOrTeacher);
    }
    return this.courseNavigation;

    function buildCourseNavigation(course: Course, activePages: Page[], isAdminOrTeacher: boolean): Navigation[] {
      const path = '/courses/' + course.id + '/';

      const pages: Navigation[] = [
        {
            link: path + 'main',
            name: 'Main Page',
            icon: 'feather-home'
        },
        {
          category: 'Pages',
          children: []
        }
      ];

      pages[1].children = activePages.map(page => {
        return {
          link: path + 'pages/' + page.id,
          name: page.name
        };
      });

      if (isAdminOrTeacher) {
        const fixed: Navigation[] = [
          {
            link: path + 'users',
            name: 'Course Users',
            icon: 'feather-users'
          },
          {
            link: path + 'roles',
            name: 'Roles',
            icon: 'tabler-id-badge-2'
          },
          {
            category: 'Settings',
            children: [
              {
                link: path + 'settings/autogame',
                name: 'AutoGame',
                icon: 'tabler-prompt'
              },
              {
                link: path + 'settings/modules',
                name: 'Modules',
                icon: 'tabler-plug'
              },
              {
                link: path + 'settings/pages',
                name: 'Pages',
                icon: 'feather-layout'
              },
              {
                link: path + 'settings/rules',
                name: 'Rule System',
                icon: 'tabler-clipboard-list'
              },
              {
                link: path + 'settings/themes',
                name: 'Themes',
                icon: 'tabler-color-swatch'
              }
            ]
          },
          {
            link: path + 'info',
            name: 'About Course',
            icon: 'feather-info'
          }
        ];
        return pages.concat(fixed);
      }

      return pages;
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getCourseIDFromURL(): number {
    const urlParts = this.router.url.substr(1).split('/');
    if (urlParts.includes('courses') && urlParts.length >= 2) return parseInt(urlParts[1]);
    else return null;
  }
}

export interface Navigation {
  link?: string,
  name?: string,
  icon?: string,
  category?: string,
  children?: Navigation[]
}
