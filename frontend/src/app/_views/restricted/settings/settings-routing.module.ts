import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {GlobalComponent} from "./global/global.component";
import {AboutComponent} from "./about/about.component";
import {ModulesComponent} from "./modules/modules.component";
import {SettingsComponent} from "./settings/settings.component";

const routes: Routes = [
  {
    path: '',
    component: SettingsComponent,
    children: [
      {
        path: 'global',
        component: GlobalComponent
      },
      {
        path: 'about',
        component: AboutComponent
      },
      {
        path: 'modules',
        component: ModulesComponent
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
