import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { UsersRoutingModule } from './users-routing.module';
import { UsersComponent } from './users/users.component';
import {SharedModule} from "../../_components/shared.module";


@NgModule({
  declarations: [
    UsersComponent
  ],
    imports: [
        CommonModule,
        UsersRoutingModule,
        SharedModule
    ]
})
export class UsersModule { }
