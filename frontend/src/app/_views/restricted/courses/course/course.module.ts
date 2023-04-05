import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CourseRoutingModule } from './course-routing.module';
import { SharedModule } from "../../../../shared.module";

import { CoursePageComponent } from './pages/course-page/course-page.component';
import { UsersComponent } from './settings/users/users.component';
import { RolesComponent } from "./settings/roles/roles.component";
import { RulesComponent } from "./settings/rules/rules.component";
import { MainComponent } from './main/main.component';
import { ViewsComponent } from "./settings/views/views/views.component";
import { ViewsEditorComponent } from './settings/views/views-editor/views-editor.component';
import { GlobalComponent } from "./settings/global/global.component";
import { AdaptationComponent } from "./settings/adaptation/adaptation.component";
import { PreferenceQuestionnaireComponent } from "./settings/adaptation/questionnaire/preference-questionnaire.component"

import { ModulesComponent } from "./settings/modules/modules/modules.component";
import { ConfigComponent } from './settings/modules/config/config/config.component';
import { SkillsComponent } from './settings/modules/config/personalized-config/skills/skills.component';
import { GooglesheetsComponent } from './settings/modules/config/personalized-config/googlesheets/googlesheets.component';
import { QrComponent } from './settings/modules/config/personalized-config/qr/qr.component';
import { ProgressReportComponent } from './settings/modules/config/personalized-config/progress-report/progress-report.component';
import { ProfilingComponent } from './settings/modules/config/personalized-config/profiling/profiling.component';
import { DataSourceStatusComponent } from "./settings/modules/config/data-source-status/data-source-status.component";
import { SubmitParticipationPageComponent } from './pages/modules/qr/submit-participation-page/submit-participation-page.component';
import { SkillPageComponent } from './pages/modules/skills/skill-page/skill-page.component';
import {DragDropModule} from "@angular/cdk/drag-drop";


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
    CoursePageComponent,
    ConfigComponent,
    SkillsComponent,
    GooglesheetsComponent,
    QrComponent,
    ProgressReportComponent,
    ProfilingComponent,
    AdaptationComponent,
    PreferenceQuestionnaireComponent,
    ProfilingComponent,
    DataSourceStatusComponent,
    SubmitParticipationPageComponent,
    SkillPageComponent
  ],
    imports: [
        CommonModule,
        CourseRoutingModule,
        SharedModule,
        DragDropModule
    ]
})
export class CourseModule { }
