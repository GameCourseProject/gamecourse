import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { CourseAdminGuard } from "../../../../_guards/course-admin-guard";

import { MainComponent } from "./main/main.component";
import { UsersComponent } from "./settings/users/users.component";
import { RolesComponent } from "./settings/roles/roles.component";
import { ModulesComponent } from "./settings/modules/modules/modules.component";
import { RulesComponent } from "./settings/rules/rules.component";
import { ViewsEditorComponent } from "./settings/views/views-editor/views-editor.component";
import { CoursePageComponent } from "./pages/course-page/course-page.component";
import { ConfigComponent } from "./settings/modules/config/config/config.component";
import { ComingSoonComponent } from "../../../../_components/misc/pages/coming-soon/coming-soon.component";
import { SkillPageComponent } from "./pages/modules/skills/skill-page/skill-page.component";
import {
  SubmitParticipationPageComponent
} from "./pages/modules/qr/submit-participation-page/submit-participation-page.component";
import { AdaptationComponent } from "./settings/adaptation/adaptation.component";

const routes: Routes = [
  {
    path: 'main',
    component: MainComponent
  },
  {
    path: 'overview',
    component: ComingSoonComponent,
    canActivate: [CourseAdminGuard]
  },
  {
    path: 'settings',
    canActivate: [CourseAdminGuard],
    children: [
      {
        path: 'users',
        component: UsersComponent
      },
      {
        path: 'roles',
        component: RolesComponent
      },
      {
        path: 'autogame',
        component: ComingSoonComponent
      },
      {
        path: 'rule-system',
        component: RulesComponent
      },
      {
        path: 'modules',
        component: ModulesComponent
      },
      {
        path: 'modules/:id/config',
        component: ConfigComponent
      },
      {
        path: 'notifications',
        component: ComingSoonComponent
      },
      {
        path: 'pages',
        component: ComingSoonComponent,
      },
      {
        path: 'pages/templates/:id/editor',
        component: ViewsEditorComponent
      },
      {
        path: 'themes',
        component: ComingSoonComponent
      },
      {
        path: 'adaptation',
        component: AdaptationComponent
      }
    ]
  },
  {
    path: 'pages/:id',
    children: [
      {
        path: '',
        component: CoursePageComponent
      },
      {
        path: 'user/:userId',
        component: CoursePageComponent
      }
    ]
  },
  {
    path: 'skills/:id',
    children: [
      {
        path: '',
        component: SkillPageComponent
      },
      {
        path: 'preview',
        component: SkillPageComponent,
        canActivate: [CourseAdminGuard]
      }
    ]
  },
  {
    path: 'participation/:key',
    component: SubmitParticipationPageComponent
  },
  { path: '', redirectTo: 'main', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CourseRoutingModule { }
