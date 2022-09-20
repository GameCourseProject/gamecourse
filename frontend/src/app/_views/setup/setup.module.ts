import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { SetupRoutingModule } from './setup-routing.module';
import { SetupComponent } from './setup/setup.component';
import { ReactiveFormsModule } from "@angular/forms";


@NgModule({
  declarations: [
    SetupComponent
  ],
  imports: [
    CommonModule,
    SetupRoutingModule,
    ReactiveFormsModule
  ]
})
export class SetupModule { }
