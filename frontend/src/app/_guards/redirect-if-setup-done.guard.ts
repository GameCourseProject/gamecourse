import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  CanActivate,
  Router, RouterStateSnapshot,
  UrlTree
} from '@angular/router';
import {Observable, throwError} from 'rxjs';
import {ApiHttpService} from "../_services/api/api-http.service";
import {catchError, map} from "rxjs/operators";

@Injectable({
  providedIn: 'root'
})
export class RedirectIfSetupDoneGuard implements CanActivate {

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    return this.check();
  }

  check() {
    return this.api.isSetupDone().pipe(
      map(
        isSetupDone => {
          if (isSetupDone) return this.router.parseUrl('/');
          return true;
        }
      ),
      catchError(error => throwError(error))
    );
  }
}
