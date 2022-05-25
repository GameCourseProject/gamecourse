import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";

import {Navigation} from "../../../../../../_components/navbar/navbar.component";

@Component({
  selector: 'app-course-settings-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit {

  navigation: Navigation[] = [];

  constructor(
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      this.initNavigation(courseID);
    });
  }

  initNavigation(courseID: number) {
    const path = '/courses/' + courseID + '/settings/';

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
      },
      {
        name: 'Views',
        link: path + 'views'
      }
    ];
  }

}
