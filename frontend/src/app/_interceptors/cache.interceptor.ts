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
  private queue: Map<HttpRequest<any>['url'], Subject<HttpResponse<any>>> = new Map<HttpRequest<any>["url"], Subject<HttpResponse<any>>>();

  private readonly dependencies: { [key: string]: string[] } = {};

  constructor() {
    // Which caches to invalidate when a POST is done on each module FIXME: review
    this.dependencies[ApiHttpService.AUTOGAME] = [ApiHttpService.COURSE];
    this.dependencies[ApiHttpService.COURSE] = [ApiHttpService.USER, ApiHttpService.MODULE];
    this.dependencies[ApiHttpService.DOCS] = [];
    this.dependencies[ApiHttpService.MODULE] = [ApiHttpService.COURSE, ApiHttpService.VIEWS];
    this.dependencies[ApiHttpService.THEME] = [];
    this.dependencies[ApiHttpService.USER] = [ApiHttpService.COURSE, ApiHttpService.VIEWS];
    this.dependencies[ApiHttpService.VIEWS] = [];
    // NOTE: add new dependencies here

    // Add modules with requests
    this.dependencies[ApiHttpService.MODULE] = this.dependencies[ApiHttpService.MODULE].concat([
      ApiHttpService.FENIX,
      ApiHttpService.GOOGLESHEETS,
      ApiHttpService.NOTIFICATIONS,
      ApiHttpService.PROFILING,
      ApiHttpService.QR,
      ApiHttpService.SKILLS,
    ]);
  }

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    if (request.method !== 'GET') {
      this.resetCache(request);
      return next.handle(request);
    }

    const cachedResponse: HttpResponse<any> = this.cache.get(request.url);
    const hasModule = !!CacheInterceptor.getUrlModule(request.url);

    if (cachedResponse) {
      // Has request cached
      return of(cachedResponse.clone());

    } else if (hasModule && this.queue.has(request.url)) {
      // Request is already being processed, answer w/ 1st response received
      return this.queue.get(request.url).pipe(take(1));

    } else {
      // Add request to queue
      if (!this.queue.has(request.url))
        this.queue.set(request.url, new Subject<HttpResponse<any>>());

      // Actually make the request
      return next.handle(request).pipe(
        tap(stateEvent => {
          if (stateEvent instanceof HttpResponse) {
            if (hasModule) this.cache.set(request.url, stateEvent.clone());

            // Trigger simultaneous requests waiting for the response
            this.queue.get(request.url).next(stateEvent.clone());

            // Remove request from queue
            this.queue.delete(request.url);
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
    const module = CacheInterceptor.getUrlModule(request.url);
    if (!module) return;

    if (module === ApiHttpService.CORE) {
      // If module core, invalidate whole cache
      // This is done because any module can potentially depend on core
      this.cache = new Map<HttpRequest<any>['url'], HttpResponse<any>>();
      return;
    }

    const dependencies = [module].concat(this.dependencies[module] || this.dependencies[ApiHttpService.MODULE]);
    this.cache.forEach((value, key, map) => {
      if (dependencies.includes(CacheInterceptor.getUrlModule(key)))
        map.delete(key);
    });
  }

  private static getUrlModule(url: string): string {
    const matches = url.match(/\bmodule=(.+?(?=&))/g);
    if (!matches) return null;
    return matches[0].split("=")[1];
  }
}
