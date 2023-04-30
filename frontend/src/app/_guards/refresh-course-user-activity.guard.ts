import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, RouterStateSnapshot, UrlTree } from '@angular/router';
import { Observable } from 'rxjs';

import { CourseUser } from "../_domain/users/course-user";

@Injectable({
  providedIn: 'root'
})
export class RefreshCourseUserActivityGuard implements CanActivate {

  // Trigger refresh on course user lastActivity
  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    const courseID = parseInt(route.params['id']);

    if (!CourseUser.activityRefreshState.has(courseID))
      CourseUser.refreshActivity(courseID);

    return true;
  }

}
