import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CourseRoutingModule } from './course-routing.module';
import { MainComponent } from './main/main.component';
import {SharedModule} from "../../../_components/shared.module";
import { UsersComponent } from './users/users.component';
import { SidebarComponent } from './settings/sidebar/sidebar.component';
import { GlobalComponent } from './settings/global/global.component';
import { RolesComponent } from './settings/roles/roles.component';
import { ModulesComponent } from './settings/modules/modules.component';
import { RulesComponent } from './settings/rules/rules.component';
import { ViewsComponent } from './settings/views/views.component';
import {FormsModule} from "@angular/forms";


@NgModule({
  declarations: [
    MainComponent,
    UsersComponent,
    SidebarComponent,
    GlobalComponent,
    RolesComponent,
    ModulesComponent,
    RulesComponent,
    ViewsComponent
  ],
  imports: [
    CommonModule,
    CourseRoutingModule,
    SharedModule,
    FormsModule
  ]
})
export class CourseModule { }
