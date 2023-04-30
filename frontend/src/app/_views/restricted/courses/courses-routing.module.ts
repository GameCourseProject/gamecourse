import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { CourseUserGuard } from "../../../_guards/course-user.guard";
import { LoadModuleStylesGuard } from "../../../_guards/load-module-styles.guard";
import { RefreshCourseUserActivityGuard } from "../../../_guards/refresh-course-user-activity.guard";

import { CoursesComponent } from "./courses/courses.component";

const routes: Routes = [
  {
    path: '',
    component: CoursesComponent,
  },
  {
    path: ':id',
    loadChildren: () => import('./course/course.module').then(mod => mod.CourseModule),
    canActivate: [CourseUserGuard, LoadModuleStylesGuard, RefreshCourseUserActivityGuard]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CoursesRoutingModule { }
