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
import {Observable, throwError} from 'rxjs';
import {catchError, map} from "rxjs/operators";
import {ApiHttpService} from "../_services/api/api-http.service";

@Injectable({
  providedIn: 'root'
})
export class RedirectIfLoggedInGuard implements CanActivate, CanLoad {

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    return this.check();
  }

  canLoad(
    route: Route,
    segments: UrlSegment[]): Observable<boolean | UrlTree> |  Promise<boolean | UrlTree> |boolean | UrlTree {

    return this.check();
  }

  check() {
    return this.api.checkLogin().pipe(
      map(
        isLoggedIn => {
          if (isLoggedIn) {
            return this.router.parseUrl('/');
          } else {
            return true;
          }
        },
        error => true
      ),
      catchError(error => throwError(error))
    );
  }
}
