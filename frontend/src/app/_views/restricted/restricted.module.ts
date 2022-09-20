import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RestrictedRoutingModule } from './restricted-routing.module';
import { SharedModule } from "../../shared.module";

import { RestrictedComponent } from './restricted.component';
import { AboutComponent } from "./about/about.component";


@NgModule({
  declarations: [
    RestrictedComponent,
    AboutComponent
  ],
  imports: [
    CommonModule,
    RestrictedRoutingModule,
    SharedModule
  ]
})
export class RestrictedModule { }
