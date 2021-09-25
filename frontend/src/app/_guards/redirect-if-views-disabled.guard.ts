import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  CanActivate,
  CanLoad,
  Route,
  Router,
  RouterStateSnapshot,
  UrlSegment,
  UrlTree
} from '@angular/router';
import {Observable, of, throwError} from 'rxjs';
import {ApiHttpService} from "../_services/api/api-http.service";
import {catchError, map} from "rxjs/operators";
import {ErrorService} from "../_services/error.service";

@Injectable({
  providedIn: 'root'
})
export class RedirectIfViewsDisabledGuard implements CanActivate, CanLoad {

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    return this.check(state.url);
  }

  canLoad(
    route: Route,
    segments: UrlSegment[]): Observable<boolean | UrlTree> |  Promise<boolean | UrlTree> |boolean | UrlTree {

    return this.check(route.path);
  }

  check(url: string) {
    let urlParts = url.substr(1).split('/');
    const courseID = urlParts[0] === 'courses' ? parseInt(urlParts[1]) : null;

    if (!courseID) return false;

    return this.api.hasViewsEnabled(courseID).pipe(
      map(
        hasViewsEnabled => {
          if (!hasViewsEnabled) {
            urlParts.splice(urlParts.length - 1, 1);
            const redirectURL = '/' + urlParts.join('/');
            return this.router.parseUrl(redirectURL);
          } else {
            return true;
          }
        },
        error => {
          ErrorService.set(error);
          return this.router.parseUrl('/');
        }
      ),
      catchError(error => throwError(error))
    );
  }
}
