import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, RouterStateSnapshot, UrlTree } from '@angular/router';
import { Observable } from 'rxjs';
import { DomSanitizer } from "@angular/platform-browser";

import {LoadingState, Module} from "../_domain/modules/module";

@Injectable({
  providedIn: 'root'
})
export class LoadModuleStylesGuard implements CanActivate {

  constructor(private sanitizer: DomSanitizer) { }

  // Load course modules' styles, if not already loaded
  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    const courseID = parseInt(route.params['id']);

    if (!Module.stylesLoaded.has(courseID) || Module.stylesLoaded.get(courseID).state === LoadingState.NOT_LOADED)
      Module.loadStyles(courseID, this.sanitizer);

    return true;
  }
}
