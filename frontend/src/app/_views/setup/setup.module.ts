import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { SetupRoutingModule } from './setup-routing.module';
import { SetupComponent } from './setup/setup.component';
import {SharedModule} from "../../shared.module";


@NgModule({
  declarations: [
    SetupComponent
  ],
  imports: [
    CommonModule,
    SetupRoutingModule,
    SharedModule
  ]
})
export class SetupModule { }
