import { Component, Input, OnInit, ViewChild } from '@angular/core';

import { ApiHttpService } from "../../../_services/api/api-http.service";
import { Aspect } from 'src/app/_domain/views/aspects/aspect';
import { Course } from 'src/app/_domain/courses/course';
import { NgForm } from '@angular/forms';
import { Output, EventEmitter } from '@angular/core';
import * as _ from "lodash";
import { ViewEditorService } from 'src/app/_services/view-editor.service';

@Component({
  selector: 'app-aspect-card',
  templateUrl: './aspect-card.component.html'
})
export class AspectCardComponent implements OnInit {

  @ViewChild('f', { static: false }) f: NgForm;

  @Input() course: Course;
  @Input() aspect: Aspect;
  @Input() editable?: boolean;
  @Input() selected?: boolean;
  @Output() deleteEvent = new EventEmitter<string>();

  oldUserRole?: string;
  oldViewerRole?: string;
  roles: { value: string, text: string }[];
  edit: boolean = false;

  constructor(
    private api: ApiHttpService,
    private viewEditorService: ViewEditorService,
  ) { }

  ngOnInit(): void {
    this.getCourseRolesNames();
    this.oldUserRole = this.aspect.userRole;
    this.oldViewerRole = this.aspect.viewerRole;
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
    if (this.aspect.viewerRole == "undefined" || this.aspect.viewerRole == "new") this.aspect.viewerRole = null;
    if (this.aspect.userRole == "undefined" || this.aspect.userRole == "new") this.aspect.userRole = null;
    this.edit = false;
    this.viewEditorService.aspectsToChange.push({old: new Aspect(this.oldViewerRole, this.oldUserRole), newAspect: new Aspect(this.aspect.viewerRole, this.aspect.userRole)});
  }
  
  cancel() {
    this.aspect.userRole = this.oldUserRole;
    this.aspect.viewerRole = this.oldViewerRole;
    this.edit = false;
  }
}
