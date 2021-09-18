import {Component, HostListener, Input, OnInit} from '@angular/core';
import {ApiHttpService} from "../../_services/api/api-http.service";
import {ActivatedRoute, NavigationEnd, Router} from "@angular/router";
import {User} from "../../_domain/User";
import {Observable} from "rxjs";
import {DomSanitizer} from "@angular/platform-browser";
import {ImageManager} from "../../_utils/image-manager";
import {ErrorService} from "../../_services/error.service";
import {CourseInfo} from "../../_domain/Course";
import {ImageUpdateService} from "../../_services/image-update.service";

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit {

  @Input() updatePhoto?: Observable<void>;

  user: User;
  photo: ImageManager;

  navigation: Navigation[];
  mainNavigation: Navigation[];
  courseNavigation: Navigation[];
  docsNavigation: Navigation[];

  docs: boolean;

  course: { id: number, name: string };
  courseInfo: CourseInfo;

  constructor(
    private api: ApiHttpService,
    private router: Router,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer,
    private photoUpdate: ImageUpdateService
  ) {
    this.photo = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    this.getUserInfo();
    this.photoUpdate.update.subscribe(() => this.getUserInfo())
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getUserInfo(): void {
    this.api.getLoggedUser()
      .subscribe(user => {
        this.user = user;
        this.photo.set(user.photoUrl);

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

  initNavigations(): void {
    this.docs = this.router.url.includes('docs');
    this.course = this.getCourse();

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
          this.courseNavigation = buildCourseNavigation(this.course.id, this.course.name, this.courseInfo);
          this.navigation = this.courseNavigation;
        },
          error => ErrorService.set(error));
    } else {
      this.navigation = this.courseNavigation;
    }

    function buildCourseNavigation(courseID: number, courseName: string, courseInfo: CourseInfo): Navigation[] {
      const path = '/courses/' + courseID + '/' + courseName + '/';

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

        } else return { link: path + '/' + nav.text.replace(' ', '-').toLowerCase(), name: nav.text }
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

  getCourse(): { id: number, name: string } {
    const urlParts = this.router.url.substr(1).split('/');
    if (urlParts.includes('courses') && urlParts.length >= 3)
      return {id: parseInt(urlParts[1]), name: urlParts[2]}
    else return null;
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
