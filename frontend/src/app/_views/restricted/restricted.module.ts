import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RestrictedRoutingModule } from './restricted-routing.module';
import { RestrictedComponent } from './restricted.component';
import {SharedModule} from "../../shared.module";


@NgModule({
  declarations: [
    RestrictedComponent
  ],
  imports: [
    CommonModule,
    RestrictedRoutingModule,
    SharedModule
  ]
})
export class RestrictedModule { }