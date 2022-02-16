import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor, HttpResponse
} from '@angular/common/http';
import {Observable, of} from 'rxjs';
import {share, tap} from "rxjs/operators";
import {ApiHttpService} from "../_services/api/api-http.service";

/**
 * This class is responsible for intercepting HTTP requests and caching them.
 * Caching requests reduces the nr. of requests made and improves performance.
 *
 * How does it work?
 * - POST requests are never cached, because they update information;
 * - GET requests are cached, because they only retrieve information without changes,
 *   and return the cached value if exists; otherwise makes the requests and caches the value.
 * - Each request module has dependencies associated that tell which request to delete from
 *   cache once changes are made.
 */
@Injectable()
export class CacheInterceptor implements HttpInterceptor {

  private cache: Map<HttpRequest<any>['url'], HttpResponse<any>> = new Map<HttpRequest<any>['url'], HttpResponse<any>>();

  private readonly dependencies: {[key: string]: string[]} = {};

  constructor() {
    this.dependencies[ApiHttpService.COURSE] = [ApiHttpService.COURSE];
    this.dependencies[ApiHttpService.MODULE] = [ApiHttpService.MODULE, ApiHttpService.COURSE];
    this.dependencies[ApiHttpService.THEMES] = [ApiHttpService.THEMES];
    this.dependencies[ApiHttpService.USER] = [ApiHttpService.USER, ApiHttpService.COURSE];
    this.dependencies[ApiHttpService.VIEWS] = [ApiHttpService.VIEWS];
    // NOTE: add new dependencies here
  }

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    if (request.method !== 'GET') {
      this.reset(request);
      return next.handle(request);
    }

    const cachedResponse: HttpResponse<any> = this.cache.get(request.url);
    if (cachedResponse) {
      return of(cachedResponse.clone());

    } else {
      return next.handle(request).pipe(
        tap(stateEvent => {
          if (stateEvent instanceof HttpResponse) {
            this.cache.set(request.url, stateEvent.clone())
          }
        }),
        share()
      )
    }
  }

  /**
   * Cleans cached data for all module dependencies of request.
   *
   * @param request
   */
  reset(request: HttpRequest<any>) {
    const module = getUrlModule(request.url);
    if (!module) return;

    const dependencies = this.dependencies[module] || [module].concat(this.dependencies[ApiHttpService.MODULE]);
    this.cache.forEach((value, key, map) => {
      if (dependencies.includes(getUrlModule(key)))
        map.delete(key);
    });

    function getUrlModule(url: string): string {
      const matches = url.match(/\bmodule=(.+?(?=&))/g);
      if (!matches) return null;
      return matches[0].split("=")[1];
    }
  }
}
