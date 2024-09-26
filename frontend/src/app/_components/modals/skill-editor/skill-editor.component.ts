import {Component, Input, OnInit} from "@angular/core";
import {
  SkillManageData
} from "../../../_views/restricted/courses/course/settings/modules/config/personalized-config/skills/skills.component";
import {Skill} from "../../../_domain/modules/config/personalized-config/skills/skill";
import * as _ from 'lodash';

@Component({
  selector: 'app-skill-editor',
  templateUrl: './skill-editor.component.html'
})
export class SkillEditorComponent implements OnInit {

  @Input() courseFolder: string;
  @Input() skill: Skill;
  @Input() loading: boolean = false;

  skillPageMode: 'editor' | 'preview' = 'editor';
  skillToManage: SkillManageData;
  onSubmit: () => void;
  reset: () => void;


  constructor(
  ) { }

  ngOnInit(): void {
    this.skillToManage = this.initSkillToManage(this.skill);
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  initSkillToManage(skill?: Skill): SkillManageData {
    const skillData: SkillManageData = {
      tierID: skill?.tierID ? 'id-' + skill.tierID : null,
      name: skill?.name ?? null,
      color: skill?.color ?? null,
      page: skill?.page ?? null,
      dependencies: skill?.dependencies ? _.cloneDeep(skill.dependencies) : []
    };
    if (skill) {
      skillData.id = skill.id;
      skillData.ruleID = skill.ruleID;
    }
    return skillData;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

}
