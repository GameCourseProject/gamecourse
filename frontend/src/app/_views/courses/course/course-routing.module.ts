import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {MainComponent} from "./main/main.component";
import {UsersComponent} from "./users/users.component";
import {ThisCourseComponent} from "./settings/this-course/this-course.component";
import {RolesComponent} from "./settings/roles/roles.component";
import {ModulesComponent} from "./settings/modules/modules.component";
import {RulesComponent} from "./settings/rules/rules.component";
import {ViewsComponent} from "./settings/views/views.component";

const routes: Routes = [
  {
    path: '',
    component: MainComponent
  },
  {
    path: 'users',
    component: UsersComponent
  },
  {
    path: 'settings/global',
    component: ThisCourseComponent
  },
  {
    path: 'settings/roles',
    component: RolesComponent
  },
  {
    path: 'settings/modules',
    component: ModulesComponent
  },
  {
    path: 'settings/rules',
    component: RulesComponent
  },
  {
    path: 'settings/views',
    component: ViewsComponent
  },
  { path: 'settings', redirectTo: 'settings/global', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CourseRoutingModule { }
