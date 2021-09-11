import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DocsRoutingModule } from './docs-routing.module';
import { ViewsComponent } from './views/views.component';
import { FunctionsComponent } from './functions/functions.component';
import { ModulesComponent } from './modules/modules.component';
import {SharedModule} from "../../_components/shared.module";
import { SidebarComponent } from './sidebar/sidebar.component';


@NgModule({
  declarations: [
    ViewsComponent,
    FunctionsComponent,
    ModulesComponent,
    SidebarComponent
  ],
  imports: [
    CommonModule,
    DocsRoutingModule,
    SharedModule
  ]
})
export class DocsModule { }
