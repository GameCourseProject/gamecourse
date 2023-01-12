export class Notification{
  private _id: number;
  private _course: number;
  private _message: string;


  constructor(id: number, course: number, message: string) {
    this._id = id;
    this._course = course;
    this._message = message;
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

  get message(): string {
    return this._message;
  }

  set message(value: string) {
    this._message = value;
  }

  static fromDatabase(obj: NotificationDatabase): Notification {
    return new Notification(
      obj.id,
      obj.course,
      obj.message
    );
  }
}

interface NotificationDatabase {
  id: number,
  course: number,
  message: string
}
