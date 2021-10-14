import {View} from "../../_domain/views/view";
import {ErrorService} from "../../_services/error.service";

export function requireValues(view: View, values: any[]) {
  values.forEach(value => {
    if (value === null || value === undefined)
      ErrorService.set('Error: View ' + view.type + ' doesn\'t have all required fields. View: ' + view);
  });
}
