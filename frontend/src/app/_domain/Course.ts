import {ObjectKeysConverter} from "../_utils/object-keys-converter";
import {TypesConverter} from "../_utils/types-converter";

export class Course {
  private _id: number;
  private _name: string;
  private _short: string;
  private _color: string;
  private _year: string;
  private _defaultLandingPage: string;
  private _isActive: boolean;
  private _isVisible: boolean;
  private _roleHierarchy: string;
  private _theme: string;
  private _createdAt: Date;
  private _updatedAt: Date;

  // Extras: not in database
  private _numberOfStudents?: number;

  constructor(source: Partial<Course>) {
    const keysConverter = new ObjectKeysConverter();
    source = keysConverter.keysToCamelCase(source);

    const typesConverter = new TypesConverter();
    Object.keys(source).forEach(key => {
      if (source.hasOwnProperty(key)) {
        this[key] = typesConverter.fromDatabase(source[key]);
      }
    });

    return this;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get short(): string {
    return this._short;
  }

  set short(value: string) {
    this._short = value;
  }

  get color(): string {
    return this._color;
  }

  set color(value: string) {
    this._color = value;
  }

  get year(): string {
    return this._year;
  }

  set year(value: string) {
    this._year = value;
  }

  get defaultLandingPage(): string {
    return this._defaultLandingPage;
  }

  set defaultLandingPage(value: string) {
    this._defaultLandingPage = value;
  }

  get isActive(): boolean {
    return !!this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  get isVisible(): boolean {
    return !!this._isVisible;
  }

  set isVisible(value: boolean) {
    this._isVisible = value;
  }

  get roleHierarchy(): string {
    return this._roleHierarchy;
  }

  set roleHierarchy(value: string) {
    this._roleHierarchy = value;
  }

  get theme(): string {
    return this._theme;
  }

  set theme(value: string) {
    this._theme = value;
  }

  get createdAt(): Date {
    return this._createdAt;
  }

  set createdAt(value: Date) {
    this._createdAt = value;
  }

  get updatedAt(): Date {
    return this._updatedAt;
  }

  set updatedAt(value: Date) {
    this._updatedAt = value;
  }

  get numberOfStudents(): number {
    return this._numberOfStudents;
  }

  set numberOfStudents(value: number) {
    this._numberOfStudents = value;
  }
}
