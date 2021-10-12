import {ErrorService} from "../../_services/error.service";

export function requireValues(values: any[]) {
  const varToString = varObj => Object.keys(varObj)[0];
  values.forEach(value => {
    if (value === null || value === undefined)
      ErrorService.set('Error: View requires \'' + varToString(value) + '\'.');
  });
}
