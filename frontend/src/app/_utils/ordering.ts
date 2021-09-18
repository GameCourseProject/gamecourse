import {Moment} from "moment";

/**
 * This class has utility functions to order items of various types.
 * @summary sort --> 1: ascending, -1: descending
 */
export class Ordering {

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
