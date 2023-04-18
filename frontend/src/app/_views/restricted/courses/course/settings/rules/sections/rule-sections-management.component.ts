import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from "@angular/core";
import {RuleSection} from "../../../../../../../_domain/rules/RuleSection";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";
import {CdkDragDrop, moveItemInArray} from "@angular/cdk/drag-drop";
import {Action} from "../../../../../../../_domain/modules/config/Action";
import {ModalService} from "../../../../../../../_services/modal.service";
import {Course} from "../../../../../../../_domain/courses/course";
import {SectionManageData} from "../rules.component";
export {SectionManageData} from "../rules.component";

@Component({
  selector: 'app-rule-sections-management',
  templateUrl: './rule-sections-management.component.html',
  styleUrls: ['rule-sections-management.component.scss']
})
export class RuleSectionsManagementComponent implements OnInit{

  @Input() course: Course;
  @Input() sections: RuleSection[];
  @Input() sectionToManage: SectionManageData;
  @Input() mode: 'add section' | 'edit section' | 'remove section' | 'manage sections priority';

  @Input() sectionActions: {action: Action | string, icon?: string, outline?: boolean, dropdown?: {action: Action | string, icon?: string}[],
    color?: "ghost" | "primary" | "secondary" | "accent" | "neutral" | "info" | "success" | "warning" | "error", disable?: boolean}[];

  @Output() newSections = new EventEmitter<RuleSection[]>();
  @Output() newSectionActions = new EventEmitter<any[]>();

  loading = {
    action: false
  };

  @ViewChild('s', {static: false}) s: NgForm;       // section form

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
  ) { }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  ngOnInit(): void {
    this.route.parent.params.subscribe();
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async doAction(action: string): Promise<void>{
    if (action === 'add section'){
      await this.createSection()
    } else if (action === 'edit section'){
      await this.editSection();
    } else if (action === 'remove section'){
      await this.removeSection();
    }
  }

  async createSection(): Promise<void> {
    if (this.s.valid) {
      this.loading.action = true;

      // create position --- NEEDS ABSTRACTION
      this.sectionToManage.position = this.sections.length + 1;

      const newSection = await this.api.createSection(clearEmptyValues(this.sectionToManage)).toPromise();
      this.sections.push(newSection);
      //this.buildTable();

      this.toggleSectionPriority();

      this.loading.action = false;

      this.resetSectionManage();

      console.log("Section management :", this.sections);
      this.newSections.emit(this.sections);
      this.newSectionActions.emit(this.sectionActions);

      AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + newSection.name + '\' added');
      ModalService.closeModal('manage-section');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editSection(): Promise<void>{
    if (this.s.valid) {
      this.loading.action = true;

      const sectionEdited = await this.api.editSection(clearEmptyValues(this.sectionToManage)).toPromise();
      const index = this.sections.findIndex(section => section.id === sectionEdited.id);
      this.sections.splice(index, 1, sectionEdited);

      this.newSections.emit(this.sections);
      //this.newSectionActions.emit(this.sectionActions);

      this.loading.action = false;
      AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + sectionEdited.name + '\' edited');

      ModalService.closeModal('manage-section');
      this.resetSectionManage();

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async removeSection(): Promise<void> {
    this.loading.action = true;

    //const rules = await this.api.getRulesOfSection(this.course.id,this.sectionToManage.id).toPromise();
    await this.api.deleteSection(this.sectionToManage.id).toPromise();

    const index = this.sections.findIndex(el => el.id === this.sectionToManage.id);
    this.sections.removeAtIndex(index);

    this.toggleSectionPriority();

    this.loading.action = false;
    ModalService.closeModal('remove section ');
    this.mode = null;
    //this.sectionToDelete = null;

    AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + this.sectionToManage.name + '\' removed');

  }

  saveSectionPriority(){
    this.newSections.emit(this.sections);
    AlertService.showAlert(AlertType.SUCCESS, 'Sections\' priority saved successfully');
    ModalService.closeModal('manage-sections-priority');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initSectionToManage(section?: RuleSection): SectionManageData {
    const sectionData: SectionManageData = {
      course: section?.course ?? null,
      name: section?.name ?? null,
      position: section?.position ?? null,
      data: null
    };
    if (section) sectionData.id = section.id;
    return sectionData;
  }

  resetSectionManage(){
    //this.mode = null;
    this.s.resetForm();
  }

  drop(event: CdkDragDrop<string[]>) {
    moveItemInArray(this.sections, event.previousIndex, event.currentIndex);
  }

  toggleSectionPriority(){
    this.sectionActions[0].disable = this.sections.length <= 1;
  }

}
