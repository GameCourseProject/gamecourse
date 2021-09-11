import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { MyInfoRoutingModule } from './my-info-routing.module';
import { MainComponent } from './main/main.component';
import {SharedModule} from "../../_components/shared.module";
import {FormsModule} from "@angular/forms";


@NgModule({
  declarations: [
    MainComponent
  ],
  imports: [
    CommonModule,
    MyInfoRoutingModule,
    SharedModule,
    FormsModule
  ]
})
export class MyInfoModule { }
