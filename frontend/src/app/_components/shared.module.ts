import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from './navbar/navbar.component';
import {RouterModule} from "@angular/router";
import { PageNotFoundComponent } from './page-not-found/page-not-found.component';
import { SidebarComponent } from './sidebar/sidebar.component';
import {FormsModule} from "@angular/forms";
import { NoAccessComponent } from './no-access/no-access.component';
import { ModalComponent } from './modals/modal/modal.component';
import {ClickedOutsideDirective} from "../_directives/clicked-outside.directive";
import { VerificationModalComponent } from './modals/verification-modal/verification-modal.component';
import { InputFileComponent } from './inputs/general/input-file/input-file.component';


@NgModule({
  declarations: [
    NavbarComponent,
    PageNotFoundComponent,
    SidebarComponent,
    NoAccessComponent,
    ModalComponent,
    ClickedOutsideDirective,
    VerificationModalComponent,
    InputFileComponent
  ],
  exports: [
    NavbarComponent,
    SidebarComponent,
    ModalComponent,
    VerificationModalComponent,
    InputFileComponent
  ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule
  ]
})
export class SharedModule { }
