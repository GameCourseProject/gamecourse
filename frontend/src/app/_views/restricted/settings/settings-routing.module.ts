import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {ModulesComponent} from "./modules/modules.component";
import {ThemesComponent} from "./themes/themes.component";

const routes: Routes = [
  {
    path: '',
    children: [
      {
        path: 'modules',
        component: ModulesComponent
      },
      {
        path: 'themes',
        component: ThemesComponent
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
