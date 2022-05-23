import { Injectable } from '@angular/core';
import {
  CanLoad,
  Route,
  Router,
  UrlSegment,
  UrlTree
} from '@angular/router';
import {Observable, of, throwError} from 'rxjs';
import {catchError, map} from "rxjs/operators";
import {ApiHttpService} from "../_services/api/api-http.service";
import {ErrorService} from "../_services/error.service";

@Injectable({
  providedIn: 'root'
})
export class RedirectIfLoggedInGuard implements CanLoad {

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  canLoad(
    route: Route,
    segments: UrlSegment[]): Observable<boolean | UrlTree> |  Promise<boolean | UrlTree> |boolean | UrlTree {

    return this.check();
  }

  check() {
    return this.api.isLoggedIn().pipe(
      map(
        isLoggedIn => {
          if (isLoggedIn) return this.router.parseUrl('/');
          return true;
        },
        error => {
          ErrorService.set(error)
          return true;
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
