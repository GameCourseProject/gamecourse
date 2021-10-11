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
import { ErrorModalComponent } from './modals/error-modal/error-modal.component';
import { FooterComponent } from './footer/footer.component';
import { InputCodeComponent } from './inputs/code/input-code/input-code.component';
import { BlockComponent } from './building-blocks/block/block.component';
import { TextComponent } from './building-blocks/text/text.component';
import { ImageComponent } from './building-blocks/image/image.component';
import { TableComponent } from './building-blocks/table/table.component';
import { HeaderComponent } from './building-blocks/header/header.component';


@NgModule({
  declarations: [
    NavbarComponent,
    PageNotFoundComponent,
    SidebarComponent,
    NoAccessComponent,
    ModalComponent,
    ClickedOutsideDirective,
    VerificationModalComponent,
    InputFileComponent,
    ErrorModalComponent,
    FooterComponent,
    InputCodeComponent,
    BlockComponent,
    TextComponent,
    ImageComponent,
    TableComponent,
    HeaderComponent
  ],
    exports: [
        NavbarComponent,
        SidebarComponent,
        ModalComponent,
        VerificationModalComponent,
        InputFileComponent,
        ErrorModalComponent,
        FooterComponent,
        InputCodeComponent,
        TextComponent,
        ImageComponent
    ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule
  ]
})
export class SharedModule { }
