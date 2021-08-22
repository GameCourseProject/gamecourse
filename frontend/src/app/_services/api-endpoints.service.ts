import { Injectable } from '@angular/core';
import {UrlBuilder} from "../_utils/url-builder";
import {QueryStringParameters} from "../_utils/query-string-parameters";

@Injectable({
  providedIn: 'root'
})
export class ApiEndpointsService {

  // public readonly API_ENDPOINT: string = 'https://gamecourse/v2/api';
  public readonly API_ENDPOINT: string = 'http://localhost:8000/api';

  constructor() { }


  /*** --------------------------------------------- ***/
  /*** --------------- Course related -------------- ***/
  /*** --------------------------------------------- ***/

  public getSetupEndpoint(): string {
    return this.createUrl('setup');
  }


  /*** --------------------------------------------- ***/
  /*** --------------- Course related -------------- ***/
  /*** --------------------------------------------- ***/

  public getCoursesEndpoint(): string {
    return this.createUrl('courses');
  }


  /*** --------------------------------------------- ***/
  /*** ---------------- URL creator ---------------- ***/
  /*** --------------------------------------------- ***/

  // e.g. https://domain.com/api/news
  private createUrl(action: string): string {
    const urlBuilder: UrlBuilder = new UrlBuilder(this.API_ENDPOINT, action);
    return urlBuilder.toString();
  }

  // e.g. https://domain.com/api/productlist?countrycode=en&postalcode=12345
  private createUrlWithQueryParameters(
    action: string,
    queryStringHandler?: (queryStringParameters: QueryStringParameters) => void
  ): string {

    const urlBuilder: UrlBuilder = new UrlBuilder(this.API_ENDPOINT, action);

    // Push extra query string params
    if (queryStringHandler)
      queryStringHandler(urlBuilder.queryString);

    return urlBuilder.toString();
  }

  // e.g. https://domain.com/api/data/12/67
  private createUrlWithPathVariables(action: string, pathVariables: any[] = []): string {
    let encodedPathVariablesUrl: string = '';

    // Push extra path variables
    for (const pathVariable of pathVariables) {
      if (pathVariable !== null)
        encodedPathVariablesUrl += `/${encodeURIComponent(pathVariable.toString())}`;
    }

    const urlBuilder: UrlBuilder = new UrlBuilder(this.API_ENDPOINT, `${action}${encodedPathVariablesUrl}`);
    return urlBuilder.toString();
  }

  /**
   * For more info on how to use the URL creator check:
   * https://betterprogramming.pub/angular-api-calls-the-right-way-264198bf2c64
   */

}
