import * as moment from 'moment';
import {Moment} from "moment/moment";

export function exists(value: any): boolean {
  return value !== null && value !== undefined;
}

export function objectMap(obj, fn) {
  return Object.fromEntries(
    Object.entries(obj).map(
      ([k, v], i) => [k, fn(v, k, i)]
    )
  );
}

export function copyObject(obj: any) {
  return Object.assign(Object.create(Object.getPrototypeOf(obj)), obj);
}

export function clearEmptyValues(obj) {
  for (const key of Object.keys(obj)) {
    if (typeof obj[key] === 'string' && (obj[key] as string).isEmpty())
      obj[key] = null;
  }
  return obj;
}

export function dateFromDatabase(date: string): Moment {
  const FORMAT = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/g;
  return FORMAT.test(date) ? moment(date) : null;
}
