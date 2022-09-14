import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {GlobalComponent} from "./global/global.component";
import {ModulesComponent} from "./modules/modules.component";
import {SettingsComponent} from "./settings/settings.component";

const routes: Routes = [
  {
    path: '',
    component: SettingsComponent,
    children: [
      {
        path: 'modules',
        component: ModulesComponent
      },
      {
        path: 'themes',
        component: GlobalComponent
      },
      { path: '', redirectTo: 'global', pathMatch: 'full' }
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class SettingsRoutingModule { }
