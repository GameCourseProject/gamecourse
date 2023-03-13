import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  CanActivate,
  Router, RouterStateSnapshot,
  UrlTree
} from '@angular/router';
import {Observable, of, throwError} from 'rxjs';
import {ApiHttpService} from "../_services/api/api-http.service";
import {catchError, map} from "rxjs/operators";
import {ErrorService} from "../_services/error.service";

@Injectable({
  providedIn: 'root'
})
export class LoginGuard implements CanActivate {

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    return this.check(state.url);
  }

  check(requestedURL: string) {
    return this.api.isLoggedIn().pipe(
      map(
        isLoggedIn => {
          if (isLoggedIn) {
            // Redirect to requested URL before login
            requestedURL = window.localStorage.getItem('redirect_after_login');
            if (requestedURL) {
              this.router.navigateByUrl(requestedURL);
              window.localStorage.removeItem('redirect_after_login');
            }

            return true;
          }

          // Save requested URL to redirect after login
          window.localStorage.setItem('redirect_after_login', requestedURL)
          return this.router.parseUrl('/login');
        },
        error => {
          ErrorService.set(error)
          return false;
        }
      ),
      catchError(error => {
        if (error.status === 403)
          return of(this.router.parseUrl('/no-access'));

        if (error.status === 409)
          return of(this.router.parseUrl('/setup'));

        return throwError(error);
      })
    );
  }
}
