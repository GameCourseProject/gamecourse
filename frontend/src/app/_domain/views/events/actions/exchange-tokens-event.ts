import {Event} from "../event";
import {EventType} from "../event-type";
import {EventAction} from "../event-action";

export class ExchangeTokensEvent extends Event {
  private _userId: number;
  private _ratio: string;
  private _threshold: number;
  private _extra: boolean;

  constructor(type: EventType, expression: string, userId: number, ratio: string, threshold: number | null, extra: boolean) {
    super(type, EventAction.EXCHANGE_TOKENS, expression);
    this.userId = userId;
    this.ratio = ratio;
    this.threshold = threshold;
    this.extra = extra;
  }

  get extra(): boolean {
    return this._extra;
  }

  set extra(value: boolean) {
    this._extra = value;
  }
  get threshold(): number {
    return this._threshold;
  }

  set threshold(value: number) {
    this._threshold = value;
  }
  get ratio(): string {
    return this._ratio;
  }

  set ratio(value: string) {
    this._ratio = value;
  }
  get userId(): number {
    return this._userId;
  }

  set userId(value: number) {
    this._userId = value;
  }

  print(): string {
    const args: string[] = [this.userId.toString(), this.ratio, this.threshold.toString(), this.extra.toString()];
    return '{vc.exchangeTokensForXP(' + args.join(', ') + ')}';
  }
}
