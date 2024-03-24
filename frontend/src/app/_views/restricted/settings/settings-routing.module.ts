import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {ModulesComponent} from "./modules/modules.component";
import {ComingSoonComponent} from "../../../_components/misc/pages/coming-soon/coming-soon.component";

const routes: Routes = [
  {
    path: '',
    children: [
      {
        path: 'modules',
        component: ComingSoonComponent  // TODO: ModulesComponent
      },
      {
        path: 'themes',
        component: ComingSoonComponent
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
