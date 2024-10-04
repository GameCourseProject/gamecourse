import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {ModulesComponent} from "./modules/modules.component";
import {BeginComponent} from "./begin/begin.component";

const routes: Routes = [
  {
    path: '',
    children: [
      {
        path: '',
        component: BeginComponent
      },
      {
        path: 'modules',
        component: ModulesComponent
      },
    ],
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DocsRoutingModule { }
