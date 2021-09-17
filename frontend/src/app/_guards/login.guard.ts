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
export class LoginGuard implements CanActivate, CanLoad {

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
    return this.api.isLoggedIn().pipe(
      map(
        isLoggedIn => {
          if (isLoggedIn) {
            return true;
          } else {
            return this.router.parseUrl('/login');
          }
        },
        error => {
          ErrorService.set(error)
          return false;
        }
      ),
      catchError(error => {
        if (error.status === 401) {
          return of(this.router.parseUrl('/no-access'));
        }

        if (error.status === 409) {
          return of(this.router.parseUrl('/setup'));
        }

        return throwError(error)
      })
    );
  }
}
