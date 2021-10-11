import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { UsersRoutingModule } from './users-routing.module';
import { UsersComponent } from './users/users.component';
import {SharedModule} from "../../shared.module";
import {FormsModule} from "@angular/forms";


@NgModule({
  declarations: [
    UsersComponent
  ],
    imports: [
        CommonModule,
        UsersRoutingModule,
        SharedModule,
        FormsModule
    ]
})
export class UsersModule { }
