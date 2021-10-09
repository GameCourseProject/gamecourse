import { Component, OnInit } from '@angular/core';
import {Course} from "../../../../../_domain/Course";
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {ErrorService} from "../../../../../_services/error.service";

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
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.params.subscribe(async params => {
      this.course = await this.getCourse(params.id);
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
    });
  }

  async getCourse(courseID: number): Promise<Course> {
    return await this.api.getCourse(courseID).toPromise();
  }

}
