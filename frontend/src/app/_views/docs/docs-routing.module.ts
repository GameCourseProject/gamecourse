import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {ViewsComponent} from "./views/views.component";
import {FunctionsComponent} from "./functions/functions.component";
import {ModulesComponent} from "./modules/modules.component";

const routes: Routes = [
  {
    path: 'views',
    component: ViewsComponent
  },
  {
    path: 'functions',
    component: FunctionsComponent
  },
  {
    path: 'modules',
    component: ModulesComponent
  },
  { path: '', redirectTo: 'views', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DocsRoutingModule { }
