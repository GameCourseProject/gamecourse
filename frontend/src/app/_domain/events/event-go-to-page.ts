import {Event} from "./event";
import {EventType} from "./event-type";
import {EventAction} from "./event-action";
import {exists} from "../../_utils/misc/misc";

export class EventGoToPage extends Event {
  private _pageId: string;
  private _userId?: string;

  constructor(type: EventType, pageId: string, userId?: string) {
    super(type, EventAction.GO_TO_PAGE);
    this.pageId = pageId;
    if (exists(userId)) this.userId = userId;
  }

  get pageId(): string {
    return this._pageId;
  }

  set pageId(value: string) {
    this._pageId = value;
  }

  get userId(): string {
    return this._userId;
  }

  set userId(value: string) {
    this._userId = value;
  }

  print(): string {
    return EventAction.GO_TO_PAGE + '(' + this.pageId + (this.userId ? ', ' + this.userId : '') + ')';
  }
}
