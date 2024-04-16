import {Event} from "../event";
import {EventType} from "../event-type";
import {EventAction} from "../event-action";
import {exists} from "../../../../_utils/misc/misc";

export class GoToPageEvent extends Event {
  private _pageId: string;
  private _userId?: string;
  private _isSkill?: boolean;

  constructor(type: EventType, expression: string, pageId: string, userId?: string, isSkill?: boolean) {
    super(type, EventAction.GO_TO_PAGE, expression);
    this.pageId = pageId;
    if (exists(userId)) this.userId = userId;
    if (exists(isSkill)) this.isSkill = isSkill;
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

  get isSkill(): boolean {
    return this._isSkill;
  }

  set isSkill(value: boolean) {
    this._isSkill = value;
  }


  print(): string {
    const args: (string | boolean)[] = [this.pageId];
    if (this.userId) args.push(this.userId);
    if (this.isSkill) args.push(this.isSkill);
    return this.printHelper(args);
  }
}
