import {Component, Input, OnInit, ViewChild} from "@angular/core";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {NgForm} from "@angular/forms";
import {Course} from "../../../../../../../_domain/courses/course";
import {ActivatedRoute} from "@angular/router";
import {RuleTag} from "../../../../../../../_domain/rules/RuleTag";
import {ThemingService} from "../../../../../../../_services/theming/theming.service";
import {clearEmptyValues} from "../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";
import {ModalService} from "../../../../../../../_services/modal.service";

@Component({
  selector: 'app-rule-tags',
  templateUrl: './rule-tags.component.html'
})
export class RuleTagsComponent implements OnInit {

  @Input() course: Course;
  @Input() mode: 'manage tags' | 'add tag' | 'remove tag' | 'edit tag';
  @Input() tags: RuleTag[];

  // FIXME -- Should consider light and dark theme
  colors: string[] = ["#5E72E4", "#EA6FAC", "#1EA896", "#38BFF8", "#36D399", "#FBBD23", "#EF6060"];

  loading = {
    management: false,
    action: false
  };
  tagToManage: TagManageData;
  newTag: TagManageData;

  @ViewChild('t', {static: false}) t: NgForm;       // tag form

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public themeService: ThemingService
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe();
    this.newTag = this.initTagToManage();
  }

  async doAction(action: string, tag?: RuleTag){

    if (action === 'create'){
      const color = this.colors.find(color => color === this.newTag.color);

      if (color){
        // Update DB
        const tag = await this.api.createTag(clearEmptyValues(this.newTag)).toPromise();

        // Update UI
        this.tags.push(tag);

        AlertService.showAlert(AlertType.SUCCESS, 'Tag created successfully');
        this.newTag = this.initTagToManage();

      } else AlertService.showAlert(AlertType.ERROR, 'Tag must have a name and a color (choose one color from the available options).');

    } else if (action === 'edit'){
      console.log(tag);

    } else if (action === 'delete'){
      this.tagToManage = this.initTagToManage(tag);
      this.mode = 'remove tag';
      ModalService.openModal('delete-tag');
    }

  }

  async deleteTag(): Promise<void>{
    // remove from DB
    await this.api.removeTag(this.course.id, this.tagToManage.id).toPromise();

    // remove from UI
    const index = this.tags.findIndex(tag => tag.id === this.tagToManage.id);
    this.tags.splice(index, 1);

    ModalService.closeModal('delete-tag');
    this.tagToManage = this.initTagToManage();
    AlertService.showAlert(AlertType.SUCCESS, 'Tag deleted successfully');
  }

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
      color : tag?.color ?? null
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
