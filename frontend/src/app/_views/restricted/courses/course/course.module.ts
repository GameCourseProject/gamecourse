import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CourseRoutingModule } from './course-routing.module';
import { SharedModule } from "../../../../shared.module";

import { CoursePageComponent } from './pages/course-page/course-page.component';
import { UsersComponent } from './settings/users/users.component';
import { RolesComponent } from "./settings/roles/roles.component";
import { SectionsComponent } from "./settings/rules/sections.component";
import { RuleTagsManagementComponent } from "./settings/rules/tags/rule-tags-management.component";
import { SectionRulesComponent } from "./settings/rules/section-rules/section-rules.component";
import { MainComponent } from './main/main.component';
import { ViewsComponent } from "./settings/views/views/views.component";
import { ViewsEditorComponent } from './settings/views/views-editor/views-editor.component';
import { GlobalComponent } from "./settings/global/global.component";
import { AdaptationComponent } from "./settings/adaptation/adaptation.component";
import { PreferenceQuestionnaireComponent } from "./settings/adaptation/questionnaire/preference-questionnaire.component"

import { ModulesComponent } from "./settings/modules/modules/modules.component";
import { ConfigComponent } from './settings/modules/config/config/config.component';
import { SkillsComponent } from './settings/modules/config/personalized-config/skills/skills.component';
import { JourneyComponent } from "./settings/modules/config/personalized-config/journey/journey.component";
import { GooglesheetsComponent } from './settings/modules/config/personalized-config/googlesheets/googlesheets.component';
import { QrComponent } from './settings/modules/config/personalized-config/qr/qr.component';
import { ProfilingComponent } from './settings/modules/config/personalized-config/profiling/profiling.component';
import { DataSourceStatusComponent } from "./settings/modules/config/data-source-status/data-source-status.component";
import { SubmitParticipationPageComponent } from './pages/modules/qr/submit-participation-page/submit-participation-page.component';
import { SkillPageComponent } from './pages/modules/skills/skill-page/skill-page.component';
import {DragDropModule} from "@angular/cdk/drag-drop";
import {RulesComponent} from "./settings/rules/section-rules/rules/rules.component";
import { AutogameComponent } from './settings/autogame/autogame.component';
import { NotificationsComponent } from './settings/notifications/notifications.component';
import { DBExplorerComponent } from './settings/db-explorer/db-explorer.component';
import {SettingsComponent} from "./settings/settings/settings.component";


@NgModule({
  declarations: [
    MainComponent,
    UsersComponent,
    GlobalComponent,
    RolesComponent,
    ModulesComponent,
    SectionsComponent,
    SectionRulesComponent,
    RulesComponent,
    RuleTagsManagementComponent,
    ViewsComponent,
    ViewsEditorComponent,
    CoursePageComponent,
    ConfigComponent,
    SkillsComponent,
    JourneyComponent,
    GooglesheetsComponent,
    QrComponent,
    ProfilingComponent,
    AdaptationComponent,
    AutogameComponent,
    PreferenceQuestionnaireComponent,
    ProfilingComponent,
    DataSourceStatusComponent,
    SubmitParticipationPageComponent,
    SkillPageComponent,
    NotificationsComponent,
    DBExplorerComponent,
    SettingsComponent
  ],
    imports: [
        CommonModule,
        CourseRoutingModule,
        SharedModule,
        DragDropModule
    ]
})
export class CourseModule { }
