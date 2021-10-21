import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from './_components/navbar/navbar.component';
import {RouterModule} from "@angular/router";
import { PageNotFoundComponent } from './_components/page-not-found/page-not-found.component';
import { SidebarComponent } from './_components/sidebar/sidebar.component';
import {FormsModule} from "@angular/forms";
import { NoAccessComponent } from './_components/no-access/no-access.component';
import { ModalComponent } from './_components/modals/modal/modal.component';
import {ClickedOutsideDirective} from "./_directives/clicked-outside.directive";
import { VerificationModalComponent } from './_components/modals/verification-modal/verification-modal.component';
import { InputFileComponent } from './_components/inputs/general/input-file/input-file.component';
import { ErrorModalComponent } from './_components/modals/error-modal/error-modal.component';
import { FooterComponent } from './_components/footer/footer.component';
import { InputCodeComponent } from './_components/inputs/code/input-code/input-code.component';
import { BlockComponent } from './_components/building-blocks/block/block.component';
import { TextComponent } from './_components/building-blocks/text/text.component';
import { ImageComponent } from './_components/building-blocks/image/image.component';
import { TableComponent } from './_components/building-blocks/table/table.component';
import { HeaderComponent } from './_components/building-blocks/header/header.component';
import {AnyComponent} from "./_components/building-blocks/any/any.component";
import {AsPipe} from "./_pipes/as.pipe";
import {ViewSelectionDirective} from "./_directives/view-selection.directive";


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
    HeaderComponent,
    AnyComponent,
    AsPipe,
    ViewSelectionDirective
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
        ImageComponent,
        HeaderComponent,
        BlockComponent,
        AnyComponent,
        TableComponent,
        AsPipe
    ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule
  ]
})
export class SharedModule { }
