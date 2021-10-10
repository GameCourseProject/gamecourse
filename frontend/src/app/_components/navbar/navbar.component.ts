import {Component, HostListener, OnInit} from '@angular/core';
import {ActivatedRoute, NavigationEnd, Router} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../_services/api/api-http.service";
import {ErrorService} from "../../_services/error.service";
import {UpdateService, UpdateType} from "../../_services/update.service";

import {User} from "../../_domain/users/user";
import {Course, CourseInfo} from "../../_domain/courses/course";
import {ImageManager} from "../../_utils/images/image-manager";

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

  docs: boolean;

  course: Course;
  courseInfo: CourseInfo;

  constructor(
    private api: ApiHttpService,
    private router: Router,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService,
  ) {
    this.photo = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    this.getUserInfo();
    this.updateManager.update.subscribe(type => {
      if (type === UpdateType.AVATAR) {
        this.getUserInfo();

      } else if (type === UpdateType.ACTIVE_PAGES) {
        this.courseInfo = null;
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
    this.docs = this.router.url.includes('docs');
    this.course = await this.getCourse();

    if (this.docs) this.navigation = this.getDocsNavigation();
    else if (!this.course) this.navigation = this.getMainNavigation();
    else if (this.course) this.setCourseNavigation();
    else this.navigation = [];
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
          {link: '/settings/about', name: 'About'},
          {link: '/settings/global', name: 'Global'},
          {link: '/settings/modules', name: 'Modules'}
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

  setCourseNavigation(): void {
    if (!this.courseInfo) {
      this.api.getCourseInfo(this.course.id)
        .subscribe(info => {
          this.courseInfo = info;
          this.courseNavigation = buildCourseNavigation(this.course.id, this.courseInfo);
          this.navigation = this.courseNavigation;
        },
          error => ErrorService.set(error));
    } else {
      this.navigation = this.courseNavigation;
    }

    function buildCourseNavigation(courseID: number, courseInfo: CourseInfo): Navigation[] {
      const path = '/courses/' + courseID + '/';

      return courseInfo.navigation.map(nav => {
        if (nav.text === 'Users') return { link: path + 'users', name: nav.text }
        else if (nav.text === 'Course Settings') {
          const children: Navigation[] = [
            {link: path + 'settings/global', name: 'This Course'},
            {link: path + 'settings/roles', name: 'Roles'},
            {link: path + 'settings/modules', name: 'Modules'},
            {link: path + 'settings/rules', name: 'Rules'}
          ];

          if (courseInfo.settings.find(el => el.text === 'Views'))
            children.push({link: path + 'settings/views', name: 'Views'});

          return { link: path + 'settings', name: nav.text, children }

        } else {
          // FIXME: refactor api link
          const pageId = parseInt(nav.sref.substr(nav.sref.search('id')).replace("id:'", "").split("'")[0]);
          return { link: path + 'pages/' + pageId, name: nav.text }
        }
      })
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

  async getCourse(): Promise<Course> {
    const urlParts = this.router.url.substr(1).split('/');
    if (urlParts.includes('courses') && urlParts.length >= 2) {
      const courseID = parseInt(urlParts[1]);
      return await this.api.getCourse(courseID).toPromise();
    } else return null;
  }


  @HostListener('window:resize', [])
  onWindowResize(): void {
    this.initNavigations();
  }

}

interface Navigation {
  link: string,
  name: string,
  children?: Navigation[]
}
