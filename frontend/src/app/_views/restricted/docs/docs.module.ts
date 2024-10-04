import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import {ModulesComponent} from "./modules/modules.component";
import {SharedModule} from "../../../shared.module";
import {DocsRoutingModule} from "./docs-routing.module";

@NgModule({
  declarations: [
    ModulesComponent
  ],
  imports: [
    CommonModule,
    DocsRoutingModule,
    SharedModule,
  ]
})
export class DocsModule { }
