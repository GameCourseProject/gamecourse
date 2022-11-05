import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {GlobalComponent} from "./global/global.component";
import {ModulesComponent} from "./modules/modules.component";
import {ComingSoonComponent} from "../../../_components/misc/pages/coming-soon/coming-soon.component";

const routes: Routes = [
  {
    path: '',
    component: ComingSoonComponent,
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
