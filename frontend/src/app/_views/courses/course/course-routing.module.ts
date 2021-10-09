import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {MainComponent} from "./main/main.component";
import {UsersComponent} from "./users/users.component";
import {SettingsComponent} from "./settings/settings/settings.component";
import {GlobalComponent} from "./settings/global/global.component";
import {RolesComponent} from "./settings/roles/roles.component";
import {ModulesComponent} from "./settings/modules/modules.component";
import {RulesComponent} from "./settings/rules/rules.component";
import {ViewsComponent} from "./settings/views/views/views.component";
import {ViewEditorComponent} from "./settings/views/view-editor/view-editor.component";

const routes: Routes = [
  {
    path: '',
    component: MainComponent
  },
  {
    path: 'users',
    component: UsersComponent
  },
  {
    path: 'settings',
    component: SettingsComponent,
    children: [
      {
        path: 'global',
        component: GlobalComponent
      },
      {
        path: 'roles',
        component: RolesComponent
      },
      {
        path: 'modules',
        component: ModulesComponent
      },
      {
        path: 'rules',
        component: RulesComponent
      },
      {
        path: 'views',
        component: ViewsComponent,
      },
      {
        path: 'views/templates/:id/editor',
        component: ViewEditorComponent,
      },
      { path: '', redirectTo: 'global', pathMatch: 'full' }
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CourseRoutingModule { }
