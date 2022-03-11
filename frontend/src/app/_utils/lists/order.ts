import {Moment} from "moment";

export enum Sort {
  ASCENDING = 1,
  DESCENDING = -1
}

/**
 * This class is responsible for ordering a list of items.
 * It also has utility functions to order items of various types.
 */
export class Order {

  private _active: {orderBy: string, sort: number};    // Order selected

  get active(): { orderBy: string; sort: number } {
    return this._active;
  }

  set active(value: { orderBy: string; sort: number }) {
    this._active = value;
  }

  public static byString(a: string, b: string, sort: number = 1): number {
    if (a === null) return sort;
    if (b === null) return -1 * sort;
    return a.localeCompare(b) * sort;
  }

  public static byNumber(a: number, b: number, sort: number = 1): number {
    if (a === null) return sort;
    if (b === null) return -1 * sort;
    return (a - b) * sort;
  }

  public static byDate(a: Moment, b: Moment, sort: number = 1): number {
    if (a === null || a.isSameOrBefore(b)) return sort;
    else return -1 * sort;
  }
}
