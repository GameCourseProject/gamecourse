import {Event} from "./event";
import {EventType} from "./event-type";
import {EventAction} from "./event-action";
import {EventGoToPage} from "./event-go-to-page";
import {EventHideView} from "./event-hide-view";
import {EventShowView} from "./event-show-view";
import {EventToggleView} from "./event-toggle-view";

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
export function buildEvent(type: EventType, eventStr: string): Event {
  eventStr = eventStr.replace(/([{}]|\bactions.\b)/g, '');
  const action = eventStr.split('(')[0];
  const args = eventStr.split('(')[1].split(',').map(arg => arg.noWhiteSpace().replace(')', ''));

  // FIXME: remove unused args from backend
  if (action === EventAction.GO_TO_PAGE) return new EventGoToPage(type, parseInt(args[0]), args.length === 2 ? parseInt(args[1]) : null);
  else if (action === EventAction.HIDE_VIEW) return new EventHideView(type, parseInt(args[0]));
  else if (action === EventAction.SHOW_VIEW) return new EventShowView(type, parseInt(args[0]));
  else if (action === EventAction.TOGGLE_VIEW) return new EventToggleView(type, parseInt(args[0]));

  return null;
}
