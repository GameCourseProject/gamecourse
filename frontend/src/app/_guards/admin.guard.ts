import { Injectable } from '@angular/core';
import {
  CanLoad,
  Route,
  Router,
  UrlSegment,
  UrlTree
} from '@angular/router';
import {Observable, throwError} from 'rxjs';
import {ApiHttpService} from "../_services/api/api-http.service";
import {catchError, map} from "rxjs/operators";

@Injectable({
  providedIn: 'root'
})
export class AdminGuard implements CanLoad {

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
    return this.api.getLoggedUser().pipe(
      map(user => {
        if (user.isAdmin) return true;
        else return this.router.parseUrl('/');
      } ),
      catchError(error => throwError(error))
    );
  }
}
