export class JourneyPath {
  private _id: number;
  private _courseID: number;
  private _name: string;
  private _color: string;
  private _isActive: boolean;

  constructor(id: number, courseID: number, name: string, color: string, isActive: boolean) {
    this._id = id;
    this._courseID = courseID;
    this._name = name;
    this._color = color;
    this._isActive = isActive;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get courseID(): number {
    return this._courseID;
  }

  set courseID(value: number) {
    this._courseID = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get color(): string {
    return this._color;
  }

  set color(value: string) {
    this._color = value;
  }

  get isActive(): boolean {
    return this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    return {
      id: this.id,
      course: this.courseID,
      name: this.name,
      color: this.color,
      isActive: this.isActive
    };
  }

  static fromDatabase(obj: JourneyPathDatabase): JourneyPath {
    return new JourneyPath(
      obj.id,
      obj.course,
      obj.name,
      obj.color,
      obj.isActive
    );
  }
}

interface JourneyPathDatabase {
  id: number,
  course: number,
  name: string,
  color: string,
  isActive: boolean
}
