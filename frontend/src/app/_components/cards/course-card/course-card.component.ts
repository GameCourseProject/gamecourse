import { Component, Input, OnInit } from '@angular/core';
import { Course } from 'src/app/_domain/courses/course';
import { User } from "../../../_domain/users/user";

@Component({
  selector: 'app-course-card',
  templateUrl: './course-card.component.html'
})
export class CourseCardComponent implements OnInit {

  @Input() course: Course;
  @Input() user: User;

  constructor() { }

  ngOnInit(): void {
  }

  getRedirectLink(): string {
    const link = '/courses/' + this.course.id;
    if (this.user.isAdmin) return link; // admins go to main page

    const pageID = this.course.landingPage; // FIXME: landing page per user role
    if (pageID) return link + '/pages/' + pageID;
    else return link;
  }

}
