import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { CourseAdminGuard } from "../../../../_guards/course-admin-guard";

import { UsersComponent } from "./settings/users/users.component";
import { GlobalComponent } from "./settings/global/global.component";
import { RolesComponent } from "./settings/roles/roles.component";
import { ModulesComponent } from "./settings/modules/modules/modules.component";
import { RulesComponent } from "./settings/rules/rules.component";
import { ViewsComponent } from "./settings/views/views/views.component";
import { ViewsEditorComponent } from "./settings/views/views-editor/views-editor.component";
import { PageComponent } from "./page/page.component";
import { ConfigComponent } from "./settings/modules/config/config/config.component";

const routes: Routes = [
  {
    path: 'pages/:id',
    component: PageComponent
  },
  {
    path: 'pages/:id/user/:userId',
    component: PageComponent
  },
  {
    path: 'settings',
    canActivate: [CourseAdminGuard],
    children: [
      {
        path: 'users',
        component: UsersComponent,
        canActivate: [CourseAdminGuard]
      },
      {
        path: 'roles',
        component: RolesComponent,
        canActivate: [CourseAdminGuard]
      },
      {
        path: 'autogame',
        // component: AutoGameComponent
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
        path: 'pages',
        component: ViewsComponent,
      },
      {
        path: 'pages/templates/:id/editor',
        component: ViewsEditorComponent
      },
      {
        path: 'themes',
        component: GlobalComponent
      }
    ]
  },
  {
    path: 'overview',
    component: GlobalComponent,
    canActivate: [CourseAdminGuard]
  },
  {
    path: 'skills/:id',
    component: PageComponent
  },
  {
    path: 'skills/:id/:preview',
    component: PageComponent,
    canActivate: [CourseAdminGuard]
  },
  {
    path: 'participation/:key',
    component: PageComponent
  },
  { path: '', redirectTo: 'overview', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CourseRoutingModule { }
