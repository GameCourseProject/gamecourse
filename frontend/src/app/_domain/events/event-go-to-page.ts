import {Event} from "./event";
import {EventType} from "./event-type";
import {EventAction} from "./event-action";
import {exists} from "../../_utils/misc/misc";

export class EventGoToPage extends Event {
  private _pageId: number;
  private _userId?: number;

  constructor(type: EventType, pageId: number, userId?: number) {
    super(type, EventAction.GO_TO_PAGE);
    this.pageId = pageId;
    if (exists(userId)) this.userId = userId;
  }

  get pageId(): number {
    return this._pageId;
  }

  set pageId(value: number) {
    this._pageId = value;
  }

  get userId(): number {
    return this._userId;
  }

  set userId(value: number) {
    this._userId = value;
  }

  print(): string {
    return EventAction.GO_TO_PAGE + '(' + this.pageId + (this.userId ? ',' + this.userId : '') + ')';
  }
}
