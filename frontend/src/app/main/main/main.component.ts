import { Component, OnInit } from '@angular/core';
import {Course} from "../../_domain/Course";
import {ApiHttpService} from "../../_services/api/api-http.service";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  loading = true;

  activeCourses: Course[]; // TODO: get actual active courses

  constructor(
    private apiHttpService: ApiHttpService
  ) {

  }

  ngOnInit(): void {
    setTimeout(() => {
      this.getUserActiveCourses();
      this.loading = false;
    }, 800); // FIXME: remove timeout

    // this.apiHttpService
    //   .get(this.apiEndpointsService.getCoursesEndpoint())
    //   .subscribe(res => console.log(res));
  }

  getUserActiveCourses(): any {
    this.activeCourses = [
      new Course({id: 1, name: 'Multimedia Content Production'})
    ];
  }

}
