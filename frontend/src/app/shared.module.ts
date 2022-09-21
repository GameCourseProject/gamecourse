import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from "@angular/router";
import { FormsModule } from "@angular/forms";

// Pipes
import { AsPipe } from "./_pipes/as.pipe";
import { SanitizeHTMLPipe } from "./_pipes/sanitize-html.pipe";

// Directives
import { ClickedOutsideDirective } from "./_directives/clicked-outside.directive";
import { ViewSelectionDirective } from "./_directives/view-selection.directive";
import { GoToPageDirective } from "./_directives/events/go-to-page.directive";
import { HideViewDirective } from "./_directives/events/hide-view.directive";
import { ShowViewDirective } from "./_directives/events/show-view.directive";
import { ToggleViewDirective } from "./_directives/events/toggle-view.directive";

// Components: layout
import { NavbarComponent } from './_components/layout/navbar/navbar.component';
import { SidebarComponent } from './_components/layout/sidebar/sidebar.component';

// Components: modals
import { ModalComponent } from './_components/modals/modal/modal.component';
import { SimpleModalComponent } from './_components/modals/simple-modal/simple-modal.component';
import { ErrorModalComponent } from './_components/modals/error-modal/error-modal.component';
import { FilePickerModalComponent } from "./_components/modals/file-picker-modal/file-picker-modal.component";

// Components: inputs
import { InputTextComponent } from "./_components/inputs/general/input-text/input-text.component";
import { InputNumberComponent } from "./_components/inputs/general/input-number/input-number.component";
import { InputSearchComponent } from './_components/inputs/general/input-search/input-search.component';
import { InputUrlComponent } from './_components/inputs/general/input-url/input-url.component';
import { InputTextareaComponent } from './_components/inputs/general/input-textarea/input-textarea.component';
import { InputEmailComponent } from './_components/inputs/personal-info/input-email/input-email.component';
import { InputColorComponent } from './_components/inputs/color/input-color/input-color.component';
import { InputFileComponent } from './_components/inputs/general/input-file/input-file.component';
import { InputCodeComponent } from './_components/inputs/code/input-code/input-code.component';
import { InputRichTextComponent } from "./_components/inputs/rich-text/input-rich-text/input-rich-text.component";
import { InputCheckboxComponent } from './_components/inputs/checkbox & radio/input-checkbox/input-checkbox.component';
import { InputRadioComponent } from './_components/inputs/checkbox & radio/input-radio/input-radio.component';
import { InputToggleComponent } from './_components/inputs/toggle/input-toggle/input-toggle.component';
import { InputSelectComponent } from './_components/inputs/select/input-select/input-select.component';
import { InputSelectWeekdayComponent } from './_components/inputs/select/input-select-weekday/input-select-weekday.component';
import { InputDateComponent } from './_components/inputs/date & time/input-date/input-date.component';
import { InputTimeComponent } from './_components/inputs/date & time/input-time/input-time.component';
import { InputDatetimeComponent } from './_components/inputs/date & time/input-datetime/input-datetime.component';
import { ThemeTogglerComponent } from './_components/inputs/misc/theme-toggler/theme-toggler.component';

// Components: charts
import { LineChartComponent } from "./_components/charts/line-chart/line-chart.component";
import { BarChartComponent } from "./_components/charts/bar-chart/bar-chart.component";
import { ProgressChartComponent } from "./_components/charts/progress-chart/progress-chart.component";
import { RadarChartComponent } from "./_components/charts/radar-chart/radar-chart.component";

// Components: tables
import { TableComponent } from "./_components/tables/table/table.component";
import { TableData } from "./_components/tables/table-data/table-data.component";

// Components: cards
import { CourseCardComponent } from './_components/cards/course-card/course-card.component';

// Components: alerts
import { AlertComponent } from './_components/alerts/alert/alert.component';

// Components: spinners
import { SpinnerComponent } from './_components/spinners/spinner/spinner.component';

// Components: skeletons
import { CourseSkeletonComponent } from './_components/skeletons/course-skeleton/course-skeleton.component';

// Components: helpers
import { ImportHelperComponent } from './_components/helpers/import-helper/import-helper.component';

// Components: building blocks
import { BBAnyComponent } from "./_components/building-blocks/any/any.component";
import { BBBlockComponent } from './_components/building-blocks/block/block.component';
import { BBTextComponent } from './_components/building-blocks/text/text.component';
import { BBImageComponent } from './_components/building-blocks/image/image.component';
import { BBHeaderComponent } from './_components/building-blocks/header/header.component';
import { BBTableComponent } from './_components/building-blocks/table/table.component';
import { BBChartComponent } from "./_components/building-blocks/chart/chart.component";

// Components: misc
import { AutoGameToastComponent } from './_components/misc/autogame-toast/auto-game-toast.component';
import { HeaderComponent } from './_components/misc/header/header.component';
import { TopActionsComponent } from './_components/misc/top-actions/top-actions.component';
import { LoaderComponent } from './_components/misc/loader/loader.component';

import { PageNotFoundComponent } from './_components/misc/pages/page-not-found/page-not-found.component';
import { NoAccessComponent } from './_components/misc/pages/no-access/no-access.component';
import { ComingSoonComponent } from './_components/misc/pages/coming-soon/coming-soon.component';

// Libraries
import { NgIconsModule } from "@ng-icons/core";
import { DataTablesModule } from "angular-datatables";
import { NgApexchartsModule } from "ng-apexcharts";

// Icons
import {
  FeatherAlertTriangle,
  FeatherCheckCircle,
  FeatherHome,
  FeatherInfo,
  FeatherLayout,
  FeatherLogOut,
  FeatherMenu,
  FeatherMoon,
  FeatherPlusCircle,
  FeatherSearch,
  FeatherSun,
  FeatherUser,
  FeatherUsers,
  FeatherX,
  FeatherXCircle
} from "@ng-icons/feather-icons";

import {
  JamDownload,
  JamEyeF,
  JamGoogle,
  JamFacebook,
  JamFilesF,
  JamLinkedin,
  JamPencilF,
  JamStopSign,
  JamTrashF,
  JamUpload
} from "@ng-icons/jam-icons";

import {
  TablerArchive,
  TablerArrowBackUp,
  TablerArrowNarrowUp,
  TablerArrowNarrowDown,
  TablerBarrierBlock,
  TablerBooks,
  TablerCaretDown,
  TablerClipboardList,
  TablerCloudUpload,
  TablerColorSwatch,
  TablerIdBadge2,
  TablerPlug,
  TablerPrompt,
  TablerSchool
} from "@ng-icons/tabler-icons";


@NgModule({
  declarations: [
    AsPipe,
    SanitizeHTMLPipe,

    ClickedOutsideDirective,
    ViewSelectionDirective,
    GoToPageDirective,
    HideViewDirective,
    ShowViewDirective,
    ToggleViewDirective,

    NavbarComponent,
    SidebarComponent,

    ModalComponent,
    SimpleModalComponent,
    ErrorModalComponent,
    FilePickerModalComponent,

    InputTextComponent,
    InputNumberComponent,
    InputSearchComponent,
    InputUrlComponent,
    InputTextareaComponent,
    InputEmailComponent,
    InputColorComponent,
    InputFileComponent,
    InputCodeComponent,
    InputRichTextComponent,
    InputCheckboxComponent,
    InputRadioComponent,
    InputToggleComponent,
    InputSelectComponent,
    InputSelectWeekdayComponent,
    InputDateComponent,
    InputTimeComponent,
    InputDatetimeComponent,
    ThemeTogglerComponent,

    LineChartComponent,
    BarChartComponent,
    ProgressChartComponent,
    RadarChartComponent,

    TableComponent,
    TableData,

    CourseCardComponent,

    AlertComponent,

    SpinnerComponent,

    CourseSkeletonComponent,

    ImportHelperComponent,

    BBAnyComponent,
    BBBlockComponent,
    BBTextComponent,
    BBImageComponent,
    BBHeaderComponent,
    BBTableComponent,
    BBChartComponent,

    AutoGameToastComponent,
    HeaderComponent,
    TopActionsComponent,
    LoaderComponent,
    PageNotFoundComponent,
    NoAccessComponent,
    ComingSoonComponent
  ],
  exports: [
    AsPipe,
    SanitizeHTMLPipe,

    ClickedOutsideDirective,
    ViewSelectionDirective,
    GoToPageDirective,
    HideViewDirective,
    ShowViewDirective,
    ToggleViewDirective,

    NavbarComponent,
    SidebarComponent,

    ModalComponent,
    SimpleModalComponent,
    ErrorModalComponent,
    FilePickerModalComponent,

    InputTextComponent,
    InputNumberComponent,
    InputSearchComponent,
    InputUrlComponent,
    InputTextareaComponent,
    InputEmailComponent,
    InputColorComponent,
    InputFileComponent,
    InputCodeComponent,
    InputRichTextComponent,
    InputCheckboxComponent,
    InputRadioComponent,
    InputToggleComponent,
    InputSelectComponent,
    InputSelectWeekdayComponent,
    InputDateComponent,
    InputTimeComponent,
    InputDatetimeComponent,
    ThemeTogglerComponent,

    LineChartComponent,
    BarChartComponent,
    ProgressChartComponent,
    RadarChartComponent,

    TableComponent,
    TableData,

    CourseCardComponent,

    AlertComponent,

    SpinnerComponent,

    CourseSkeletonComponent,

    ImportHelperComponent,

    BBAnyComponent,
    BBBlockComponent,
    BBTextComponent,
    BBImageComponent,
    BBHeaderComponent,
    BBTableComponent,
    BBChartComponent,

    AutoGameToastComponent,
    HeaderComponent,
    TopActionsComponent,
    LoaderComponent,
    PageNotFoundComponent,
    NoAccessComponent,
    ComingSoonComponent,

    NgIconsModule,
    FormsModule
  ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule,

    NgIconsModule.withIcons({
      FeatherAlertTriangle,
      FeatherCheckCircle,
      FeatherHome,
      FeatherInfo,
      FeatherLayout,
      FeatherLogOut,
      FeatherMenu,
      FeatherMoon,
      FeatherPlusCircle,
      FeatherSearch,
      FeatherSun,
      FeatherUser,
      FeatherUsers,
      FeatherX,
      FeatherXCircle,

      JamDownload,
      JamEyeF,
      JamGoogle,
      JamFacebook,
      JamFilesF,
      JamLinkedin,
      JamPencilF,
      JamStopSign,
      JamTrashF,
      JamUpload,

      TablerArchive,
      TablerArrowBackUp,
      TablerArrowNarrowUp,
      TablerArrowNarrowDown,
      TablerBarrierBlock,
      TablerBooks,
      TablerCaretDown,
      TablerClipboardList,
      TablerCloudUpload,
      TablerColorSwatch,
      TablerIdBadge2,
      TablerPlug,
      TablerPrompt,
      TablerSchool
    }),
    DataTablesModule,
    NgApexchartsModule
  ]
})
export class SharedModule { }
