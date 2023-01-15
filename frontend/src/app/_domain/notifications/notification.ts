export class Notification{
  private _id: number;
  private _course: number;
  private _user: number;
  private _message: string;
  private _isShowed: boolean;


  constructor(id: number, course: number, user: number, message: string, isShowed: boolean) {
    this._id = id;
    this._course = course;
    this._user = user;
    this._message = message;
    this._isShowed = isShowed;
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

  static fromDatabase(obj: NotificationDatabase): Notification {
    return new Notification(
      obj.id,
      obj.course,
      obj.user,
      obj.message,
      obj.isShowed
    );
  }
}

interface NotificationDatabase {
  id: number,
  course: number,
  user: number,
  message: string
  isShowed: boolean
}
