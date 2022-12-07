import {Rule} from "./rule";
import {LoadingState} from "../modules/module";
import {RuleSection} from "./RuleSection";
import {RuleTag} from "./RuleTag";

export class CourseRule extends Rule {
  private _isActiveInCourse: boolean;

  private static _activityRefreshState: Map<number, LoadingState> = new Map<number, LoadingState>();

  constructor(id: number, sectionId: number, name: string, description: string,
              when: string, then: string, position: number, isActive: boolean, tags: RuleTag[], isActiveInCourse: boolean) {
    super(id, sectionId, name, description, when, then, position, isActive, tags);

    this._isActiveInCourse = isActiveInCourse;
  }

  get isActiveInCourse(): boolean {
    return this._isActiveInCourse;
  }

  set isActiveInCourse(value: boolean) {
    this._isActiveInCourse = value;
  }


  static fromDatabase(obj: CourseRuleDatabase): CourseRule {
    return new CourseRule(
      obj.id,
      obj.sectionId,
      obj.name,
      obj.description,
      obj.when,
      obj.then,
      obj.position,
      obj.isActive,
      obj.tags,
      obj.isActiveInCourse
    );
  }
}

interface CourseRuleDatabase {
  "id" : number,
  "sectionId" : number,
  "name" : string,
  "description" : string,
  "when" : string,
  "then" : string,
  "position" : number,
  "isActive" : boolean,
  "tags": RuleTag[],
  "isActiveInCourse": boolean
}
