import { ApiHttpService } from "../../_services/api/api-http.service";
import { RuleSection } from "./RuleSection";
import {RuleTag} from "./RuleTag";

export class Rule {
  private _id: number;
  private _sectionId: number;
  private _name: string;
  private _description: string;
  private _when: string;
  private _then: string;
  private _position: number;
  private _isActive: boolean;
  private _tags: RuleTag[];

  constructor(id: number, sectionID: number, name: string, description: string, when: string, then: string,
              position: number, isActive: boolean, tags: RuleTag[]) {
    this._id = id;
    this._sectionId = sectionID;
    this._name = name;
    this._description = description;
    this._when = when;
    this._then = then;
    this._position = position;
    this._isActive = isActive;
    this._tags = tags;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get sectionId(): number {
    return this._sectionId;
  }

  set sectionId(value: number) {
    this._sectionId = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get description(): string {
    return this._description;
  }

  set description(value: string) {
    this._description = value;
  }

  get when(): string {
    return this._when;
  }

  set when(value: string) {
    this._when = value;
  }

  get then(): string {
    return this._then;
  }

  set then(value: string) {
    this._then = value;
  }

  get position(): number {
    return this._position;
  }

  set position(value: number) {
    this._position = value;
  }

  get isActive(): boolean {
    return this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  get tags(): RuleTag[] {
    return this._tags;
  }

  set tags(value: RuleTag[]) {
    this._tags = value;
  }

  static fromDatabase(obj: RuleDatabase): Rule {
    return new Rule(
      obj.id,
      obj.sectionId,
      obj.name,
      obj.description,
      obj.when,
      obj.then,
      obj.position,
      obj.isActive,
      obj.tags
    );
  }
}

interface RuleDatabase {
  id: number,
  sectionId: number,
  name: string,
  description: string,
  when: string,
  then: string,
  position: number,
  isActive: boolean,
  tags: RuleTag[]
}
