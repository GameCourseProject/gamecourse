import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";

import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../_services/error.service";
import {UpdateService, UpdateType} from "../../../../../_services/update.service";

import {Course} from "../../../../../_domain/Course";

@Component({
  selector: 'app-course-settings-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit {

  course: Course;

  navigation: { name: string, link: string }[] = [];

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private updateManager: UpdateService
  ) { }

  ngOnInit(): void {
    this.route.params.subscribe(async params => {
      await this.initNavigation(params.id);

      this.updateManager.update.subscribe(async type => {
        console.log('update')
        if (type === UpdateType.VIEWS) await this.initNavigation(params.id);
      });
    }).unsubscribe();
  }

  async initNavigation(courseID: number) {
    console.log('init sidebar')
    this.course = await this.getCourse(courseID);
    const path = '/courses/' + this.course.id + '/settings/';

    this.navigation = [
      {
        name: 'This Course',
        link: path + 'global'
      },
      {
        name: 'Roles',
        link: path + 'roles'
      },
      {
        name: 'Modules',
        link: path + 'modules'
      },
      {
        name: 'Rules',
        link: path + 'rules'
      }
    ];

    this.api.hasViewsEnabled(this.course.id)
      .subscribe(has => {
          if (has) this.navigation.push({name: 'Views', link: path + 'views'});
        },
        error => ErrorService.set(error));
  }

  async getCourse(courseID: number): Promise<Course> {
    return await this.api.getCourse(courseID).toPromise();
  }

}
