import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DocsRoutingModule } from './docs-routing.module';
import { ViewsComponent } from './views/views/views.component';
import { FunctionsComponent } from './functions/functions.component';
import { ModulesShellComponent } from './modules/modules-shell.component';
import { SharedModule } from "../../shared.module";
import { SidebarComponent } from './sidebar/sidebar.component';
import { DocsComponent } from './docs/docs.component';
import { PartsComponent } from './views/parts/parts.component';
import { ExpressionLanguageComponent } from './views/expression-language/expression-language.component';
import { PartConfigComponent } from './views/config/part-config.component';
import { ViewsShellComponent } from './views/views-shell.component';
import { CreationComponent } from './modules/creation/creation.component';
import { InitComponent } from './modules/init/init.component';
import { ModuleConfigComponent } from './modules/config/module-config.component';
import { ResourcesComponent } from './modules/resources/resources.component';
import { DataComponent } from './modules/data/data.component';


@NgModule({
  declarations: [
    ViewsComponent,
    FunctionsComponent,
    ModulesShellComponent,
    SidebarComponent,
    DocsComponent,
    PartsComponent,
    ExpressionLanguageComponent,
    PartConfigComponent,
    ViewsShellComponent,
    ModulesShellComponent,
    CreationComponent,
    InitComponent,
    ModuleConfigComponent,
    ResourcesComponent,
    DataComponent
  ],
  imports: [
    CommonModule,
    DocsRoutingModule,
    SharedModule
  ]
})
export class DocsModule { }
