import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot, CanActivate,
  Router, RouterStateSnapshot,
  UrlTree
} from '@angular/router';
import {Observable, throwError} from 'rxjs';
import {ApiHttpService} from "../_services/api/api-http.service";
import {catchError, map} from "rxjs/operators";

@Injectable({
  providedIn: 'root'
})
export class TeacherGuard implements CanActivate {

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
    return this.api.getLoggedUser().pipe(
      map(user => {
        const isATeacher = this.api.isATeacher(user.id).toPromise();
        if (isATeacher) return true;
        else return this.router.parseUrl('/no-access');
      } ),
      catchError(error => throwError(error))
    );
  }
}
