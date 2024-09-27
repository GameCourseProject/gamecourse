import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from "@angular/core";
import {
  SkillManageData
} from "../../../_views/restricted/courses/course/settings/modules/config/personalized-config/skills/skills.component";
import {Skill} from "../../../_domain/modules/config/personalized-config/skills/skill";
import * as _ from 'lodash';
import {clearEmptyValues} from "../../../_utils/misc/misc";
import {ModalService} from "../../../_services/modal.service";
import {AlertService, AlertType} from "../../../_services/alert.service";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {NgForm} from "@angular/forms";

@Component({
  selector: 'app-skill-editor',
  templateUrl: './skill-editor.component.html'
})
export class SkillEditorComponent implements OnInit {

  @Input() courseID: number;
  @Input() courseFolder: string;
  @Input() skill: Skill;

  @Output() onClose: EventEmitter<void> = new EventEmitter();

  @ViewChild('fSkill', { static: false }) fSkill: NgForm;

  loading: boolean = false;
  skillPageMode: 'editor' | 'preview' = 'editor';
  skillToManage: SkillManageData;

  constructor(
    private api: ApiHttpService,
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
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/
  async onSubmit(): Promise<void> {
    if (this.fSkill.valid) {
      this.loading = true;

      const skillToEdit = await this.api.getSkillById(this.skillToManage.id).toPromise();
      skillToEdit.name = this.skillToManage.name;
      skillToEdit.color = this.skillToManage.color;
      skillToEdit.page = this.skillToManage.page;

      await this.api.editSkill(this.courseID, clearEmptyValues(skillToEdit)).toPromise();
      ModalService.closeModal('skill-manage');
      AlertService.showAlert(AlertType.SUCCESS, 'Skill \'' + this.skillToManage.name + '\' edited');
      this.reset();

      this.loading = false;

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  reset() {
    this.onClose.emit();
  }

}
