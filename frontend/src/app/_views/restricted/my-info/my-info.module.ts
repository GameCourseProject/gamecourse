import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { MyInfoRoutingModule } from './my-info-routing.module';
import { MyInfoComponent } from './my-info/my-info.component';
import {FormsModule} from "@angular/forms";
import {SharedModule} from "../../../shared.module";


@NgModule({
  declarations: [
    MyInfoComponent
  ],
  imports: [
    CommonModule,
    MyInfoRoutingModule,
    FormsModule,
    SharedModule
  ]
})
export class MyInfoModule { }
