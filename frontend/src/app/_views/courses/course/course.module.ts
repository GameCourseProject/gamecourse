import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CourseRoutingModule } from './course-routing.module';
import { MainComponent } from './main/main.component';
import {SharedModule} from "../../../_components/shared.module";
import { UsersComponent } from './users/users.component';
import { SidebarComponent } from './settings/sidebar/sidebar.component';
import { ThisCourseComponent } from './settings/this-course/this-course.component';
import { RolesComponent } from './settings/roles/roles.component';
import { ModulesComponent } from './settings/modules/modules.component';
import { RulesComponent } from './settings/rules/rules.component';
import { ViewsComponent } from './settings/views/views.component';


@NgModule({
  declarations: [
    MainComponent,
    UsersComponent,
    SidebarComponent,
    ThisCourseComponent,
    RolesComponent,
    ModulesComponent,
    RulesComponent,
    ViewsComponent
  ],
  imports: [
    CommonModule,
    CourseRoutingModule,
    SharedModule
  ]
})
export class CourseModule { }
