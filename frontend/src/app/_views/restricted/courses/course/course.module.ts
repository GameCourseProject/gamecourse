import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CourseRoutingModule } from './course-routing.module';
import { SharedModule } from "../../../../shared.module";

import { PageComponent } from './page/page.component';
import { UsersComponent } from './users/users.component';
import { RolesComponent } from "./settings/roles/roles.component";
import { RulesComponent } from "./settings/rules/rules.component";
import { MainComponent } from './main/main.component';
import { ViewsComponent } from "./settings/views/views/views.component";
import { ViewsEditorComponent } from './settings/views/views-editor/views-editor.component';
import { GlobalComponent } from "./settings/global/global.component";

import { ModulesComponent } from "./settings/modules/modules/modules.component";
import { ConfigComponent } from './settings/modules/config/config/config.component';
import { FenixComponent } from './settings/modules/config/personalized-config/fenix/fenix.component';
import { SkillsComponent } from './settings/modules/config/personalized-config/skills/skills.component';
import { GooglesheetsComponent } from './settings/modules/config/personalized-config/googlesheets/googlesheets.component';
import { QrComponent } from './settings/modules/config/personalized-config/qr/qr.component';
import { NotificationsComponent } from './settings/modules/config/personalized-config/notifications/notifications.component';
import { ProfilingComponent } from './settings/modules/config/personalized-config/profiling/profiling.component';


@NgModule({
  declarations: [
    MainComponent,
    UsersComponent,
    GlobalComponent,
    RolesComponent,
    ModulesComponent,
    RulesComponent,
    ViewsComponent,
    ViewsEditorComponent,
    PageComponent,
    ConfigComponent,
    FenixComponent,
    SkillsComponent,
    GooglesheetsComponent,
    QrComponent,
    NotificationsComponent,
    ProfilingComponent
  ],
  imports: [
    CommonModule,
    CourseRoutingModule,
    SharedModule
  ]
})
export class CourseModule { }
