/**
 * This class gets a query string key-value set and encodes the value.
 * It also returns all the query string key-value sets in a string with the & character:
 * e.g., id=3&type=customer.
 */
export class QueryStringParameters {

  private paramsAndValues: string[];

  constructor() {
    this.paramsAndValues = [];
  }

  public push(key: string, value: Object): void {
    value = encodeURIComponent(value.toString());
    this.paramsAndValues.push([key, value].join('='));
  }

  public toString = (): string => this.paramsAndValues.join('&');

}
