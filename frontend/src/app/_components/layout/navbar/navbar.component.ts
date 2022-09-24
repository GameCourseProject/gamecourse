import {Component, OnInit} from '@angular/core';
import {NavigationEnd, PRIMARY_OUTLET, Router} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";
import {environment} from "../../../../environments/environment.prod";
import {of} from "rxjs";

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";
import {ThemingService} from "../../../_services/theming/theming.service";
import {UpdateService, UpdateType} from "../../../_services/update.service";
import {SidebarService} from "../../../_services/sidebar.service";

import {User} from "../../../_domain/users/user";
import {Course} from 'src/app/_domain/courses/course';
import {ResourceManager} from "../../../_utils/resources/resource-manager";

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html'
})
export class NavbarComponent implements OnInit {

  user: User;
  course: Course;
  photo: ResourceManager;

  // FIXME: navbar space should be configurable in modules
  hasTokensEnabled: boolean;
  isStudent: boolean;
  tokens: number;
  tokensImg: string = ApiEndpointsService.API_ENDPOINT + '/modules/' + ApiHttpService.VIRTUAL_CURRENCY + '/imgs/token.png'; // FIXME: should be configurable

  constructor(
    private api: ApiHttpService,
    public router: Router,
    private themeService: ThemingService,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService,
    public sidebar: SidebarService
  ) {
    this.photo = new ResourceManager(sanitizer);
  }

  async ngOnInit(): Promise<void> {
    // Get logged user information
    await this.getLoggedUser();

    // Get course information
    await this.getCourse();

    // Whenever URL changes
    this.router.events.subscribe(event => {
      if (event instanceof NavigationEnd) {
        const courseID = this.getCourseIDFromURL();
        if (courseID !== this.course?.id) this.getCourse();
      }
    });

    // Whenever updates are received
    this.updateManager.update.subscribe(type => {
      if (type === UpdateType.AVATAR) {
        this.user = null; // NOTE: forces skeleton
        this.getLoggedUser();
      }
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- User ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
    this.photo.set(this.user.photoUrl ?? environment.defaultAvatar);
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Course ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(): Promise<void> {
    const courseID = this.getCourseIDFromURL();
    if (courseID) this.course = await this.api.getCourseById(courseID).toPromise();
    else this.course = null;
  }


  /*** --------------------------------------------- ***/
  /*** ------------- Configurable Area ------------- ***/
  /*** --------------------------------------------- ***/

  async getConfigurableArea(): Promise<void> {
    // FIXME: should be made general
    // this.hasTokensEnabled = await this.isVirtualCurrencyEnabled();
    // if (this.hasTokensEnabled) {
    //   this.isStudent = await this.api.isStudent(this.course.id, this.user.id).toPromise();
    //   if (this.isStudent) this.tokens = await this.getUserTokens();
    // }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Logout ------------------- ***/
  /*** --------------------------------------------- ***/

  logout(): void {
    this.api.logout().subscribe(
      isLoggedIn => {
        if (!isLoggedIn) this.router.navigate(['/login']);
      })
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getCourseIDFromURL(): number {
    const urlParts = this.router.url.substr(1).split('/');
    if (urlParts.includes('courses') && urlParts.length >= 2) return parseInt(urlParts[1]);
    else return null;
  }

  getURLParts(full: boolean = false): string[] {
    const segments = this.router.parseUrl(this.router.url).root.children[PRIMARY_OUTLET].segments;
    return segments.map(segment => segment.path
      .replaceAll('-', ' ')
      .split(' ')
      .map(word => word.capitalize())
      .join(' ')).slice(full ? 0 : 2);
  }

  isInCourse(): boolean {
    const parts = this.getURLParts(true);
    return parts.includes('Courses') && parts.length >= 2;
  }

  async isVirtualCurrencyEnabled(): Promise<boolean> {
    return of(false).toPromise(); // FIXME
    // const courseID = this.getCourseIDFromURL();
    // if (courseID) return await this.api.isVirtualCurrencyEnabled(courseID).toPromise();
    // return null;
  }

  async getUserTokens(): Promise<number> {
    return of(100).toPromise(); // FIXME
    // const courseID = this.getCourseIDFromURL();
    // if (courseID) return await this.api.getUserTokens(courseID, this.user.id).toPromise();
    // return null;
  }
}
