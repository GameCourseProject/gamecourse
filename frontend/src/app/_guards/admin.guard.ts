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

@Injectable({
  providedIn: 'root'
})
export class AdminGuard implements CanActivate, CanLoad {

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
    return this.api.getLoggedUser().pipe(
      map(user => {
        if (user.isAdmin) return true;
        else return this.router.parseUrl('/');
      } ),
      catchError(error => throwError(error))
    );
  }
}
