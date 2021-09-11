import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CoursesRoutingModule } from './courses-routing.module';
import { MainComponent } from './main/main.component';
import {SharedModule} from "../../_components/shared.module";
import {FormsModule} from "@angular/forms";


@NgModule({
  declarations: [
    MainComponent
  ],
    imports: [
        CommonModule,
        CoursesRoutingModule,
        SharedModule,
        FormsModule
    ]
})
export class CoursesModule { }
