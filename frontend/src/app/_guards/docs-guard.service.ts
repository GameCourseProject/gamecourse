import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot, CanActivate,
  Router, RouterStateSnapshot,
  UrlTree
} from '@angular/router';
import {Observable} from 'rxjs';
import {ApiHttpService} from "../_services/api/api-http.service";

@Injectable({
  providedIn: 'root'
})
export class DocsGuard implements CanActivate {

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    return this.check();
  }

  async check() {
    const user = await this.api.getLoggedUser().toPromise();
    const isATeacher = await this.api.isATeacher(user.id).toPromise();

    if (user.isAdmin || isATeacher) return true;
    else return this.router.parseUrl('/no-access');
  }
}
