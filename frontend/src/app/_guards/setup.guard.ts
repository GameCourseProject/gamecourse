import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  CanActivate,
  CanLoad,
  Route, Router,
  RouterStateSnapshot,
  UrlSegment,
  UrlTree
} from '@angular/router';
import {Observable, throwError} from 'rxjs';
import {ApiHttpService} from "../_services/api/api-http.service";
import {catchError, map} from "rxjs/operators";

@Injectable({
  providedIn: 'root'
})
export class SetupGuard implements CanActivate, CanLoad {

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    const url = state.url;
    // return this.check(url);
    return true;
  }

  canLoad(
    route: Route,
    segments: UrlSegment[]): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    const url = '/' + route.path;
    // return this.check(url);
    return true;
  }

  check(url: string): Observable<boolean> {
    return this.api.needsSetup().pipe(
      map(
        res => {
          if (url === '/setup') {
            if (!res) {
              this.router.navigate(['/']);
              return false;
            }
            return true;

          } else {
            if (res) {
              this.router.navigate(['/setup']);
              return false;
            }
            return true;
          }
        }
      ),
      catchError(error => throwError(error)))
  }

}
