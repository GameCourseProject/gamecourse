import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CoursesRoutingModule } from './courses-routing.module';
import { MainComponent } from './main/main.component';
import {SharedModule} from "../../_components/shared.module";


@NgModule({
  declarations: [
    MainComponent
  ],
  imports: [
    CommonModule,
    CoursesRoutingModule,
    SharedModule
  ]
})
export class CoursesModule { }
