import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CourseRoutingModule } from './course-routing.module';
import { MainComponent } from './main/main.component';
import { UsersComponent } from './users/users.component';
import {SettingsComponent} from "./settings/settings/settings.component";
import {SharedModule} from "../../../_components/shared.module";
import {FormsModule} from "@angular/forms";
import {GlobalComponent} from "./settings/global/global.component";
import {RolesComponent} from "./settings/roles/roles.component";
import {ModulesComponent} from "./settings/modules/modules.component";
import {RulesComponent} from "./settings/rules/rules.component";
import {ViewsComponent} from "./settings/views/views/views.component";
import { ViewEditorComponent } from './settings/views/view-editor/view-editor.component';
import {SidebarComponent} from "./settings/sidebar/sidebar.component";


@NgModule({
  declarations: [
    MainComponent,
    UsersComponent,
    SettingsComponent,
    SidebarComponent,
    GlobalComponent,
    RolesComponent,
    ModulesComponent,
    RulesComponent,
    ViewsComponent,
    ViewEditorComponent
  ],
  imports: [
    CommonModule,
    CourseRoutingModule,
    SharedModule,
    FormsModule
  ]
})
export class CourseModule { }
