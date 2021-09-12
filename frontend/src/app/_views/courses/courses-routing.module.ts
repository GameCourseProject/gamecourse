import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {MainComponent} from "./main/main.component";

const routes: Routes = [
  {
    path: '',
    component: MainComponent,
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
