import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {CoursesComponent} from "./courses/courses.component";

const routes: Routes = [
  {
    path: '',
    component: CoursesComponent,
  },
  {
    path: ':id/:name',
    loadChildren: () => import('./course/course.module').then(mod => mod.CourseModule),
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CoursesRoutingModule { }
