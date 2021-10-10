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
import { ViewsEditorComponent } from './settings/views/views-editor/views-editor.component';
import {SidebarComponent} from "./settings/sidebar/sidebar.component";
import { PageComponent } from './page/page.component';


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
    ViewsEditorComponent,
    PageComponent
  ],
  imports: [
    CommonModule,
    CourseRoutingModule,
    SharedModule,
    FormsModule
  ]
})
export class CourseModule { }
