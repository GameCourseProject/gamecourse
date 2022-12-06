import {Rule} from "./rule";
import {LoadingState} from "../modules/module";
import {RuleSection} from "./RuleSection";

export class CourseRule extends Rule {
  private _isActiveInCourse: boolean;

  private static _activityRefreshState: Map<number, LoadingState> = new Map<number, LoadingState>();

  constructor(id: number, name: string, section: RuleSection, isActiveInCourse: boolean) {
    super(id, name, section);

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
      obj.name,
      obj.section,
      obj.isActiveInCourse
    );
  }
}

interface CourseRuleDatabase {
  "id" : number,
  "name" : string,
  "section" : RuleSection,
  "isActiveInCourse": boolean
}
