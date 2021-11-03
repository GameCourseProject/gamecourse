import {View} from "../../_domain/views/view";
import {ErrorService} from "../../_services/error.service";
import * as moment from 'moment';
import {Moment} from "moment/moment";

export function requireValues(view: View, values: any[]) {
  values.forEach(value => {
    if (!exists(value))
      ErrorService.set('Error: View ' + view.type + ' doesn\'t have all required fields. View: ' + view);
  });
}

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

export function dateFromDatabase(date: string): Moment {
  const FORMAT = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/g;
  return FORMAT.test(date) ? moment(date).subtract(1, 'hours') : null; // FIXME: check utc and local
}
