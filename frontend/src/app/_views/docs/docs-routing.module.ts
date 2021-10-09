import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import {ViewsComponent} from "./views/views/views.component";
import {FunctionsComponent} from "./functions/functions.component";
import {ModulesShellComponent} from "./modules/modules-shell.component";
import {DocsComponent} from "./docs/docs.component";
import {ViewsShellComponent} from "./views/views-shell.component";
import {PartsComponent} from "./views/parts/parts.component";
import {ExpressionLanguageComponent} from "./views/expression-language/expression-language.component";
import {PartConfigComponent} from "./views/config/part-config.component";
import {CreationComponent} from "./modules/creation/creation.component";
import {InitComponent} from "./modules/init/init.component";
import {ModuleConfigComponent} from "./modules/config/module-config.component";
import {ResourcesComponent} from "./modules/resources/resources.component";
import {DataComponent} from "./modules/data/data.component";

const routes: Routes = [
  {
    path: '',
    component: DocsComponent,
    children: [
      {
        path: 'views',
        component: ViewsShellComponent,
        children: [
          { path: 'views', component: ViewsComponent },
          { path: 'part-types', component: PartsComponent },
          { path: 'expression-language', component: ExpressionLanguageComponent },
          { path: 'part-configuration', component: PartConfigComponent },
          { path: '', redirectTo: 'views', pathMatch: 'full' }
        ]
      },
      {
        path: 'functions',
        component: FunctionsComponent,
        children: [
          { path: '', component: FunctionsComponent }
        ]
      },
      {
        path: 'modules',
        component: ModulesShellComponent,
        children: [
          { path: 'creation', component: CreationComponent },
          { path: 'initialization', component: InitComponent },
          { path: 'configuration', component: ModuleConfigComponent },
          { path: 'resources', component: ResourcesComponent },
          { path: 'data', component: DataComponent },
          { path: '', redirectTo: 'creation', pathMatch: 'full' }
        ]
      },
      { path: '', redirectTo: 'creation', pathMatch: 'full' }
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DocsRoutingModule { }
