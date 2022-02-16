import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor, HttpResponse
} from '@angular/common/http';
import {Observable, of} from 'rxjs';
import {share, tap} from "rxjs/operators";

@Injectable()
export class CacheInterceptor implements HttpInterceptor {

  private cache: Map<HttpRequest<any>['url'], HttpResponse<any>> = new Map<HttpRequest<any>['url'], HttpResponse<any>>();

  constructor() {}

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    console.log(request.url)
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

  reset(request: HttpRequest<any>) {
    // Clean cached data for all requests of the same module
    // TODO: find a better way to do it, while still being general enough

    const module = getUrlModule(request.url);
    this.cache.forEach((value, key, map) => {
      if (getUrlModule(key) === module)
        map.delete(key);
    });

    function getUrlModule(url: string): string {
      const matches = url.match(/\bmodule=(.+?(?=&))/g);
      if (!matches) return null;
      return matches[0].split("=")[1];
    }
  }
}
