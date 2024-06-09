import {Component, Input, OnInit, SimpleChanges, ViewChild} from '@angular/core';

import { ApiHttpService } from "../../../_services/api/api-http.service";
import { Aspect } from 'src/app/_domain/views/aspects/aspect';
import { Course } from 'src/app/_domain/courses/course';
import { NgForm } from '@angular/forms';
import { Output, EventEmitter } from '@angular/core';
import * as _ from "lodash";
import { ViewEditorService } from 'src/app/_services/view-editor.service';
import {AlertService, AlertType} from "../../../_services/alert.service";

@Component({
  selector: 'app-aspect-card',
  templateUrl: './aspect-card.component.html'
})
export class AspectCardComponent implements OnInit {

  @ViewChild('f', { static: false }) f: NgForm;

  @Input() course: Course;
  @Input() isNewPage: boolean;
  @Input() aspect: Aspect;
  @Input() editable?: boolean;
  @Input() selected?: boolean;
  @Output() deleteEvent = new EventEmitter<string>();
  @Output() editEvent = new EventEmitter<{ old: Aspect, new: Aspect }>();

  oldUserRole?: string;
  oldViewerRole?: string;
  roles: { value: string, text: string }[];
  edit: boolean = false;

  constructor(
    private api: ApiHttpService,
    private viewEditorService: ViewEditorService,
  ) { }

  async ngOnInit() {
    await this.getCourseRolesNames();
    this.oldUserRole = this.aspect.userRole;
    this.oldViewerRole = this.aspect.viewerRole;
    this.aspect = _.cloneDeep(this.aspect);
  }

  async getCourseRolesNames() {
    const roles = await this.api.getRoles(this.course.id, true).toPromise();
    this.roles = roles.map((value) => { return ({ value: value, text: value }) })
  }

  editAction() {
    this.edit = true;
  }

  deleteAction() {
    this.deleteEvent.emit();
  }

  save() {
    if (!this.aspect.viewerRole || this.aspect.viewerRole == "") this.aspect.viewerRole = null;
    if (!this.aspect.viewerRole || this.aspect.userRole == "") this.aspect.userRole = null;
    const oldAspect = new Aspect(this.oldViewerRole, this.oldUserRole);
    const newAspect = new Aspect(this.aspect.viewerRole, this.aspect.userRole);

    if (this.viewEditorService.getFutureAspects().filter(e => _.isEqual(e, newAspect)).length <= 0) {
      this.viewEditorService.aspectsToChange.push({old: oldAspect, newAspect: newAspect});
      this.edit = false;
      this.editEvent.emit({old: oldAspect, new: newAspect});
    }
    else {
      this.aspect.userRole = this.oldUserRole;
      this.aspect.viewerRole = this.oldViewerRole;
      this.edit = true;
      AlertService.showAlert(AlertType.ERROR, "A version with these roles already exists.");
    }
  }

  cancel() {
    this.aspect.userRole = this.oldUserRole;
    this.aspect.viewerRole = this.oldViewerRole;
    this.edit = false;
  }

  // can't change the root of an existing page
  canEdit() {
    return !_.isEqual(new Aspect(null, null), this.aspect) || this.isNewPage;
  }
}
