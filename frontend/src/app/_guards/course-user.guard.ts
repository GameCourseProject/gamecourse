import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot, CanActivate,
  Router, RouterStateSnapshot,
  UrlTree
} from '@angular/router';
import {Observable} from 'rxjs';
import {ApiHttpService} from "../_services/api/api-http.service";

@Injectable({
  providedIn: 'root'
})
export class CourseUserGuard implements CanActivate {

  courseID: number;

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    this.courseID = this.getCourseIDFromURL(route);
    return this.check();
  }

  async check() {
    const user = await this.api.getLoggedUser().toPromise();
    if (user.isAdmin) return true;

    const isCourseUser = await this.api.isCourseUser(this.courseID, user.id).toPromise();
    if (isCourseUser) return true;

    return this.router.parseUrl('/no-access');
  }

  getCourseIDFromURL(route: ActivatedRouteSnapshot): number {
    const url = route.pathFromRoot
      .map(v => v.url.map(segment => segment.toString()).join('/'))
      .join('/');
    return parseInt(url.match('courses\\/(.*)')[1]);
  }
}
