import {Moment} from "moment";

// NOTE: sort --> 1: ascending, -1: descending

export function orderByString(a: string, b: string, sort: number = 1) {
  if (a === null) return sort;
  if (b === null) return -1 * sort;
  return a.localeCompare(b) * sort;
}

export function orderByNumber(a: number, b: number, sort: number = 1) {
  if (a === null) return sort;
  if (b === null) return -1 * sort;
  return (a - b) * sort;
}

export function orderByDate(a: Moment, b: Moment, sort: number = 1) {
  if (a === null || a.isSameOrBefore(b)) return sort;
  else return -1 * sort;
}
