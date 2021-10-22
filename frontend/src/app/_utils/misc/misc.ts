import {View} from "../../_domain/views/view";
import {ErrorService} from "../../_services/error.service";

export function requireValues(view: View, values: any[]) {
  values.forEach(value => {
    if (value === null || value === undefined)
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
