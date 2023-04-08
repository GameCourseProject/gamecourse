import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from "@angular/core";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {NgForm} from "@angular/forms";
import {Course} from "../../../../../../../_domain/courses/course";
import {ActivatedRoute} from "@angular/router";
import {RuleTag} from "../../../../../../../_domain/rules/RuleTag";
import {ThemingService} from "../../../../../../../_services/theming/theming.service";
import {clearEmptyValues} from "../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";
import {ModalService} from "../../../../../../../_services/modal.service";
import {Rule} from "../../../../../../../_domain/rules/rule";

@Component({
  selector: 'app-rule-tags',
  templateUrl: './rule-tags.component.html'
})
export class RuleTagsComponent implements OnInit {

  @Input() course: Course;
  @Input() mode: 'manage tags' | 'add tag' | 'remove tag' | 'edit tag';

  @Input() tags: RuleTag[];
  @Input() rules: Rule[];

  @Output() newTags = new EventEmitter<RuleTag[]>();

  // FIXME -- Should consider light and dark theme
  colors: string[] = ["#5E72E4", "#EA6FAC", "#1EA896", "#38BFF8", "#36D399", "#FBBD23", "#EF6060"];

  loading = {
    management: false,
    action: false
  };

  tagToManage: TagManageData;
  tagEdit: string = "";
  ruleNames: {value: any, text: string}[];

  @ViewChild('t', {static: false}) t: NgForm;       // tag form

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public themeService: ThemingService
  ) { }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  ngOnInit(): void {
    this.route.parent.params.subscribe();
    this.ruleNames = this.getRuleNames();
  }

  getRuleNames(): {value: any, text: string}[] {
    return Object.values(this.rules).map(rule => {
      return {value: rule.name, text: (rule.name).capitalize()}
    });
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  prepareModal(action: string, tag?: RuleTag){
    this.tagToManage = this.initTagToManage(tag);

    this.mode = switchMode(action);

    function switchMode(action?: string) {
      switch (action) {
        case "create" : return 'add tag';
        case "edit" : return 'edit tag';
        case "delete" : return 'remove tag';
      }
      return "manage tags";
    }

    if (this.mode === "add tag" || this.mode === "edit tag") {
      ModalService.openModal('create-and-edit-tag');
      this.tagEdit = JSON.stringify(this.tagToManage.name);

    } else {
      ModalService.openModal(this.mode);
    }
  }

  async doAction(action: string): Promise<void> {

    if (action === 'remove tag') {
      // remove from DB
      await this.api.removeTag(this.course.id, this.tagToManage.id).toPromise();

      // remove from UI
      const index = this.tags.findIndex(tag => tag.id === this.tagToManage.id);
      this.tags.splice(index, 1);

      AlertService.showAlert(AlertType.SUCCESS, "Tag deleted successfully");
      ModalService.closeModal(action);

    } else if (action === 'add tag') {
      if (this.t.valid){
        const color = this.colors.find(color => color === this.tagToManage.color);

        if (color) {
          const tag = await this.api.createTag(clearEmptyValues(this.tagToManage)).toPromise(); // Update DB
          this.tags.push(tag); // Update UI

          AlertService.showAlert(AlertType.SUCCESS, "Tag created successfully");
          ModalService.closeModal('create-and-edit-tag');
          this.resetTagManage();

        } else { AlertService.showAlert(AlertType.ERROR, "Tag color must be selected from one of the available options"); }
      } else { AlertService.showAlert(AlertType.ERROR, "Invalid form"); }

    } else if (action === 'edit tag'){
      if (this.t.valid){
        const color = this.colors.find(color => color === this.tagToManage.color);

        if (color) {
          const editedTag = await this.api.editTag(this.tagToManage).toPromise(); // Update DB

          // Update UI
          const index = this.tags.findIndex(tag => tag.id === this.tagToManage.id);
          this.tags.splice(index, 1, editedTag);

          AlertService.showAlert(AlertType.SUCCESS, "Tag edited successfully");
          ModalService.closeModal('create-and-edit-tag');
          this.resetTagManage();

        } else { AlertService.showAlert(AlertType.ERROR, "Tag color must be selected from one of the available options"); }
      } else { AlertService.showAlert(AlertType.ERROR, "Invalid form"); }
    }
  }

  closeManagement(){
    ModalService.closeModal('manage tags');
    this.mode = null;
    this.newTags.emit(this.tags);
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  hexaToColor(color: string) : string {
    if (this.themeService.getTheme() === "light"){
      switch (color) {
        case "#5E72E4" : return "primary";
        case "#EA6FAC" : return "secondary";
        case "#1EA896" : return "accent";
        case "#38BFF8" : return "info";
        case "#36D399" : return "success";
        case "#FBB50A" : return "warning";
        case "#EF6060" : return "error";
      }
    }
    else {
      switch (color) {
        case "#5E72E4" : return "primary";
        case "#EA6FAC" : return "secondary";
        case "#1EA896" : return "accent";
        case "#38BFF8" : return "info";
        case "#36D399" : return "success";
        case "#FBBD23" : return "warning";
        case "#EF6060" : return "error";
      }
    }
    return "";
  }

  initTagToManage(tag?: RuleTag): TagManageData {
    const tagData: TagManageData = {
      course: tag?.course ?? this.course.id,
      name : tag?.name ?? null,
      color : tag?.color ?? this.colors[0]
    };
    if (tag) { tagData.id = tag.id; }
    return tagData;
  }

  resetTagManage() {
    this.tagToManage = this.initTagToManage();
    this.t.resetForm();
  }
}

export interface TagManageData {
  id?: number,
  course?: number,
  name?: string,
  color?: string
}
