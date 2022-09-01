import {NgModule} from '@angular/core';
import { CommonModule } from '@angular/common';

import { CourseRoutingModule } from './course-routing.module';
import { MainComponent } from './main/main.component';
import { UsersComponent } from './users/users.component';
import {SettingsComponent} from "./settings/settings/settings.component";
import {SharedModule} from "../../../../shared.module";
import {FormsModule} from "@angular/forms";
import {GlobalComponent} from "./settings/global/global.component";
import {RolesComponent} from "./settings/roles/roles.component";
import {ModulesComponent} from "./settings/modules/modules/modules.component";
import {RulesComponent} from "./settings/rules/rules.component";
import {ViewsComponent} from "./settings/views/views/views.component";
import { ViewsEditorComponent } from './settings/views/views-editor/views-editor.component';
import {SidebarComponent} from "./settings/sidebar/sidebar.component";
import { PageComponent } from './page/page.component';
import { ConfigComponent } from './settings/modules/config/config/config.component';
import { FenixComponent } from './settings/modules/config/fenix/fenix.component';
import { ClasscheckComponent } from './settings/modules/config/classcheck/classcheck.component';
import { SkillsComponent } from './settings/modules/config/personalized-config/skills/skills.component';
import { GooglesheetsComponent } from './settings/modules/config/googlesheets/googlesheets.component';
import { MoodleComponent } from './settings/modules/config/moodle/moodle.component';
import { QrComponent } from './settings/modules/config/personalized-config/qr/qr.component';
import { NotificationsComponent } from './settings/modules/config/notifications/notifications.component';
import { ProfilingComponent } from './settings/modules/config/profiling/profiling.component';


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
    PageComponent,
    ConfigComponent,
    FenixComponent,
    ClasscheckComponent,
    SkillsComponent,
    GooglesheetsComponent,
    MoodleComponent,
    QrComponent,
    NotificationsComponent,
    ProfilingComponent
  ],
  imports: [
    CommonModule,
    CourseRoutingModule,
    SharedModule,
    FormsModule
  ]
})
export class CourseModule { }
