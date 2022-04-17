import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor, HttpResponse
} from '@angular/common/http';
import {Observable, of, Subject} from 'rxjs';
import {share, take, tap} from "rxjs/operators";
import {ApiHttpService} from "../_services/api/api-http.service";

/**
 * This class is responsible for intercepting HTTP requests and caching them.
 * Caching requests reduces the nr. of requests made and improves performance.
 *
 * How does it work?
 * - POST requests are never cached, because they update information;
 * - GET requests are cached, because they only retrieve information without changes,
 *   and return the cached value if exists; otherwise makes the request and caches the value.
 * - If same GET request is done in a row, it waits for 1st response and returns it.
 * - Each request module has dependencies associated that tell which requests to delete from
 *   cache once changes are made.
 */
@Injectable()
export class CacheInterceptor implements HttpInterceptor {

  private cache: Map<HttpRequest<any>['url'], HttpResponse<any>> = new Map<HttpRequest<any>['url'], HttpResponse<any>>();

  private lastGetRequest: HttpRequest<any>['url'];    // last GET request actually made
  private lastGetRequestSubject: {[url: string]: Subject<HttpResponse<any>> } = {};

  private readonly dependencies: {[key: string]: string[]} = {};

  constructor() {
    this.dependencies[ApiHttpService.COURSE] = [ApiHttpService.COURSE, ApiHttpService.USER];
    this.dependencies[ApiHttpService.MODULE] = [ApiHttpService.MODULE, ApiHttpService.COURSE, ApiHttpService.VIEWS];
    this.dependencies[ApiHttpService.THEMES] = [ApiHttpService.THEMES];
    this.dependencies[ApiHttpService.USER] = [ApiHttpService.USER, ApiHttpService.COURSE, ApiHttpService.VIEWS];
    this.dependencies[ApiHttpService.VIEWS] = [ApiHttpService.VIEWS];
    // NOTE: add new dependencies here

    // Add modules with requests
    this.dependencies[ApiHttpService.MODULE] = this.dependencies[ApiHttpService.MODULE].concat([
      ApiHttpService.CLASSCHECK,
      ApiHttpService.FENIX,
      ApiHttpService.GOOGLESHEETS,
      ApiHttpService.MOODLE,
      ApiHttpService.NOTIFICATIONS,
      ApiHttpService.PROFILING,
      ApiHttpService.QR,
      ApiHttpService.QUEST,
      ApiHttpService.SKILLS,
      ApiHttpService.VIRTUAL_CURRENCY,
    ]);
  }

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    if (request.method !== 'GET') {
      this.lastGetRequest = null;
      this.resetCache(request);
      return next.handle(request);
    }

    const cachedResponse: HttpResponse<any> = this.cache.get(request.url);
    if (cachedResponse) {
      // Has request cached
      return of(cachedResponse.clone());

    } else if (this.lastGetRequest === request.url){
      // Same request in a row, answer w/ 1st response
      return this.lastGetRequestSubject[request.url].pipe( take(1) )

    } else {
      // Actually make the request
      this.lastGetRequest = request.url;
      this.lastGetRequestSubject[request.url] = new Subject<HttpResponse<any>>();

      return next.handle(request).pipe(
        tap(stateEvent => {
          if (stateEvent instanceof HttpResponse) {
            this.cache.set(request.url, stateEvent.clone());
            this.lastGetRequestSubject[request.url].next(stateEvent.clone());
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
  resetCache(request: HttpRequest<any>) {
    const module = getUrlModule(request.url);
    if (!module) return;

    if (module === ApiHttpService.CORE) {
      // If module core, invalidate whole cache
      // This is done because any module can potentially depend on core
      this.cache = new Map<HttpRequest<any>['url'], HttpResponse<any>>();
      return;
    }

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
