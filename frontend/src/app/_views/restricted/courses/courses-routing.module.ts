import {NgModule} from '@angular/core';
import {RouterModule, Routes} from '@angular/router';
import {CoursesComponent} from "./courses/courses.component";
import {LoadingState, Module} from "../../../_domain/modules/module";
import {DomSanitizer} from "@angular/platform-browser";
import {CourseUser} from "../../../_domain/users/course-user";

const routes: Routes = [
  {
    path: '',
    component: CoursesComponent,
  },
  {
    // NOTE: This path is only here so that course modules' styles are loaded
    //       and to trigger course user last activity refresh
    path: ':id',
    matcher: url => {
      const courseID = parseInt(url[0].path);

      // Load styles if not already loaded
      if (!Module.stylesLoaded.has(courseID) || Module.stylesLoaded.get(courseID).state === LoadingState.NOT_LOADED)
        Module.loadStyles(courseID, CoursesRoutingModule.sanitizer);

      // Trigger refresh on course user lastActivity
      if (!CourseUser.activityRefreshState.has(courseID))
        CourseUser.refreshActivity(courseID);

      // Return null so the router matches the next ':id' path
      return null;
    }
  },
  {
    path: ':id',
    loadChildren: () => import('./course/course.module').then(mod => mod.CourseModule),
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CoursesRoutingModule {
  static sanitizer: DomSanitizer;

  constructor(private sanitizer: DomSanitizer) {
    CoursesRoutingModule.sanitizer = sanitizer;
  }
}
