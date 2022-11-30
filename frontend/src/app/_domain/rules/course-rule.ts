import {Rule} from "./rule";
import {LoadingState} from "../modules/module";

export class CourseRule extends Rule {
  private _isActiveInCourse: boolean;

  private static _activityRefreshState: Map<number, LoadingState> = new Map<number, LoadingState>();

  constructor(id: number, name: string, isActiveInCourse: boolean) {
    super(id, name);

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
      obj.isActiveInCourse
    );
  }
}

interface CourseRuleDatabase {
  "id" : number,
  "name" : string,
  "isActiveInCourse": boolean
}
