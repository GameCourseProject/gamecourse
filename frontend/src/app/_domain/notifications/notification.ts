import { Moment } from "moment";
import { dateFromDatabase } from "src/app/_utils/misc/misc";

export class Notification{
  private _id: number;
  private _course: number;
  private _user: number;
  private _message: string;
  private _isShowed: boolean;
  private _dateCreated: Moment;


  constructor(id: number, course: number, user: number, message: string, isShowed: boolean, dateCreated: Moment) {
    this._id = id;
    this._course = course;
    this._user = user;
    this._message = message;
    this._isShowed = isShowed;
    this._dateCreated = dateCreated;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get course(): number {
    return this._course;
  }

  set course(value: number) {
    this._course = value;
  }

  get user(): number {
    return this._user;
  }

  set user(value: number) {
    this._user = value;
  }

  get message(): string {
    return this._message;
  }

  set message(value: string) {
    this._message = value;
  }

  get isShowed(): boolean {
    return this._isShowed;
  }

  set isShowed(value: boolean) {
    this._isShowed = value;
  }

  get dateCreated(): Moment {
    return this._dateCreated;
  }

  set dateCreated(value: Moment) {
    this._dateCreated = value;
  }

  static fromDatabase(obj: NotificationDatabase): Notification {
    return new Notification(
      obj.id,
      obj.course,
      obj.user,
      obj.message,
      obj.isShowed == "0" ? false : true,
      obj.dateCreated ? dateFromDatabase(obj.dateCreated) : null,
    );
  }
}

interface NotificationDatabase {
  id: number,
  course: number,
  user: number,
  message: string,
  isShowed: string,
  dateCreated: string
}
