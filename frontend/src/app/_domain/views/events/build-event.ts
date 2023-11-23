import {Event} from "./event";
import {EventType} from "./event-type";
import {EventAction} from "./event-action";
import {GoToPageEvent} from "./actions/go-to-page-event";
import {ShowTooltipEvent} from "./actions/show-tooltip-event";

/**
 * Builds an event from its type and description according to whatever
 * action the event is.
 *
 * This function needs to be outside event.ts so as not to create a
 * Circular Dependency between Event and its children.
 * @link https://stackoverflow.com/questions/49727530/how-to-move-typescript-classes-with-circular-dependency-into-separate-files
 *
 * @param type
 * @param eventStr
 * @private
 */
export function buildEvent(type: EventType, expression: string): Event {
  const eventStr = expression.replace(/[{}]|\bactions.\b/g, '');
  const action: EventAction = eventStr.split('(')[0] as EventAction;
  const args: string[] = eventStr.split('(')[1].split(',').map(arg => arg.trim().replace(')', ''));

  if (action === EventAction.GO_TO_PAGE) return new GoToPageEvent(type, expression, args[0], args[1] || null);
  if (action === EventAction.SHOW_TOOLTIP) return new ShowTooltipEvent(type, expression, args[0], args[1]);
  // NOTE: insert here other types of event actions

  return null;
}
