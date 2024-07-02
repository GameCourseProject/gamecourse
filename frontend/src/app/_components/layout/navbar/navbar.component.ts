import {Component, OnInit} from '@angular/core';
import {NavigationEnd, PRIMARY_OUTLET, Router} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";
import {environment} from "../../../../environments/environment.prod";

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {UpdateService, UpdateType} from "../../../_services/update.service";
import {SidebarService} from "../../../_services/sidebar.service";

import {User} from "../../../_domain/users/user";
import {Course} from 'src/app/_domain/courses/course';
import {ResourceManager} from "../../../_utils/resources/resource-manager";
import {Notification} from '../../../_domain/notifications/notification';
import {Theme} from "../../../_services/theming/themes-available";
import * as moment from "moment";


@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html'
})
export class NavbarComponent implements OnInit {

  user: User;
  course: Course;
  photo: ResourceManager;

  notifications: Notification[] = [];
  mode: "new" | "notNew";

  breadcrumbsLinks: { name: string, url: string }[] = [];

  constructor(
    private api: ApiHttpService,
    public router: Router,
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

    // Get notifications information
    await this.getNotifications();

    // Get breadcrumbs information
    await this.getBreadcrumbs();

    // Whenever URL changes
    this.router.events.subscribe(async event => {
      if (event instanceof NavigationEnd) {
        const courseID = this.getCourseIDFromURL();
        if (courseID !== this.course?.id) this.getCourse();
        await this.getBreadcrumbs();
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
  /*** ---------------- Breadcrumbs ---------------- ***/
  /*** --------------------------------------------- ***/

  async getBreadcrumbs() {
    const segments = this.router.parseUrl(this.router.url).root.children[PRIMARY_OUTLET].segments;
    this.breadcrumbsLinks = [];
    let url = "";

    for (const [index, segment] of segments.entries()) {
      url += "/" + segment;

      // Parts that are just numbers don't matter
      if (!segment.path.match("[0-9]+")) {
        // Intermediate Paths in pages/[pid]/user/[uid] don't make sense since there's no page for those
        // In Custom Page show its name tho, and settings/pages must still work
        if (segment.path == "pages" && segments[index - 1].path != "settings") {
          const id = segments[index + 1]
          const page = await this.api.getPageById(+id.path).toPromise();
          this.breadcrumbsLinks.push({
            name: page.name,
            url: url
          })
        }
        else if (segment.path == "user") {
          continue;
        }
        // In modules/[name]/config can only go back to modules, but display its name at end
        else if (segment.path == "config" && segments[index-2].path == "modules") {
          continue;
        }
        // To go back to rule-system/sections need the id after it
        else if (segment.path == "sections") {
          this.breadcrumbsLinks.push({
            name: "Sections",
            url: url + "/" + segments[index + 1],
          })
        }
        // In View Editor, can have editor/[id] or editor/new or editor/system-template/[id]
        // so having "editor" is only relevant if there's no id right after it
        else if (segment.path == "editor" && !segments[index + 1].path.match("[0-9]+")) {
          continue;
        }
        else {
          this.breadcrumbsLinks.push({
            name: segment.path.replaceAll('-', ' ')
              .split(' ')
              .map(word => word.capitalize())
              .join(' '),
            url: url
          })
        }
      }

    }
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- User ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
    this.photo.set(this.user.avatarUrl ?? this.user.photoUrl ?? environment.defaultAvatar);
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Course ----------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(): Promise<void> {
    const courseID = this.getCourseIDFromURL();
    if (courseID) this.course = await this.api.getCourseById(courseID).toPromise();
    else this.course = null;
  }

  /*** --------------------------------------------- ***/
  /*** -------------- Notifications ---------------- ***/
  /*** --------------------------------------------- ***/

  async getNotifications():Promise<void> {
    const notifications = await this.api.getNotificationsByUser(this.user.id).toPromise();
    this.notifications = notifications.reverse();
    this.mode = "notNew";

    // see if there are notifications to be showed
    for (let i = 0; i < this.notifications.length; i++) {
      if (!this.isShowed(this.notifications[i])) {
        this.mode = "new";
        break;
      }
    }
  }

  async notificationSetShowed(notification: Notification): Promise<void> {
    if (!this.isShowed(notification)){
      const date = moment().format("YYYY-MM-DD HH:mm:ss");
      const notificationEdited = await this.api.notificationSetShowed(notification.id, true, date).toPromise();
      const index = this.notifications.findIndex(notification => notification.id === notificationEdited.id);
      this.notifications.splice(index, 1, notificationEdited);
    }

    for (let i = 0; i < this.notifications.length; i++) {
      if (!this.isShowed(this.notifications[i])) {
        this.mode = "new";
        return;
      }
    }
    this.mode = "notNew";
  }

  async setAllNotificationsShowed(){
    for (let i = 0; i < this.notifications.length; i++){
      await this.notificationSetShowed(this.notifications[i]);
    }
  }

  isShowed(notification: Notification){
    return !((notification.isShowed).toString() === "0" || (notification.isShowed).toString() === "false");
  }

  bgColor(notification?: Notification){
    if (!notification || this.isShowed(notification)){
      return this.theme === Theme.DARK ? "bg-gray-500 text-gray-300" : "bg-gray-200";
    } else {
      return "bg-rose-200 text-black hover:bg-rose-300";
    }
  }

  getCount(){
    let count = 0;
    for (let i=0; i < this.notifications.length; i++){
      if (!this.isShowed(this.notifications[i])) {
        count++;
      }
    }

    if (count < 9) { return count.toString(); } else {return "9+";}
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Themes ------------------- ***/
  /*** --------------------------------------------- ***/

  protected readonly Theme = Theme;

  get theme() {
    const html = document.querySelector('html');
    return html.getAttribute('data-theme') as Theme;
  }

  getNavbarColor() {
    if (this.course?.color && (this.theme == Theme.LIGHT || this.theme == Theme.DARK)) {
      return this.course.color;
    } else return '';
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

}
