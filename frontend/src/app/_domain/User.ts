import {ObjectKeysConverter} from "../_utils/object-keys-converter";
import {TypesConverter} from "../_utils/types-converter";

export class User {
  private _id: number;
  private _name: string;
  private _email: string;
  private _major: string;
  private _nickname: string;
  private _studentNumber: number;
  private _isAdmin: boolean;
  private _isActive: boolean;
  private _createdAt: Date;
  private _updatedAt: Date;

  constructor(source: Partial<User>) {
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

  get email(): string {
    return this._email;
  }

  set email(value: string) {
    this._email = value;
  }

  get major(): string {
    return this._major;
  }

  set major(value: string) {
    this._major = value;
  }

  get nickname(): string {
    return this._nickname;
  }

  set nickname(value: string) {
    this._nickname = value;
  }

  get studentNumber(): number {
    return this._studentNumber;
  }

  set studentNumber(value: number) {
    this._studentNumber = value;
  }

  get isAdmin(): boolean {
    return !!this._isAdmin;
  }

  set isAdmin(value: boolean) {
    this._isAdmin = value;
  }

  get isActive(): boolean {
    return !!this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
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
}
