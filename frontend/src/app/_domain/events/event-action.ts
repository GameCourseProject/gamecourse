import {Event} from "./event";
import {EventType} from "./event-type";

export enum EventAction {
  GO_TO_PAGE = 'goToPage',
  HIDE_VIEW = 'hideView',
  SHOW_VIEW = 'showView',
  TOGGLE_VIEW = 'toggleView',
  SHOW_TOOLTIP = 'showToolTip', // TODO
  SHOW_POPUP = 'showPopUp'  // TODO
}

export function getEventFromAction(events: {[key in EventType]?: Event}, action: EventAction): Event {
  for (const event of Object.values(events)) {
    if (event.action === action) return event;
  }
  return null;
}
