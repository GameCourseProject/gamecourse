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
import { AutoGameToastComponent } from './_components/autogame-toast/auto-game-toast.component';
import { InputCodeComponent } from './_components/inputs/code/input-code/input-code.component';
import { BlockComponent } from './_components/building-blocks/block/block.component';
import { TextComponent } from './_components/building-blocks/text/text.component';
import { ImageComponent } from './_components/building-blocks/image/image.component';
import { TableComponent } from './_components/building-blocks/table/table.component';
import { HeaderComponent } from './_components/building-blocks/header/header.component';
import {AnyComponent} from "./_components/building-blocks/any/any.component";
import {AsPipe} from "./_pipes/as.pipe";
import {ViewSelectionDirective} from "./_directives/view-selection.directive";
import {GoToPageDirective} from "./_directives/events/go-to-page.directive";
import {HideViewDirective} from "./_directives/events/hide-view.directive";
import {ShowViewDirective} from "./_directives/events/show-view.directive";
import {ToggleViewDirective} from "./_directives/events/toggle-view.directive";
import {ChartComponent} from "./_components/building-blocks/chart/chart.component";
import {NgApexchartsModule} from "ng-apexcharts";
import {LineChartComponent} from "./_components/charts/line-chart/line-chart.component";
import {BarChartComponent} from "./_components/charts/bar-chart/bar-chart.component";
import {ProgressChartComponent} from "./_components/charts/progress-chart/progress-chart.component";
import {RadarChartComponent} from "./_components/charts/radar-chart/radar-chart.component";
import {InputRichTextComponent} from "./_components/inputs/rich-text/input-rich-text/input-rich-text.component";
import {FilePickerModalComponent} from "./_components/modals/file-picker-modal/file-picker-modal.component";
import {SanitizeHTMLPipe} from "./_pipes/sanitize-html.pipe";
import {DatatableComponent} from "./_components/tables/datatable/datatable.component";
import { InputColorComponent } from './_components/inputs/general/input-color/input-color.component';
import { ThemeTogglerComponent } from './_components/theming/theme-toggler/theme-toggler.component';
import {NgIconsModule} from "@ng-icons/core";


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
    AutoGameToastComponent,
    InputCodeComponent,
    BlockComponent,
    TextComponent,
    ImageComponent,
    TableComponent,
    HeaderComponent,
    ChartComponent,
    AnyComponent,
    AsPipe,
    ViewSelectionDirective,
    GoToPageDirective,
    HideViewDirective,
    ShowViewDirective,
    ToggleViewDirective,
    LineChartComponent,
    BarChartComponent,
    ProgressChartComponent,
    RadarChartComponent,
    InputRichTextComponent,
    FilePickerModalComponent,
    SanitizeHTMLPipe,
    DatatableComponent,
    InputColorComponent,
    ThemeTogglerComponent
  ],
    exports: [
        NavbarComponent,
        SidebarComponent,
        ModalComponent,
        VerificationModalComponent,
        InputFileComponent,
        ErrorModalComponent,
        AutoGameToastComponent,
        InputCodeComponent,
        TextComponent,
        ImageComponent,
        HeaderComponent,
        BlockComponent,
        AnyComponent,
        TableComponent,
        AsPipe,
        ClickedOutsideDirective,
        InputRichTextComponent,
        SanitizeHTMLPipe,
        DatatableComponent,
        InputColorComponent,
        ThemeTogglerComponent
    ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule,
    NgApexchartsModule,
    NgIconsModule,
  ]
})
export class SharedModule { }
